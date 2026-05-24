const { DataTypes } = require('sequelize');
const sequelize = require('../database');
const User = require('./User');
const Product = require('./Product');

const CartItem = sequelize.define('CartItem', {
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
  productId: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: Product,
      key: 'id'
    }
  },
  quantity: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 1
  }
});

CartItem.belongsTo(User, { foreignKey: 'userId' });
CartItem.belongsTo(Product, { foreignKey: 'productId' });
User.hasMany(CartItem, { foreignKey: 'userId', onDelete: 'CASCADE' });

module.exports = CartItem;
