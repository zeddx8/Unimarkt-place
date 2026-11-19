-- ============================================================
-- UniMarket Ghana — Commercial Nationwide Marketplace Database
-- Platform: Universal Commercial Retail for Ghana
-- Coverage: Nationwide (Accra, Kumasi, Takoradi, Cape Coast, etc.)
-- Currency: Ghana Cedis (GH₵ / GHS)
-- Zero Emojis — Vector Icons & High-Res Commercial Imagery
-- ============================================================

CREATE DATABASE IF NOT EXISTS unimarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unimarket;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ── 1. USERS TABLE ──────────────────────────────────────────
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    region VARCHAR(100) DEFAULT 'Greater Accra Region',
    city VARCHAR(100) DEFAULT 'Accra',
    address VARCHAR(200) DEFAULT 'Airport Residential, Accra',
    avatar VARCHAR(500) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. CATEGORIES TABLE ─────────────────────────────────────
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE,
    icon VARCHAR(60) NOT NULL,
    image VARCHAR(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. PRODUCTS TABLE ───────────────────────────────────────
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    condition_type ENUM('New','Like New','Good','Refurbished') NOT NULL DEFAULT 'New',
    region VARCHAR(100) DEFAULT 'Greater Accra Region',
    rating DECIMAL(2,1) DEFAULT 4.8,
    reviews_count INT DEFAULT 18,
    image VARCHAR(500) DEFAULT NULL,
    status ENUM('available','sold','removed') DEFAULT 'available',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. MESSAGES TABLE ───────────────────────────────────────
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. REPORTS TABLE ────────────────────────────────────────
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    product_id INT NOT NULL,
    reason VARCHAR(200) NOT NULL,
    details TEXT,
    status ENUM('pending','reviewed','resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. WISHLIST TABLE ───────────────────────────────────────
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. ORDERS / CHECKOUT TABLE ──────────────────────────────
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    payment_method ENUM('mtn_momo','telecel_cash','at_money','card','cash_on_delivery') NOT NULL,
    momo_number VARCHAR(30) DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. ORDER ITEMS TABLE ────────────────────────────────────
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SEED USERS ──────────────────────────────────────────────
-- Super-Admin: admin@unimarket.com.gh / password
INSERT INTO users (id, full_name, email, password, phone, region, city, address, avatar, is_verified, is_admin) VALUES
(1, 'UniMarket Super Administrator', 'admin@unimarket.com.gh', '$2y$10$VjQIOvHiL0.E07eb3WKX7eTEdlgaj2whCSbqfKNEygWpuHqqWhF1G', '+233 30 200 0001', 'Greater Accra Region', 'Accra Central', 'High Street Commercial Plaza, Suite 400', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80', 1, 1),
(2, 'Kwame Mensah', 'kwame.mensah@unimarket.com.gh', '$2y$10$VjQIOvHiL0.E07eb3WKX7eTEdlgaj2whCSbqfKNEygWpuHqqWhF1G', '+233 24 456 7890', 'Greater Accra Region', 'East Legon', 'Boundary Road, East Legon, Accra', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80', 1, 0),
(3, 'Abena Osei', 'abena.osei@unimarket.com.gh', '$2y$10$VjQIOvHiL0.E07eb3WKX7eTEdlgaj2whCSbqfKNEygWpuHqqWhF1G', '+233 55 123 9876', 'Ashanti Region (Kumasi)', 'Adum', 'Harper Road, Commercial Hub, Adum, Kumasi', 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=200&auto=format&fit=crop&q=80', 1, 0),
(4, 'Yaw Boateng', 'yaw.boateng@unimarket.com.gh', '$2y$10$VjQIOvHiL0.E07eb3WKX7eTEdlgaj2whCSbqfKNEygWpuHqqWhF1G', '+233 20 889 0012', 'Western Region (Takoradi)', 'Takoradi', 'Market Circle Commercial Drive, Takoradi', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&auto=format&fit=crop&q=80', 1, 0);

-- ── SEED CATEGORIES (Student Entrepreneurship & Campus Commerce) ──
INSERT INTO categories (id, name, slug, icon, image) VALUES
(1, 'Fashion & Streetwear', 'fashion-streetwear', 'bi-bag-heart', 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&auto=format&fit=crop&q=80'),
(2, 'Tech, Gadgets & Accessories', 'tech-gadgets', 'bi-laptop', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&auto=format&fit=crop&q=80'),
(3, 'Beauty, Hair & Skincare', 'beauty-hair-skincare', 'bi-stars', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&auto=format&fit=crop&q=80'),
(4, 'Food, Pastries & Snacks', 'food-pastries-snacks', 'bi-cup-hot', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80'),
(5, 'Books, Stationery & Study Gear', 'books-stationery', 'bi-journal-bookmark', 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=600&auto=format&fit=crop&q=80'),
(6, 'Dorm & Hostel Essentials', 'dorm-hostel-essentials', 'bi-house-heart', 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&auto=format&fit=crop&q=80'),
(7, 'Footwear & Bags', 'footwear-bags', 'bi-handbag', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80'),
(8, 'Graphic Design & Digital Services', 'graphic-digital-services', 'bi-palette', 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&auto=format&fit=crop&q=80'),
(9, 'Photography & Event Services', 'photography-event-services', 'bi-camera', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&auto=format&fit=crop&q=80'),
(10, 'Fitness & Sports Gear', 'fitness-sports-gear', 'bi-trophy', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=600&auto=format&fit=crop&q=80'),
(11, 'Art, Crafts & Custom Merch', 'art-crafts-custom-merch', 'bi-brush', 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80');

-- ── SEED COMMERCIAL PRODUCTS ────────────────────────────────
INSERT INTO products (id, seller_id, category_id, title, description, price, condition_type, region, rating, reviews_count, image, status, views) VALUES
(1, 2, 3, 'Samsung 55" Crystal UHD 4K Smart TV with HDR & Gaming Hub', 'Brand new in original retail packaging. Features Crystal Processor 4K, HDR10+, Smart Hub with streaming applications, voice control remote, and 3 HDMI ports. Includes 1-year manufacturer warranty with nationwide delivery.', 6850.00, 'New', 'Greater Accra Region', 4.9, 32, 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=800&auto=format&fit=crop&q=80', 'available', 154),
(2, 3, 1, 'Apple iPhone 15 Pro Max 256GB Natural Titanium (Unlocked)', 'Factory unlocked and pristine. Powered by the A17 Pro chip, 5x telephoto optical zoom camera system, Action button, and titanium aerospace-grade frame. USB-C charging cable and original packaging included.', 14200.00, 'Like New', 'Ashanti Region (Kumasi)', 5.0, 48, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&auto=format&fit=crop&q=80', 'available', 290),
(3, 2, 2, 'Dell XPS 13 9315 Ultrabook (Intel Core i7, 16GB RAM, 512GB SSD)', 'Ultra-thin, premium carbon fiber and aluminum build. 12th Gen Intel Core i7, 16GB LPDDR5 RAM, 512GB PCIe NVMe SSD, 13.4" FHD+ InfinityEdge display, backlit keyboard, and fingerprint reader. Includes 45W Type-C adapter.', 9500.00, 'New', 'Greater Accra Region', 4.8, 27, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800&auto=format&fit=crop&q=80', 'available', 110),
(4, 4, 4, 'Bruhm 200L High-Efficiency Energy Star Chest Freezer', 'Direct-cool 200-litre chest freezer designed for heavy-duty household and commercial storage. Fast-freezing function, low noise compressor, safety lock with keys, and interior LED lighting. Operates smoothly under local voltage conditions.', 3950.00, 'New', 'Western Region (Takoradi)', 4.7, 19, 'https://images.unsplash.com/photo-1584992236310-6edddc08acff?w=800&auto=format&fit=crop&q=80', 'available', 78),
(5, 3, 5, 'Nike Air Jordan 1 Retro High OG - Black & White Edition', 'Authentic high-top basketball sneakers. Premium leather construction, encapsulated Nike Air cushioning, perforated toe box, and durable rubber outsole. Brand new in box with extra white laces.', 850.00, 'New', 'Ashanti Region (Kumasi)', 4.9, 41, 'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=800&auto=format&fit=crop&q=80', 'available', 125),
(6, 2, 4, 'Philips ProBlend 600W Glass Blender with Multi-Mill Chopper', 'High-powered kitchen blender equipped with 6-star ProBlend serrated blades, 2-litre thermal shock-resistant glass jug, and variable speed control with pulse. Excellent for smoothies, soups, and crushing ice.', 420.00, 'New', 'Greater Accra Region', 4.8, 56, 'https://images.unsplash.com/photo-1570222094114-d054a817e56b?w=800&auto=format&fit=crop&q=80', 'available', 95),
(7, 4, 3, 'JBL Charge 5 Waterproof Portable Bluetooth Speaker', 'Original JBL Charge 5 speaker featuring an optimized long excursion driver, separate tweeter, and dual JBL bass radiators. Delivers up to 20 hours of playtime and built-in power bank. IP67 waterproof and dustproof.', 1650.00, 'New', 'Western Region (Takoradi)', 4.9, 34, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&auto=format&fit=crop&q=80', 'available', 130),
(8, 3, 6, 'CeraVe Hydrating Facial Cleanser with Ceramides 473ml', 'Dermatologist-developed gentle moisturizing face and body wash for normal to dry skin. Formulated with three essential ceramides (1, 3, 6-II) and MVE Delivery Technology for all-day continuous hydration.', 210.00, 'New', 'Ashanti Region (Kumasi)', 4.9, 73, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800&auto=format&fit=crop&q=80', 'available', 175),
(9, 2, 7, 'Nestle Nido Fortigrow Instant Dry Whole Milk Powder 2.5kg Tin', 'Nutrient-rich full cream powdered milk fortified with iron, zinc, calcium, vitamin C, and vitamin D. Sealed factory-fresh metal tin with plastic scoop. Essential everyday grocery staple for households.', 320.00, 'New', 'Greater Accra Region', 4.8, 62, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=800&auto=format&fit=crop&q=80', 'available', 98),
(10, 4, 5, 'Handcrafted Mens Genuine Leather Derby Oxford Shoes (Size 42-44)', 'Premium hand-stitched full-grain Italian leather shoes with cushioned insole and non-slip Goodyear welted leather sole. Suitable for weddings, corporate meetings, and upscale occasions.', 580.00, 'New', 'Western Region (Takoradi)', 4.7, 15, 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=800&auto=format&fit=crop&q=80', 'available', 62),
(11, 2, 4, 'Midea 1.5HP Inverter Split Air Conditioner with R32 Eco Gas', 'High-efficiency energy-saving split A/C with rapid cooling technology, golden fin anti-corrosive condenser, hidden temperature display, and multi-stage air purification filter. Runs whisper quiet.', 4800.00, 'New', 'Greater Accra Region', 4.8, 23, 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=800&auto=format&fit=crop&q=80', 'available', 84),
(12, 3, 8, 'Garmin Forerunner 265 GPS Running & Fitness Smartwatch', 'Vibrant AMOLED touchscreen display, training readiness score, morning report, wrist-based running dynamics, multi-band GNSS satellite positioning, and up to 13 days of battery life in smartwatch mode.', 4250.00, 'New', 'Ashanti Region (Kumasi)', 5.0, 19, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80', 'available', 92);

-- ── SEED SAMPLE ORDERS & ORDER ITEMS ────────────────────────
INSERT INTO orders (id, order_code, user_id, customer_name, region, city, delivery_address, phone, payment_method, momo_number, subtotal, delivery_fee, discount, total, status) VALUES
(1, 'ORD-2026-9081', 2, 'Kwame Mensah', 'Greater Accra Region', 'East Legon', 'Boundary Road, East Legon, Accra', '+233 24 456 7890', 'mtn_momo', '0244567890', 6850.00, 35.00, 0.00, 6885.00, 'delivered'),
(2, 'ORD-2026-9082', 3, 'Abena Osei', 'Ashanti Region (Kumasi)', 'Adum', 'Harper Road, Adum, Kumasi', '+233 55 123 9876', 'telecel_cash', '0551239876', 1700.00, 25.00, 0.00, 1725.00, 'shipped'),
(3, 'ORD-2026-9083', 4, 'Yaw Boateng', 'Western Region (Takoradi)', 'Takoradi', 'Market Circle Area, Takoradi', '+233 20 889 0012', 'card', NULL, 1650.00, 20.00, 0.00, 1670.00, 'processing'),
(4, 'ORD-2026-9084', 2, 'Kwame Mensah', 'Greater Accra Region', 'Osu', '14 Cantonments Road, Osu, Accra', '+233 24 456 7890', 'mtn_momo', '0244567890', 530.00, 15.00, 0.00, 545.00, 'pending');

INSERT INTO order_items (order_id, product_id, title, price, quantity) VALUES
(1, 1, 'Samsung 55" Crystal UHD 4K Smart TV', 6850.00, 1),
(2, 5, 'Nike Air Jordan 1 Retro High OG', 850.00, 2),
(3, 7, 'JBL Charge 5 Waterproof Portable Bluetooth Speaker', 1650.00, 1),
(4, 8, 'CeraVe Hydrating Facial Cleanser with Ceramides', 210.00, 1),
(4, 9, 'Nestle Nido Fortigrow Instant Dry Whole Milk Powder', 320.00, 1);
