#!/bin/bash
# ZAME SCENT - Web Server Bootstrap Provisioning Script
# Logs output to user_data.log for SSM debugging
exec > >(tee /var/log/user_data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "========================================================="
echo "🚀 INICIANDO CONFIGURACIÓN DEL SERVIDOR WEB EC2"
echo "========================================================="

# 1. Update and install core system utilities
yum update -y
yum install -y git ruby wget zip unzip

# 2. Install Node.js (NodeSource Node.js 20 LTS setup for Amazon Linux 2023 / AL2)
echo "Installing Node.js 20..."
curl -sL https://rpm.nodesource.com/setup_20.x | bash -
yum install -y nodejs

# Verify installations
node -v
npm -v

# 3. Create application directories
APP_DIR="/opt/zame-app"
mkdir -p $APP_DIR
cd $APP_DIR

# 4. Fetch the application code from S3 bucket
# The S3 bucket name is passed into this script dynamically via Terraform templatefile()
BUCKET_NAME="${s3_bucket_name}"
PRESIGNED_URL="${s3_presigned_url}"

if [ -n "$PRESIGNED_URL" ]; then
    echo "Downloading application bundle from pre-signed S3 URL..."
    curl -L -o ./app-bundle.zip "$PRESIGNED_URL"
else
    echo "Downloading application bundle from S3 bucket: $BUCKET_NAME"
    aws s3 cp s3://$BUCKET_NAME/app-bundle.zip ./app-bundle.zip
fi

if [ -f "./app-bundle.zip" ]; then
    echo "App bundle downloaded successfully. Extracting..."
    unzip -o app-bundle.zip
    rm app-bundle.zip
else
    echo "⚠️ S3 bundle download failed or was empty. Creating inline application fallback."
    
    # Create folder structure
    mkdir -p models public
    
    # Write inline files
    cat << 'EOF' > package.json
{
  "name": "zame-ecommerce",
  "version": "1.0.0",
  "description": "Premium E-commerce scalable application",
  "main": "server.js",
  "scripts": {
    "start": "node server.js"
  },
  "dependencies": {
    "bcryptjs": "^2.4.3",
    "dotenv": "^16.4.5",
    "express": "^4.19.2",
    "jsonwebtoken": "^9.0.2",
    "mysql2": "^3.9.7",
    "sequelize": "^6.37.3",
    "sqlite3": "^5.1.7"
  }
}
EOF

    cat << 'EOF' > database.js
const { Sequelize } = require('sequelize');
require('dotenv').config();

const sequelize = new Sequelize(
  process.env.DB_NAME,
  process.env.DB_USER,
  process.env.DB_PASS,
  {
    host: process.env.DB_HOST,
    dialect: 'mysql',
    port: process.env.DB_PORT || 3306,
    logging: false
  }
);
module.exports = sequelize;
EOF

    # User Model
    cat << 'EOF' > models/User.js
const { DataTypes } = require('sequelize');
const sequelize = require('../database');
const bcrypt = require('bcryptjs');

const User = sequelize.define('User', {
  id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
  username: { type: DataTypes.STRING, allowNull: false, unique: true },
  password: { type: DataTypes.STRING, allowNull: false }
}, {
  hooks: {
    beforeCreate: async (user) => {
      if (user.password) {
        user.password = await bcrypt.hash(user.password, 10);
      }
    }
  }
});

User.prototype.comparePassword = function (password) {
  return bcrypt.compare(password, this.password);
};
module.exports = User;
EOF

    # Product Model
    cat << 'EOF' > models/Product.js
const { DataTypes } = require('sequelize');
const sequelize = require('../database');

const Product = sequelize.define('Product', {
  id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
  name: { type: DataTypes.STRING, allowNull: false },
  description: { type: DataTypes.TEXT, allowNull: true },
  price: { type: DataTypes.DECIMAL(10, 2), allowNull: false },
  image: { type: DataTypes.STRING, allowNull: true },
  category: { type: DataTypes.STRING, allowNull: true }
});
module.exports = Product;
EOF

    # CartItem Model
    cat << 'EOF' > models/CartItem.js
const { DataTypes } = require('sequelize');
const sequelize = require('../database');
const User = require('./User');
const Product = require('./Product');

const CartItem = sequelize.define('CartItem', {
  id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
  userId: { type: DataTypes.INTEGER, allowNull: false, references: { model: User, key: 'id' } },
  productId: { type: DataTypes.INTEGER, allowNull: false, references: { model: Product, key: 'id' } },
  quantity: { type: DataTypes.INTEGER, allowNull: false, defaultValue: 1 }
});

CartItem.belongsTo(User, { foreignKey: 'userId' });
CartItem.belongsTo(Product, { foreignKey: 'productId' });
User.hasMany(CartItem, { foreignKey: 'userId', onDelete: 'CASCADE' });
module.exports = CartItem;
EOF

    # Order Model
    cat << 'EOF' > models/Order.js
const { DataTypes } = require('sequelize');
const sequelize = require('../database');
const User = require('./User');

const Order = sequelize.define('Order', {
  id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
  userId: { type: DataTypes.INTEGER, allowNull: false, references: { model: User, key: 'id' } },
  total: { type: DataTypes.DECIMAL(10, 2), allowNull: false },
  status: { type: DataTypes.STRING, defaultValue: 'COMPLETED' },
  billingName: { type: DataTypes.STRING, allowNull: false },
  billingAddress: { type: DataTypes.STRING, allowNull: false },
  billingCity: { type: DataTypes.STRING, allowNull: false },
  items: { type: DataTypes.TEXT, allowNull: false }
});

Order.belongsTo(User, { foreignKey: 'userId' });
User.hasMany(Order, { foreignKey: 'userId' });
module.exports = Order;
EOF

    # Server main configuration
    cat << 'EOF' > server.js
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

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];
  if (!token) return res.status(401).json({ error: 'Acceso no autorizado' });
  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) return res.status(403).json({ error: 'Token inválido' });
    req.user = user;
    next();
  });
};

app.post('/api/auth/register', async (req, res) => {
  try {
    const { username, password } = req.body;
    const existingUser = await User.findOne({ where: { username } });
    if (existingUser) return res.status(400).json({ error: 'Usuario ya registrado' });
    const user = await User.create({ username, password });
    res.status(201).json({ message: 'Usuario registrado', userId: user.id });
  } catch (error) { res.status(500).json({ error: 'Error del servidor' }); }
});

app.post('/api/auth/login', async (req, res) => {
  try {
    const { username, password } = req.body;
    const user = await User.findOne({ where: { username } });
    if (!user || !(await user.comparePassword(password))) {
      return res.status(401).json({ error: 'Credenciales inválidas' });
    }
    const token = jwt.sign({ id: user.id, username: user.username }, JWT_SECRET, { expiresIn: '24h' });
    res.json({ token, username: user.username });
  } catch (error) { res.status(500).json({ error: 'Error del servidor' }); }
});

app.get('/api/products', async (req, res) => {
  try { res.json(await Product.findAll()); } catch (e) { res.status(500).end(); }
});

app.get('/api/cart', authenticateToken, async (req, res) => {
  try {
    res.json(await CartItem.findAll({ where: { userId: req.user.id }, include: [Product] }));
  } catch (e) { res.status(500).end(); }
});

app.post('/api/cart', authenticateToken, async (req, res) => {
  try {
    const { productId, quantity } = req.body;
    let item = await CartItem.findOne({ where: { userId: req.user.id, productId } });
    if (item) {
      item.quantity += (parseInt(quantity) || 1);
      await item.save();
    } else {
      item = await CartItem.create({ userId: req.user.id, productId, quantity: parseInt(quantity) || 1 });
    }
    res.status(201).json(item);
  } catch (e) { res.status(500).end(); }
});

app.put('/api/cart/:id', authenticateToken, async (req, res) => {
  try {
    const { quantity } = req.body;
    const item = await CartItem.findOne({ where: { id: req.params.id, userId: req.user.id } });
    if (!item) return res.status(404).end();
    if (parseInt(quantity) <= 0) {
      await item.destroy();
    } else {
      item.quantity = parseInt(quantity);
      await item.save();
    }
    res.json({ message: 'Updated' });
  } catch (e) { res.status(500).end(); }
});

app.delete('/api/cart/:id', authenticateToken, async (req, res) => {
  try {
    await CartItem.destroy({ where: { id: req.params.id, userId: req.user.id } });
    res.json({ message: 'Deleted' });
  } catch (e) { res.status(500).end(); }
});

app.post('/api/checkout', authenticateToken, async (req, res) => {
  try {
    const { billingName, billingAddress, billingCity } = req.body;
    const cart = await CartItem.findAll({ where: { userId: req.user.id }, include: [Product] });
    if (cart.length === 0) return res.status(400).json({ error: 'Carrito vacío' });
    let total = 0;
    const summaries = cart.map(i => {
      total += i.Product.price * i.quantity;
      return { productId: i.productId, name: i.Product.name, price: i.Product.price, quantity: i.quantity };
    });
    const order = await Order.create({
      userId: req.user.id, total, billingName, billingAddress, billingCity, items: JSON.stringify(summaries)
    });
    await CartItem.destroy({ where: { userId: req.user.id } });
    res.status(201).json({ orderId: order.id, total: order.total });
  } catch (e) { res.status(500).end(); }
});

app.get('/health', async (req, res) => {
  try {
    await sequelize.authenticate();
    res.json({ status: 'HEALTHY', database: 'CONNECTED', instance: process.env.INSTANCE_ID || 'aws' });
  } catch (err) {
    res.status(500).json({ status: 'UNHEALTHY', error: err.message });
  }
});

sequelize.sync().then(async () => {
  const count = await Product.count();
  if (count === 0) {
    await Product.bulkCreate([
      { name: "ZAME BACCARAT 540", description: "Fijación extrema de más de 12 horas.", price: 290000.00, category: "Ambarado" },
      { name: "ZAME CREED AVENTUS", description: "Masculinidad y proyección incomparable.", price: 250000.00, category: "Chypre Frutal" },
      { name: "ZAME SAUVAGE DIOR", description: "Notas frescas de bergamota de Calabria.", price: 195000.00, category: "Fougère" },
      { name: "ZAME BLEU DE CHANEL", description: "El clásico de la elegancia masculina.", price: 180000.00, category: "Amaderado" },
      { name: "ZAME BLACK OPIUM YSL", description: "Deliciosa combinación de café negro y vainilla.", price: 185000.00, category: "Gourmand" },
      { name: "ZAME GOOD GIRL CH", description: "La combinación de audacia y feminidad.", price: 190000.00, category: "Floral Oriental" }
    ]);
  }
  app.listen(PORT, () => console.log('Listening on port ' + PORT));
});
EOF

    # Frontend pages and scripts
    mkdir -p public
    # In a real environment, we'd write full index.html etc. Let's pull the files using AWS CLI from S3 or use a curl downloader to avoid massive user data size.
    # To keep it completely robust, we will write a streamlined single-page fallback or download ZIP from github/internet.
    # Since we also create the S3 bucket and upload the app-bundle to S3 in the guide, we assume S3 download is standard.
    # We write a basic landing index.html for emergency fallback.
    cat << 'EOF' > public/index.html
<!DOCTYPE html>
<html>
<head>
  <title>ZAME SCENT | Fallback</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white min-h-screen flex flex-col items-center justify-center">
  <h1 class="text-4xl font-serif text-amber-500 mb-4">ZAME SCENT</h1>
  <p class="text-zinc-400 mb-8">El bundle de la aplicación no se descargó de S3. Sube el app-bundle.zip al bucket S3 y reinicia el servicio.</p>
  <a href="/health" class="px-6 py-2 bg-amber-600 rounded text-xs font-bold uppercase">Verificar Health Check</a>
</body>
</html>
EOF
fi

# 5. Install Node production dependencies
echo "Installing application dependencies..."
npm install --omit=dev

# 6. Set up Environment variables
echo "Configuring environment variables..."
cat << EOF > .env
PORT=3000
JWT_SECRET=super-secret-key-zame-scent
DB_HOST=${rds_endpoint_host}
DB_PORT=3306
DB_NAME=${rds_db_name}
DB_USER=${rds_db_user}
DB_PASS=${rds_db_pass}
EPAYCO_MOCK=${epayco_mock}
EPAYCO_TEST_MODE=${epayco_test_mode}
EPAYCO_PUBLIC_KEY=${epayco_public_key}
EPAYCO_PRIVATE_KEY=${epayco_private_key}
EPAYCO_TEST_PRICE_DIVISOR=${epayco_test_price_divisor}
EPAYCO_TEST_MAX_AMOUNT=${epayco_test_max_amount}
EPAYCO_RESPONSE_URL=${epayco_response_url}
EPAYCO_CONFIRMATION_URL=${epayco_confirmation_url}
INSTANCE_ID=$(curl -s http://169.254.169.254/latest/meta-data/instance-id)
EOF

# 7. Configure systemd Service for Node Web Application
echo "Configuring systemd service..."
cat << EOF > /etc/systemd/system/zame-app.service
[Unit]
Description=Zame Scent Node.js E-Commerce Application
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/zame-app
ExecStart=/usr/bin/node server.js
Restart=on-failure
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
EOF

# 8. Start and Enable Node service
echo "Starting and enabling Zame Scent Application..."
systemctl daemon-reload
systemctl enable zame-app
systemctl start zame-app

# 9. Verify if running locally on port 3000
sleep 5
curl -I http://localhost:3000/health

echo "========================================================="
echo "✅ SERVIDOR WEB EC2 CONFIGURADO Y ACTIVO"
echo "========================================================="
