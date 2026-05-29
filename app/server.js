const express = require('express');
const jwt = require('jsonwebtoken');
const path = require('path');
const sequelize = require('./database');
const User = require('./models/User');
const Product = require('./models/Product');
const CartItem = require('./models/CartItem');
const Order = require('./models/Order');
const PaymentTransaction = require('./models/PaymentTransaction');
const createEpaycoClient = require('epayco-sdk-node');

require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = process.env.JWT_SECRET || 'super-secret-key-zame';
const EPAYCO_MOCK = (process.env.EPAYCO_MOCK || '').toLowerCase() === 'true';
const EPAYCO_TEST_MODE = (process.env.EPAYCO_TEST_MODE || 'true').toLowerCase() !== 'false';
const EPAYCO_PUBLIC_KEY = process.env.EPAYCO_PUBLIC_KEY || '';
const EPAYCO_PRIVATE_KEY = process.env.EPAYCO_PRIVATE_KEY || '';
const EPAYCO_TEST_PRICE_DIVISOR = Number(process.env.EPAYCO_TEST_PRICE_DIVISOR || 10);
const EPAYCO_TEST_MAX_AMOUNT = Number(process.env.EPAYCO_TEST_MAX_AMOUNT || 200000);
const PAYMENT_PROVIDER = 'epayco';

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

const authenticateToken = (req, res, next) => {
  const authHeader = req.headers.authorization;
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) return res.status(401).json({ error: 'Acceso no autorizado' });

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) return res.status(403).json({ error: 'Token invalido o expirado' });
    req.user = user;
    next();
  });
};

function getClientIp(req) {
  const forwardedFor = req.headers['x-forwarded-for'];
  if (forwardedFor) return forwardedFor.split(',')[0].trim();
  return req.socket.remoteAddress || '127.0.0.1';
}

function splitFullName(fullName) {
  const parts = String(fullName || '').trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return { name: 'Cliente', lastName: 'ZAME' };
  if (parts.length === 1) return { name: parts[0], lastName: 'ZAME' };

  return {
    name: parts.slice(0, -1).join(' '),
    lastName: parts[parts.length - 1]
  };
}

function parseCardExpiry(cardExpiry) {
  const match = String(cardExpiry || '').trim().match(/^(\d{1,2})\s*\/\s*(\d{2}|\d{4})$/);
  if (!match) return null;

  const month = match[1].padStart(2, '0');
  const year = match[2].length === 2 ? `20${match[2]}` : match[2];

  if (Number(month) < 1 || Number(month) > 12) return null;
  return { month, year };
}

function maskCardNumber(cardNumber) {
  const digits = String(cardNumber || '').replace(/\D/g, '');
  if (digits.length < 8) return '****';
  return `${digits.slice(0, 6)}******${digits.slice(-4)}`;
}

function isEpaycoTestPricingEnabled() {
  return EPAYCO_TEST_MODE && Number.isFinite(EPAYCO_TEST_PRICE_DIVISOR) && EPAYCO_TEST_PRICE_DIVISOR > 1;
}

function getEffectiveProductPrice(price) {
  const numericPrice = Number(price);
  if (!Number.isFinite(numericPrice)) return 0;

  if (!isEpaycoTestPricingEnabled()) return numericPrice;
  return Math.round((numericPrice / EPAYCO_TEST_PRICE_DIVISOR) * 100) / 100;
}

function serializeProduct(product) {
  const data = typeof product.toJSON === 'function' ? product.toJSON() : { ...product };
  return {
    ...data,
    price: getEffectiveProductPrice(data.price).toFixed(2)
  };
}

function serializeCartItem(cartItem) {
  const data = typeof cartItem.toJSON === 'function' ? cartItem.toJSON() : { ...cartItem };
  if (data.Product) {
    data.Product = serializeProduct(data.Product);
  }
  return data;
}

function validateEpaycoTestAmount(total) {
  if (!EPAYCO_TEST_MODE) return null;
  if (!Number.isFinite(EPAYCO_TEST_MAX_AMOUNT) || EPAYCO_TEST_MAX_AMOUNT <= 0) return null;
  if (total <= EPAYCO_TEST_MAX_AMOUNT) return null;

  return `En modo de pruebas ePayco el total maximo permitido es ${EPAYCO_TEST_MAX_AMOUNT.toLocaleString('es-CO')} COP`;
}

function extractNestedValue(source, paths) {
  for (const pathKey of paths) {
    const value = pathKey.split('.').reduce((current, key) => {
      if (current && Object.prototype.hasOwnProperty.call(current, key)) {
        return current[key];
      }
      return undefined;
    }, source);

    if (value !== undefined && value !== null && value !== '') return value;
  }

  return null;
}

function normalizeEpaycoPaymentResult(rawResponse) {
  const statusText = String(extractNestedValue(rawResponse, [
    'data.estado',
    'data.x_response',
    'estado',
    'x_response',
    'response'
  ]) || '').toUpperCase();

  const errorMessages = Array.isArray(rawResponse && rawResponse.data && rawResponse.data.errors)
    ? rawResponse.data.errors.map(error => error.errorMessage).filter(Boolean).join('; ')
    : '';

  const responseText = String(errorMessages || extractNestedValue(rawResponse, [
    'data.respuesta',
    'data.x_response_reason_text',
    'data.description',
    'message',
    'text_response',
    'respuesta',
    'x_response_reason_text'
  ]) || '');

  const responseCode = String(extractNestedValue(rawResponse, [
    'data.cod_respuesta',
    'data.x_cod_response',
    'cod_respuesta',
    'x_cod_response'
  ]) || '');

  const providerReference = String(extractNestedValue(rawResponse, [
    'data.ref_payco',
    'data.recibo',
    'data.transactionID',
    'ref_payco',
    'recibo',
    'transactionID'
  ]) || '');

  const approved = statusText.includes('ACEPTADA') ||
    statusText.includes('APROBADA') ||
    responseText.toUpperCase().includes('APROBADA') ||
    responseCode === '00' ||
    responseCode === '1';

  const pending = statusText.includes('PENDIENTE') || responseCode === '3';

  return {
    approved,
    status: approved ? 'APPROVED' : pending ? 'PENDING' : 'FAILED',
    providerReference,
    responseText: responseText || statusText || 'Sin detalle de respuesta',
    rawResponse
  };
}

function getEpaycoClient() {
  if (!EPAYCO_PUBLIC_KEY || !EPAYCO_PRIVATE_KEY) {
    throw new Error('Faltan EPAYCO_PUBLIC_KEY y EPAYCO_PRIVATE_KEY');
  }

  return createEpaycoClient({
    apiKey: EPAYCO_PUBLIC_KEY,
    privateKey: EPAYCO_PRIVATE_KEY,
    lang: 'ES',
    test: EPAYCO_TEST_MODE
  });
}

function validateCheckoutPayload(body) {
  const requiredFields = [
    'billingName',
    'billingAddress',
    'billingCity',
    'customerEmail',
    'customerPhone',
    'docType',
    'docNumber',
    'cardNumber',
    'cardExpiry',
    'cardCvc'
  ];

  const missing = requiredFields.filter(field => !String(body[field] || '').trim());
  if (missing.length > 0) {
    return `Faltan datos requeridos: ${missing.join(', ')}`;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(body.customerEmail)) {
    return 'El correo electronico no tiene un formato valido';
  }

  if (!parseCardExpiry(body.cardExpiry)) {
    return 'La fecha de vencimiento debe tener formato MM/AA o MM/AAAA';
  }

  if (String(body.cardNumber).replace(/\D/g, '').length < 13) {
    return 'El numero de tarjeta no parece valido';
  }

  if (String(body.cardCvc).replace(/\D/g, '').length < 3) {
    return 'El codigo de seguridad no parece valido';
  }

  return null;
}

function buildPaymentInfo({ tokenId, customerId, order, checkout, total, req }) {
  const { name, lastName } = splitFullName(checkout.billingName);

  return {
    token_card: tokenId,
    customer_id: customerId,
    doc_type: checkout.docType,
    doc_number: checkout.docNumber,
    name,
    last_name: lastName,
    email: checkout.customerEmail,
    city: checkout.billingCity,
    address: checkout.billingAddress,
    phone: checkout.customerPhone,
    cell_phone: checkout.customerPhone,
    bill: `ZAME-${order.id}`,
    description: `Pedido ZAME SCENT #${order.id}`,
    value: String(total),
    tax: '0',
    tax_base: '0',
    currency: 'COP',
    dues: String(checkout.dues || '1'),
    ip: getClientIp(req),
    url_response: process.env.EPAYCO_RESPONSE_URL || 'http://localhost:3000/checkout.html',
    url_confirmation: process.env.EPAYCO_CONFIRMATION_URL || 'http://localhost:3000/api/payments/epayco/confirmation',
    method_confirmation: 'POST',
    extras: {
      extra1: String(order.id),
      extra2: String(order.userId),
      extra3: PAYMENT_PROVIDER
    }
  };
}

async function processEpaycoPayment({ order, checkout, total, req }) {
  const cardDigits = String(checkout.cardNumber || '').replace(/\D/g, '');
  const expiry = parseCardExpiry(checkout.cardExpiry);
  const maskedCard = maskCardNumber(cardDigits);

  if (EPAYCO_MOCK) {
    const reference = `DEV-${Date.now()}`;
    return {
      approved: true,
      status: 'APPROVED',
      providerReference: reference,
      responseText: 'Pago aprobado en modo desarrollo local',
      rawResponse: {
        provider: PAYMENT_PROVIDER,
        mock: true,
        maskedCard,
        data: {
          ref_payco: reference,
          estado: 'Aceptada',
          respuesta: 'Aprobada',
          cod_respuesta: '00'
        }
      }
    };
  }

  const epayco = getEpaycoClient();
  const tokenResponse = await epayco.token.create({
    'card[number]': cardDigits,
    'card[exp_year]': expiry.year,
    'card[exp_month]': expiry.month,
    'card[cvc]': String(checkout.cardCvc).replace(/\D/g, ''),
    hasCvv: true
  });

  const tokenId = extractNestedValue(tokenResponse, ['id', 'data.id', 'token', 'data.token']);
  if (!tokenId) {
    throw new Error('ePayco no retorno token de tarjeta');
  }

  const { name, lastName } = splitFullName(checkout.billingName);
  const customerResponse = await epayco.customers.create({
    token_card: tokenId,
    name,
    last_name: lastName,
    email: checkout.customerEmail,
    default: true,
    city: checkout.billingCity,
    address: checkout.billingAddress,
    phone: checkout.customerPhone,
    cell_phone: checkout.customerPhone
  });

  const customerId = extractNestedValue(customerResponse, [
    'data.customerId',
    'data.customer_id',
    'data.id',
    'customerId',
    'customer_id',
    'id'
  ]);

  if (!customerId) {
    throw new Error('ePayco no retorno id de cliente');
  }

  const chargeResponse = await epayco.charge.create(
    buildPaymentInfo({ tokenId, customerId, order, checkout, total, req })
  );

  return normalizeEpaycoPaymentResult(chargeResponse);
}

async function getCartSummary(userId, transaction) {
  const cartItems = await CartItem.findAll({
    where: { userId },
    include: [Product],
    transaction
  });

  let total = 0;
  const itemSummaries = cartItems.map(item => {
    const price = getEffectiveProductPrice(item.Product.price);
    const subtotal = price * item.quantity;
    total += subtotal;
    return {
      productId: item.productId,
      name: item.Product.name,
      price,
      quantity: item.quantity,
      subtotal
    };
  });

  return { cartItems, itemSummaries, total };
}

app.post('/api/auth/register', async (req, res) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.status(400).json({ error: 'Usuario y contrasena son requeridos' });
    }

    const existingUser = await User.findOne({ where: { username } });
    if (existingUser) {
      return res.status(400).json({ error: 'El nombre de usuario ya esta registrado' });
    }

    const user = await User.create({ username, password });
    res.status(201).json({ message: 'Usuario registrado exitosamente', userId: user.id });
  } catch (error) {
    res.status(500).json({ error: 'Error interno en el servidor' });
  }
});

app.post('/api/auth/login', async (req, res) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.status(400).json({ error: 'Usuario y contrasena son requeridos' });
    }

    const user = await User.findOne({ where: { username } });
    if (!user || !(await user.comparePassword(password))) {
      return res.status(401).json({ error: 'Credenciales invalidas' });
    }

    const token = jwt.sign({ id: user.id, username: user.username }, JWT_SECRET, { expiresIn: '24h' });
    res.json({ token, username: user.username });
  } catch (error) {
    res.status(500).json({ error: 'Error interno en el servidor' });
  }
});

app.get('/api/products', async (req, res) => {
  try {
    const products = await Product.findAll();
    res.json(products.map(serializeProduct));
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener el catalogo' });
  }
});

app.get('/api/products/:id', async (req, res) => {
  try {
    const product = await Product.findByPk(req.params.id);
    if (!product) return res.status(404).json({ error: 'Producto no encontrado' });
    res.json(serializeProduct(product));
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener detalles del producto' });
  }
});

app.get('/api/cart', authenticateToken, async (req, res) => {
  try {
    const cartItems = await CartItem.findAll({
      where: { userId: req.user.id },
      include: [Product]
    });
    res.json(cartItems.map(serializeCartItem));
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener el carrito' });
  }
});

app.post('/api/cart', authenticateToken, async (req, res) => {
  try {
    const { productId, quantity } = req.body;
    if (!productId) return res.status(400).json({ error: 'ID de producto requerido' });

    const product = await Product.findByPk(productId);
    if (!product) return res.status(404).json({ error: 'Producto no encontrado' });

    let cartItem = await CartItem.findOne({
      where: { userId: req.user.id, productId }
    });

    const qtyToAdd = parseInt(quantity, 10) || 1;

    if (cartItem) {
      cartItem.quantity += qtyToAdd;
      await cartItem.save();
    } else {
      cartItem = await CartItem.create({
        userId: req.user.id,
        productId,
        quantity: qtyToAdd
      });
    }

    res.status(201).json({ message: 'Producto agregado al carrito', cartItem });
  } catch (error) {
    res.status(500).json({ error: 'Error al agregar al carrito' });
  }
});

app.put('/api/cart/:id', authenticateToken, async (req, res) => {
  try {
    const { quantity } = req.body;
    if (quantity === undefined) return res.status(400).json({ error: 'Cantidad requerida' });

    const cartItem = await CartItem.findOne({
      where: { id: req.params.id, userId: req.user.id }
    });

    if (!cartItem) return res.status(404).json({ error: 'Item no encontrado' });

    if (parseInt(quantity, 10) <= 0) {
      await cartItem.destroy();
      res.json({ message: 'Producto eliminado del carrito' });
    } else {
      cartItem.quantity = parseInt(quantity, 10);
      await cartItem.save();
      res.json({ message: 'Cantidad actualizada', cartItem });
    }
  } catch (error) {
    res.status(500).json({ error: 'Error al actualizar el carrito' });
  }
});

app.delete('/api/cart/:id', authenticateToken, async (req, res) => {
  try {
    const cartItem = await CartItem.findOne({
      where: { id: req.params.id, userId: req.user.id }
    });

    if (!cartItem) return res.status(404).json({ error: 'Item no encontrado en el carrito' });

    await cartItem.destroy();
    res.json({ message: 'Producto eliminado del carrito' });
  } catch (error) {
    res.status(500).json({ error: 'Error al eliminar del carrito' });
  }
});

app.post('/api/checkout', authenticateToken, async (req, res) => {
  const transaction = await sequelize.transaction();
  let order;

  try {
    const checkoutError = validateCheckoutPayload(req.body);
    if (checkoutError) {
      await transaction.rollback();
      return res.status(400).json({ error: checkoutError });
    }

    const { cartItems, itemSummaries, total } = await getCartSummary(req.user.id, transaction);

    if (cartItems.length === 0) {
      await transaction.rollback();
      return res.status(400).json({ error: 'El carrito de compras esta vacio' });
    }

    const amountError = validateEpaycoTestAmount(total);
    if (amountError) {
      await transaction.rollback();
      return res.status(400).json({ error: amountError, maxAmount: EPAYCO_TEST_MAX_AMOUNT, total });
    }

    order = await Order.create({
      userId: req.user.id,
      total,
      status: 'PENDING_PAYMENT',
      billingName: req.body.billingName,
      billingAddress: req.body.billingAddress,
      billingCity: req.body.billingCity,
      items: JSON.stringify(itemSummaries)
    }, { transaction });

    await transaction.commit();

    const payment = await processEpaycoPayment({
      order,
      checkout: req.body,
      total,
      req
    });

    const postPaymentTransaction = await sequelize.transaction();

    try {
      await PaymentTransaction.create({
        orderId: order.id,
        userId: req.user.id,
        provider: PAYMENT_PROVIDER,
        providerReference: payment.providerReference,
        status: payment.status,
        amount: total,
        currency: 'COP',
        responseText: payment.responseText,
        rawResponse: JSON.stringify(payment.rawResponse)
      }, { transaction: postPaymentTransaction });

      order.status = payment.approved ? 'COMPLETED' : payment.status === 'PENDING' ? 'PENDING_PAYMENT' : 'PAYMENT_FAILED';
      await order.save({ transaction: postPaymentTransaction });

      if (payment.approved) {
        await CartItem.destroy({
          where: { userId: req.user.id },
          transaction: postPaymentTransaction
        });
      }

      await postPaymentTransaction.commit();
    } catch (postPaymentError) {
      await postPaymentTransaction.rollback();
      throw postPaymentError;
    }

    if (!payment.approved) {
      return res.status(402).json({
        error: 'El pago no fue aprobado por ePayco',
        orderId: order.id,
        paymentStatus: payment.status,
        providerReference: payment.providerReference,
        providerMessage: payment.responseText
      });
    }

    return res.status(201).json({
      message: EPAYCO_MOCK
        ? 'Pedido procesado exitosamente (ePayco modo desarrollo)'
        : 'Pedido procesado exitosamente con ePayco',
      orderId: order.id,
      total: order.total,
      paymentProvider: PAYMENT_PROVIDER,
      paymentStatus: payment.status,
      providerReference: payment.providerReference,
      providerMessage: payment.responseText
    });
  } catch (error) {
    if (!transaction.finished) await transaction.rollback();

    if (order) {
      try {
        order.status = 'PAYMENT_ERROR';
        await order.save();
        await PaymentTransaction.create({
          orderId: order.id,
          userId: req.user.id,
          provider: PAYMENT_PROVIDER,
          status: 'ERROR',
          amount: order.total,
          currency: 'COP',
          responseText: error.message,
          rawResponse: JSON.stringify({ error: error.message })
        });
      } catch (loggingError) {
        console.error('Unable to log failed payment:', loggingError);
      }
    }

    console.error('Checkout error:', error);
    res.status(500).json({ error: 'Error al procesar el pago con ePayco', detail: error.message });
  }
});

app.post('/api/payments/epayco/confirmation', express.urlencoded({ extended: true }), async (req, res) => {
  console.log('ePayco confirmation received:', req.body || {});
  res.sendStatus(200);
});

app.get('/health', async (req, res) => {
  try {
    await sequelize.authenticate();
    res.json({ status: 'HEALTHY', database: 'CONNECTED', instance: process.env.INSTANCE_ID || 'local' });
  } catch (error) {
    res.status(500).json({ status: 'UNHEALTHY', database: 'DISCONNECTED', error: error.message });
  }
});

sequelize.sync().then(async () => {
  const count = await Product.count();
  if (count === 0) {
    const seedProducts = [
      {
        name: 'ZAME BACCARAT 540',
        description: 'Inspiracion del iconico perfume Baccarat Rouge 540. Nota floral ambarada y amaderada intensa.',
        price: 290000.00,
        image: '/assets/images/hero-bg.png',
        category: 'Ambarado'
      },
      {
        name: 'ZAME CREED AVENTUS',
        description: 'Inspiracion del rey de las fragancias masculinas, Creed Aventus.',
        price: 250000.00,
        image: '/assets/images/hero-bg.png',
        category: 'Chypre Frutal'
      },
      {
        name: 'ZAME SAUVAGE DIOR',
        description: 'Inspiracion de Sauvage de Dior con notas frescas de bergamota.',
        price: 195000.00,
        image: '/assets/images/hero-bg.png',
        category: 'Fougere'
      },
      {
        name: 'ZAME BLEU DE CHANEL',
        description: 'Inspiracion de Bleu de Chanel. Aroma amaderado aromatico sofisticado.',
        price: 180000.00,
        image: '/assets/images/hero-bg.png',
        category: 'Amaderado'
      },
      {
        name: 'ZAME BLACK OPIUM YSL',
        description: 'Inspiracion de Black Opium con cafe negro, flores blancas y vainilla dulce.',
        price: 185000.00,
        image: '/assets/images/hero-bg.png',
        category: 'Gourmand'
      },
      {
        name: 'ZAME GOOD GIRL CH',
        description: 'Inspiracion de Good Girl con jazmin y haba tonka tostada.',
        price: 190000.00,
        image: '/assets/images/hero-bg.png',
        category: 'Floral Oriental'
      }
    ];

    await Product.bulkCreate(seedProducts);
    console.log('Database synced and seeded successfully.');
  } else {
    console.log('Database synced (existing products found).');
  }

  app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
  });
}).catch(err => {
  console.error('Unable to connect to the database:', err);
});
