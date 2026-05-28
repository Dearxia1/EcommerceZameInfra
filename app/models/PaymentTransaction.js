const { DataTypes } = require('sequelize');
const sequelize = require('../database');
const User = require('./User');
const Order = require('./Order');

const PaymentTransaction = sequelize.define('PaymentTransaction', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  orderId: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: Order,
      key: 'id'
    }
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: User,
      key: 'id'
    }
  },
  provider: {
    type: DataTypes.STRING,
    allowNull: false,
    defaultValue: 'epayco'
  },
  providerReference: {
    type: DataTypes.STRING,
    allowNull: true
  },
  status: {
    type: DataTypes.STRING,
    allowNull: false,
    defaultValue: 'PENDING'
  },
  amount: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  currency: {
    type: DataTypes.STRING,
    allowNull: false,
    defaultValue: 'COP'
  },
  responseText: {
    type: DataTypes.STRING,
    allowNull: true
  },
  rawResponse: {
    type: DataTypes.TEXT,
    allowNull: true
  }
});

PaymentTransaction.belongsTo(Order, { foreignKey: 'orderId' });
PaymentTransaction.belongsTo(User, { foreignKey: 'userId' });
Order.hasMany(PaymentTransaction, { foreignKey: 'orderId' });
User.hasMany(PaymentTransaction, { foreignKey: 'userId' });

module.exports = PaymentTransaction;
