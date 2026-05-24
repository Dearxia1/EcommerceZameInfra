const { DataTypes } = require('sequelize');
const sequelize = require('../database');
const User = require('./User');

const Order = sequelize.define('Order', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: User,
      key: 'id'
    }
  },
  total: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  status: {
    type: DataTypes.STRING,
    defaultValue: 'COMPLETED'
  },
  billingName: {
    type: DataTypes.STRING,
    allowNull: false
  },
  billingAddress: {
    type: DataTypes.STRING,
    allowNull: false
  },
  billingCity: {
    type: DataTypes.STRING,
    allowNull: false
  },
  items: {
    type: DataTypes.TEXT, // Store JSON serialized list of products as text
    allowNull: false
  }
});

Order.belongsTo(User, { foreignKey: 'userId' });
User.hasMany(Order, { foreignKey: 'userId' });

module.exports = Order;
