const express = require('express');
const jwt = require('jsonwebtoken');
const path = require('path');
const sequelize = require('./database');
const User = require('./models/User');
const Product = require('./models/Product');
const CartItem = require('./models/CartItem');
const Order = require('./models/Order');

require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = process.env.JWT_SECRET || 'super-secret-key-zame';

// Middleware
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Authentication Middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];
  
  if (!token) return res.status(401).json({ error: 'Acceso no autorizado' });
  
  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) return res.status(403).json({ error: 'Token inválido o expirado' });
    req.user = user;
    next();
  });
};

// --- AUTH API ---
app.post('/api/auth/register', async (req, res) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.status(400).json({ error: 'Usuario y contraseña son requeridos' });
    }
    
    const existingUser = await User.findOne({ where: { username } });
    if (existingUser) {
      return res.status(400).json({ error: 'El nombre de usuario ya está registrado' });
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
      return res.status(400).json({ error: 'Usuario y contraseña son requeridos' });
    }
    
    const user = await User.findOne({ where: { username } });
    if (!user || !(await user.comparePassword(password))) {
      return res.status(401).json({ error: 'Credenciales inválidas' });
    }
    
    const token = jwt.sign({ id: user.id, username: user.username }, JWT_SECRET, { expiresIn: '24h' });
    res.json({ token, username: user.username });
  } catch (error) {
    res.status(500).json({ error: 'Error interno en el servidor' });
  }
});

// --- PRODUCT CATALOG API ---
app.get('/api/products', async (req, res) => {
  try {
    const products = await Product.findAll();
    res.json(products);
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener el catálogo' });
  }
});

app.get('/api/products/:id', async (req, res) => {
  try {
    const product = await Product.findByPk(req.params.id);
    if (!product) return res.status(404).json({ error: 'Producto no encontrado' });
    res.json(product);
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener detalles del producto' });
  }
});

// --- SHOPPING CART API ---
app.get('/api/cart', authenticateToken, async (req, res) => {
  try {
    const cartItems = await CartItem.findAll({
      where: { userId: req.user.id },
      include: [Product]
    });
    res.json(cartItems);
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
    
    const qtyToAdd = parseInt(quantity) || 1;
    
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
    
    if (parseInt(quantity) <= 0) {
      await cartItem.destroy();
      res.json({ message: 'Producto eliminado del carrito' });
    } else {
      cartItem.quantity = parseInt(quantity);
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

// --- CHECKOUT API (SIMULATION) ---
app.post('/api/checkout', authenticateToken, async (req, res) => {
  const transaction = await sequelize.transaction();
  try {
    const { billingName, billingAddress, billingCity, cardNumber } = req.body;
    
    if (!billingName || !billingAddress || !billingCity || !cardNumber) {
      await transaction.rollback();
      return res.status(400).json({ error: 'Faltan datos de facturación o pago' });
    }
    
    const cartItems = await CartItem.findAll({
      where: { userId: req.user.id },
      include: [Product],
      transaction
    });
    
    if (cartItems.length === 0) {
      await transaction.rollback();
      return res.status(400).json({ error: 'El carrito de compras está vacío' });
    }
    
    // Calculate total amount
    let total = 0;
    const itemSummaries = cartItems.map(item => {
      const subtotal = item.Product.price * item.quantity;
      total += subtotal;
      return {
        productId: item.productId,
        name: item.Product.name,
        price: item.Product.price,
        quantity: item.quantity,
        subtotal
      };
    });
    
    // Record Order in DB
    const order = await Order.create({
      userId: req.user.id,
      total,
      billingName,
      billingAddress,
      billingCity,
      items: JSON.stringify(itemSummaries)
    }, { transaction });
    
    // Clear user's cart
    await CartItem.destroy({
      where: { userId: req.user.id },
      transaction
    });
    
    await transaction.commit();
    
    res.status(201).json({
      message: 'Pedido procesado exitosamente (Pago Simulado)',
      orderId: order.id,
      total: order.total
    });
  } catch (error) {
    await transaction.rollback();
    res.status(500).json({ error: 'Error al procesar el pago' });
  }
});

// --- HEALTH & STATUS API (For ALB Health checks) ---
app.get('/health', async (req, res) => {
  try {
    await sequelize.authenticate();
    res.json({ status: 'HEALTHY', database: 'CONNECTED', instance: process.env.INSTANCE_ID || 'local' });
  } catch (error) {
    res.status(500).json({ status: 'UNHEALTHY', database: 'DISCONNECTED', error: error.message });
  }
});

// Sync Database and Start Server
sequelize.sync().then(async () => {
  // Check if products table is empty. If so, seed basic products
  const count = await Product.count();
  if (count === 0) {
    const seedProducts = [
      {
        name: "ZAME BACCARAT 540",
        description: "Inspiración del icónico perfume Baccarat Rouge 540 de Maison Francis Kurkdjian. Nota floral ambarada y amaderada intensa, fijación premium de más de 12 horas.",
        price: 290000.00,
        image: "/assets/images/hero-bg.png", // fallback beautiful asset
        category: "Ambarado"
      },
      {
        name: "ZAME CREED AVENTUS",
        description: "Inspiración del rey de las fragancias masculinas, Creed Aventus. Notas frutales de piña, abedul y almizcle. Elegancia, masculinidad y proyección incomparables.",
        price: 250000.00,
        image: "/assets/images/hero-bg.png",
        category: "Chypre Frutal"
      },
      {
        name: "ZAME SAUVAGE DIOR",
        description: "Inspiración de Sauvage de Dior. Notas frescas de bergamota de Calabria y pimienta de Sichuan. Ideal para el hombre versátil y magnético.",
        price: 195000.00,
        image: "/assets/images/hero-bg.png",
        category: "Fougère"
      },
      {
        name: "ZAME BLEU DE CHANEL",
        description: "Inspiración de Bleu de Chanel. Un aroma amaderado aromático sofisticado con notas de pomelo, cedro y sándalo. El clásico de la elegancia masculina.",
        price: 180000.00,
        image: "/assets/images/hero-bg.png",
        category: "Amaderado"
      },
      {
        name: "ZAME BLACK OPIUM YSL",
        description: "Inspiración de Black Opium de Yves Saint Laurent. Deliciosa combinación de café negro, flores blancas y vainilla dulce. Seductora, adictiva y misteriosa.",
        price: 185000.00,
        image: "/assets/images/hero-bg.png",
        category: "Gourmand"
      },
      {
        name: "ZAME GOOD GIRL CH",
        description: "Inspiración de Good Girl de Carolina Herrera. Notas de nardo sabroso, jazmín Sambac y haba tonka tostada. La combinación perfecta de audacia y feminidad.",
        price: 190000.00,
        image: "/assets/images/hero-bg.png",
        category: "Floral Oriental"
      }
    ];
    await Product.bulkCreate(seedProducts);
    console.log("Database synced and seeded successfully.");
  } else {
    console.log("Database synced (existing products found).");
  }
  
  app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
  });
}).catch(err => {
  console.error('Unable to connect to the database:', err);
});
