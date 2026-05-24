const sequelize = require('./database');
const Product = require('./models/Product');

const seedProducts = [
  {
    name: "ZAME BACCARAT 540",
    description: "Inspiración del icónico perfume Baccarat Rouge 540 de Maison Francis Kurkdjian. Nota floral ambarada y amaderada intensa, fijación premium de más de 12 horas.",
    price: 290000.00,
    image: "/assets/images/baccarat.jpg",
    category: "Ambarado"
  },
  {
    name: "ZAME CREED AVENTUS",
    description: "Inspiración del rey de las fragancias masculinas, Creed Aventus. Notas frutales de piña, abedul y almizcle. Elegancia, masculinidad y proyección incomparables.",
    price: 250000.00,
    image: "/assets/images/creed.jpg",
    category: "Chypre Frutal"
  },
  {
    name: "ZAME SAUVAGE DIOR",
    description: "Inspiración de Sauvage de Dior. Notas frescas de bergamota de Calabria y pimienta de Sichuan. Ideal para el hombre versátil y magnético.",
    price: 195000.00,
    image: "/assets/images/sauvage.jpg",
    category: "Fougère"
  },
  {
    name: "ZAME BLEU DE CHANEL",
    description: "Inspiración de Bleu de Chanel. Un aroma amaderado aromático sofisticado con notas de pomelo, cedro y sándalo. El clásico de la elegancia masculina.",
    price: 180000.00,
    image: "/assets/images/bleu.jpg",
    category: "Amaderado"
  },
  {
    name: "ZAME BLACK OPIUM YSL",
    description: "Inspiración de Black Opium de Yves Saint Laurent. Deliciosa combinación de café negro, flores blancas y vainilla dulce. Seductora, adictiva y misteriosa.",
    price: 185000.00,
    image: "/assets/images/black_opium.jpg",
    category: "Gourmand"
  },
  {
    name: "ZAME GOOD GIRL CH",
    description: "Inspiración de Good Girl de Carolina Herrera. Notas de nardo sabroso, jazmín Sambac y haba tonka tostada. La combinación perfecta de audacia y feminidad.",
    price: 190000.00,
    image: "/assets/images/good_girl.jpg",
    category: "Floral Oriental"
  }
];

async function runSeed() {
  try {
    await sequelize.sync({ force: true });
    console.log("Database synchronized successfully.");
    
    await Product.bulkCreate(seedProducts);
    console.log("Database seeded successfully with premium product catalog!");
    process.exit(0);
  } catch (error) {
    console.error("Error seeding database:", error);
    process.exit(1);
  }
}

runSeed();
