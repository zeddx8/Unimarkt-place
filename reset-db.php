<?php
// ============================================================
// UniMarket Ghana — Database Reset & Clean Commercial Seeder
// Super-Admin: admin@unimarket.com.gh / password
// Currency: Ghana Cedis (GH₵ / GHS)
// ZERO Emojis — Professional Bootstrap Icons & Curated Unsplash Photos
// ============================================================

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This maintenance script can only be executed from the CLI.');
}

require_once __DIR__ . '/includes/db.php';

// Disable reporting exceptions for manual handling
mysqli_report(MYSQLI_REPORT_OFF);

echo "[UniMarket Reset] Starting clean database migration and reset...\n";

// Ensure connection exists
if (!$conn || $conn->connect_error) {
    die("[ERROR] Could not connect to MySQL server. Please verify MySQL service is running.\n");
}

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop existing tables cleanly
$tables = ['order_items', 'orders', 'wishlist', 'reports', 'messages', 'products', 'categories', 'users'];
foreach ($tables as $tbl) {
    $conn->query("DROP TABLE IF EXISTS `$tbl`");
    echo "  - Dropped table: $tbl\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 1. Create USERS table
$conn->query("
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: users\n";

// 2. Create CATEGORIES table (icon column stores Bootstrap Icon class e.g. bi-phone)
$conn->query("
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE,
    icon VARCHAR(60) NOT NULL,
    image VARCHAR(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: categories\n";

// 3. Create PRODUCTS table
$conn->query("
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: products\n";

// 4. Create MESSAGES table
$conn->query("
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: messages\n";

// 5. Create REPORTS table
$conn->query("
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: reports\n";

// 6. Create WISHLIST table
$conn->query("
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: wishlist\n";

// 7. Create ORDERS table
$conn->query("
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: orders\n";

// 8. Create ORDER_ITEMS table
$conn->query("
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  + Created table: order_items\n";

// ── SEED USERS (Admin Only) ─────────────────────────────────
$admin_pw = password_hash('password', PASSWORD_DEFAULT);

$stmt_user = $conn->prepare("
INSERT INTO users (id, full_name, email, password, phone, region, city, address, avatar, is_verified, is_admin)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$users_seed = [
    [1, 'UniMarket Super Administrator', 'admin@unimarket.com.gh', $admin_pw, '+233 30 200 0001', 'Greater Accra Region', 'Accra Central', 'High Street Commercial Plaza, Suite 400', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80', 1, 1],
];

foreach ($users_seed as $u) {
    $stmt_user->bind_param("issssssssii", $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $u[6], $u[7], $u[8], $u[9], $u[10]);
    $stmt_user->execute();
}
echo "  + Seeded Super Admin: admin@unimarket.com.gh (password: password)\n";

// ── SEED CATEGORIES (Student Entrepreneurship & Campus Commerce) ──
$categories_seed = [
    [1, 'Fashion & Streetwear', 'fashion-streetwear', 'bi-bag-heart', 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&auto=format&fit=crop&q=80'],
    [2, 'Tech, Gadgets & Accessories', 'tech-gadgets', 'bi-laptop', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&auto=format&fit=crop&q=80'],
    [3, 'Beauty, Hair & Skincare', 'beauty-hair-skincare', 'bi-stars', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&auto=format&fit=crop&q=80'],
    [4, 'Food, Pastries & Snacks', 'food-pastries-snacks', 'bi-cup-hot', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80'],
    [5, 'Books, Stationery & Study Gear', 'books-stationery', 'bi-journal-bookmark', 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=600&auto=format&fit=crop&q=80'],
    [6, 'Dorm & Hostel Essentials', 'dorm-hostel-essentials', 'bi-house-heart', 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&auto=format&fit=crop&q=80'],
    [7, 'Footwear & Bags', 'footwear-bags', 'bi-handbag', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80'],
    [8, 'Graphic Design & Digital Services', 'graphic-digital-services', 'bi-palette', 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&auto=format&fit=crop&q=80'],
    [9, 'Photography & Event Services', 'photography-event-services', 'bi-camera', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&auto=format&fit=crop&q=80'],
    [10, 'Fitness & Sports Gear', 'fitness-sports-gear', 'bi-trophy', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=600&auto=format&fit=crop&q=80'],
    [11, 'Art, Crafts & Custom Merch', 'art-crafts-custom-merch', 'bi-brush', 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80']
];

$stmt_cat = $conn->prepare("INSERT INTO categories (id, name, slug, icon, image) VALUES (?, ?, ?, ?, ?)");
foreach ($categories_seed as $c) {
    $stmt_cat->bind_param("issss", $c[0], $c[1], $c[2], $c[3], $c[4]);
    $stmt_cat->execute();
}
echo "  + Seeded 11 commercial categories with vector icons\n";

echo "============================================================\n";
echo "SUCCESS: Database 'unimarket' has been cleared and reseeded!\n";
echo "Super Admin Credentials:\n";
echo "  Email:    admin@unimarket.com.gh\n";
echo "  Password: password\n";
echo "============================================================\n";
