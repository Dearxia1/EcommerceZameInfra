const { Sequelize } = require('sequelize');
require('dotenv').config();

let sequelize;

if (process.env.DB_HOST) {
  // RDS MySQL connection
  sequelize = new Sequelize(
    process.env.DB_NAME || 'zame_db',
    process.env.DB_USER || 'admin',
    process.env.DB_PASS || 'password123',
    {
      host: process.env.DB_HOST,
      dialect: 'mysql',
      port: process.env.DB_PORT || 3306,
      logging: false,
      pool: {
        max: 5,
        min: 0,
        acquire: 30000,
        idle: 10000
      }
    }
  );
} else {
  // Local SQLite fallback
  sequelize = new Sequelize({
    dialect: 'sqlite',
    storage: './database.sqlite',
    logging: false
  });
}

module.exports = sequelize;
