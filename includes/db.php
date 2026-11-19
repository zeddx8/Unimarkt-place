<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Core Commercial Database & Utilities
// Platform: Universal Nationwide Marketplace (Ghana)
// ════════════════════════════════════════════════════════════

// Set Ghana Timezone (GMT / UTC+0)
date_default_timezone_set('Africa/Accra');

// Database credentials with Cloud & Environment variable fallbacks (Railway, Vercel, Docker, cPanel)
define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost'));
define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root'));
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : ''));
define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'unimarket'));
define('DB_PORT', (int)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306)));

// ── Database Connection ──────────────────────────────────────
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Auto-recovery / auto-creation if database 'unimarket' does not exist yet
if (!$conn || $conn->connect_error) {
    // Attempt connection without selecting DB to check if MySQL server is alive
    $root_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, "", DB_PORT);
    if ($root_conn && !$root_conn->connect_error) {
        $root_conn->set_charset("utf8mb4");
        $root_conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $root_conn->select_db(DB_NAME);

        // Auto-seed schema from unimarket.sql
        $sql_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'unimarket.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            $root_conn->multi_query($sql_content);
            while ($root_conn->more_results() && $root_conn->next_result()) { /* flush multi query */ }
        }
        $root_conn->close();

        // Reconnect to unimarket database
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
}

if (!$conn || $conn->connect_error) {
    die('<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required — UniMarket Ghana</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: "Plus Jakarta Sans", sans-serif; background: #0F172A; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .setup-card { background: #1E293B; border: 1.5px solid #EA580C; border-radius: 20px; max-width: 600px; width: 100%; padding: 36px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
            .brand-accent { color: #EA580C; }
            code { background: rgba(0,0,0,0.3); color: #FDBA74; padding: 2px 6px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="setup-card text-center">
            <div style="font-size: 48px; margin-bottom: 16px; color: #EA580C;"><i class="bi bi-database-exclamation"></i></div>
            <h2 class="fw-bold mb-2">Uni<span class="brand-accent">Market</span> Ghana</h2>
            <h5 class="text-warning mb-3">Database Connection Notice</h5>
            <p class="text-white-50">Could not connect to MySQL database <code>' . DB_NAME . '</code> on <code>' . DB_HOST . '</code>.</p>
            <div class="text-start bg-black bg-opacity-25 p-3 rounded-3 mb-4 font-monospace small">
                <strong>Error:</strong> ' . htmlspecialchars($conn ? $conn->connect_error : 'Connection failed') . '<br><br>
                <strong>Quick Fix:</strong><br>
                1. Make sure MySQL is running (via XAMPP, WAMP, Laragon, or Windows Service).<br>
                2. Run <code>php reset-db.php</code> or import <code>sql/unimarket.sql</code>.<br>
                3. Update credentials in <code>includes/db.php</code> if your MySQL has a password.
            </div>
            <a href="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php') . '" class="btn btn-primary px-4 py-2 fw-bold" style="background:#EA580C;border-color:#EA580C;">Retry Connection</a>
        </div>
    </body>
    </html>');
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+00:00'");

// Safe schema synchronization for existing databases
if ($conn && !$conn->connect_error) {
    // Check users table columns
    $u_cols = $conn->query("SHOW COLUMNS FROM users");
    if ($u_cols) {
        $cols = [];
        while ($c = $u_cols->fetch_assoc()) { $cols[] = $c['Field']; }
        if (!in_array('region', $cols)) {
            @$conn->query("ALTER TABLE users ADD COLUMN region VARCHAR(100) DEFAULT 'Greater Accra Region'");
        }
        if (!in_array('city', $cols)) {
            @$conn->query("ALTER TABLE users ADD COLUMN city VARCHAR(100) DEFAULT 'Accra'");
        }
        if (!in_array('address', $cols)) {
            @$conn->query("ALTER TABLE users ADD COLUMN address VARCHAR(200) DEFAULT 'Airport Residential, Accra'");
        }
    }

    // Check products table columns
    $p_cols = $conn->query("SHOW COLUMNS FROM products");
    if ($p_cols) {
        $pcols = [];
        while ($c = $p_cols->fetch_assoc()) { $pcols[] = $c['Field']; }
        if (!in_array('region', $pcols)) {
            @$conn->query("ALTER TABLE products ADD COLUMN region VARCHAR(100) DEFAULT 'Greater Accra Region'");
        }
        if (!in_array('rating', $pcols)) {
            @$conn->query("ALTER TABLE products ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.8");
        }
        if (!in_array('reviews_count', $pcols)) {
            @$conn->query("ALTER TABLE products ADD COLUMN reviews_count INT DEFAULT 18");
        }
    }

    // Ensure campus & student entrepreneurship categories exist
    $default_student_cats = [
        ['Fashion & Streetwear', 'fashion-streetwear', 'bi-bag-heart', 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&auto=format&fit=crop&q=80'],
        ['Tech, Gadgets & Accessories', 'tech-gadgets', 'bi-laptop', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&auto=format&fit=crop&q=80'],
        ['Beauty, Hair & Skincare', 'beauty-hair-skincare', 'bi-stars', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&auto=format&fit=crop&q=80'],
        ['Food, Pastries & Snacks', 'food-pastries-snacks', 'bi-cup-hot', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80'],
        ['Books, Stationery & Study Gear', 'books-stationery', 'bi-journal-bookmark', 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=600&auto=format&fit=crop&q=80'],
        ['Dorm & Hostel Essentials', 'dorm-hostel-essentials', 'bi-house-heart', 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&auto=format&fit=crop&q=80'],
        ['Footwear & Bags', 'footwear-bags', 'bi-handbag', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80'],
        ['Graphic Design & Digital Services', 'graphic-digital-services', 'bi-palette', 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&auto=format&fit=crop&q=80'],
        ['Photography & Event Services', 'photography-event-services', 'bi-camera', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&auto=format&fit=crop&q=80'],
        ['Fitness & Sports Gear', 'fitness-sports-gear', 'bi-trophy', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=600&auto=format&fit=crop&q=80'],
        ['Art, Crafts & Custom Merch', 'art-crafts-custom-merch', 'bi-brush', 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80']
    ];
    $cat_count = $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0] ?? 0;
    if ($cat_count < count($default_student_cats)) {
        foreach ($default_student_cats as $dc) {
            $check = $conn->query("SELECT id FROM categories WHERE name = '" . $conn->real_escape_string($dc[0]) . "'");
            if (!$check || $check->num_rows === 0) {
                $cstmt = $conn->prepare("INSERT INTO categories (name, slug, icon, image) VALUES (?, ?, ?, ?)");
                if ($cstmt) {
                    $cstmt->bind_param("ssss", $dc[0], $dc[1], $dc[2], $dc[3]);
                    $cstmt->execute();
                }
            }
        }
    }
}

// ── Session Initialization ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── Authentication & Access Control Helpers ───────────────────
function isLoggedIn() {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn() || empty($_SESSION['is_admin'])) {
        header('Location: ../admin/login.php');
        exit();
    }
}

// ── Ghanaian Currency & Regional Helpers ─────────────────────

/**
 * Formats a monetary amount into Ghana Cedis (GH₵) with 2 decimal places and comma separators.
 * Example: 450 -> "GH₵ 450.00", 1250.5 -> "GH₵ 1,250.50"
 */
function formatPrice($price) {
    $amount = is_numeric($price) ? (float)$price : 0.00;
    return 'GH₵ ' . number_format($amount, 2, '.', ',');
}

function formatGHS($price) {
    return formatPrice($price);
}

/**
 * Returns nationwide regions across Ghana for delivery and location targeting.
 */
function getGhanaRegions() {
    return [
        'Greater Accra Region',
        'Ashanti Region (Kumasi)',
        'Western Region (Takoradi)',
        'Central Region (Cape Coast)',
        'Eastern Region (Koforidua)',
        'Volta Region (Ho)',
        'Northern Region (Tamale)',
        'Bono Region (Sunyani)',
        'Upper East Region (Bolgatanga)',
        'Upper West Region (Wa)',
        'Oti Region',
        'Ahafo Region',
        'Bono East Region',
        'North East Region',
        'Savannah Region',
        'Western North Region'
    ];
}

/**
 * Returns Ghanaian universities, technical universities, colleges and campuses for student entrepreneurship.
 */
function getGhanaCampuses() {
    return [
        'University of Ghana (UG - Legon)',
        'Kwame Nkrumah Univ of Science & Tech (KNUST - Kumasi)',
        'University of Cape Coast (UCC - Cape Coast)',
        'University of Professional Studies, Accra (UPSA)',
        'Ashesi University (Berekuso)',
        'Accra Technical University (ATU)',
        'Kumasi Technical University (KsTU)',
        'Cape Coast Technical University (CCTU)',
        'Ghana Communication Technology Univ (GCTU)',
        'GIMPA (Greenhill, Achimota)',
        'University of Education, Winneba (UEW)',
        'University of Mines and Technology (UMaT - Tarkwa)',
        'Academic City University College',
        'Pentecost University (Sowutuom)',
        'Central University (Miotso / Dawhenya)',
        'Ho Technical University (HTU)',
        'Tamale Technical University (TaTU)',
        'Greater Accra (Non-Campus / General)',
        'Ashanti Region (Non-Campus / General)',
        'Western Region (Non-Campus / General)',
        'Other Ghanaian Campus / Region'
    ];
}

/**
 * Formats Ghanaian phone numbers to standard format (+233 XX XXX XXXX).
 */
function formatGhanaPhone($phone) {
    $clean = preg_replace('/[^0-9]/', '', (string)$phone);
    if (strpos($clean, '233') === 0 && strlen($clean) === 12) {
        return '+233 ' . substr($clean, 3, 2) . ' ' . substr($clean, 5, 3) . ' ' . substr($clean, 8);
    }
    if (strpos($clean, '0') === 0 && strlen($clean) === 10) {
        return '+233 ' . substr($clean, 1, 2) . ' ' . substr($clean, 3, 3) . ' ' . substr($clean, 6);
    }
    return $phone;
}

// ── General Utilities ────────────────────────────────────────
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

function timeAgo($datetime) {
    $timezone = new DateTimeZone('Africa/Accra');
    try {
        $now = new DateTime('now', $timezone);
        $ago = new DateTime($datetime, $timezone);
        $diff = $now->diff($ago);

        if ($diff->y > 0) return $diff->y . 'y ago';
        if ($diff->m > 0) return $diff->m . 'mo ago';
        if ($diff->d >= 7) return floor($diff->d / 7) . 'w ago';
        if ($diff->d > 0) return $diff->d . 'd ago';
        if ($diff->h > 0) return $diff->h . 'h ago';
        if ($diff->i > 0) return $diff->i . 'm ago';
        return 'Just now';
    } catch (Exception $e) {
        return 'Recently';
    }
}

function getAvatar($name) {
    $parts = explode(' ', trim((string)$name));
    $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    if (isset($parts[1]) && strlen($parts[1]) > 0) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }
    return $initials;
}

// ── Chat Permissions Helper ──────────────────────────────────
function canAccessProductChat($conn, $product_id, $user_id, $other_user_id, $is_admin) {
    if ($product_id < 1 || $user_id < 1 || $other_user_id < 1 || $user_id === $other_user_id) {
        return false;
    }

    $product_stmt = $conn->prepare("SELECT seller_id FROM products WHERE id=?");
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product = $product_stmt->get_result()->fetch_assoc();
    if (!$product) {
        return false;
    }

    $other_stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
    $other_stmt->bind_param("i", $other_user_id);
    $other_stmt->execute();
    $other_user = $other_stmt->get_result()->fetch_assoc();
    if (!$other_user) {
        return false;
    }

    if ($is_admin) {
        if ($other_user['is_admin']) {
            return false;
        }

        $history_stmt = $conn->prepare("
            SELECT id FROM messages
            WHERE product_id=? AND (sender_id=? OR receiver_id=?)
            LIMIT 1
        ");
        $history_stmt->bind_param("iii", $product_id, $other_user_id, $other_user_id);
        $history_stmt->execute();
        return (int) $product['seller_id'] === $other_user_id
            || $history_stmt->get_result()->num_rows > 0;
    }

    if ($other_user['is_admin']) {
        $history_stmt = $conn->prepare("
            SELECT id FROM messages
            WHERE product_id=? AND
                ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))
            LIMIT 1
        ");
        $history_stmt->bind_param("iiiii", $product_id, $user_id, $other_user_id, $other_user_id, $user_id);
        $history_stmt->execute();
        return $history_stmt->get_result()->num_rows > 0;
    }

    return (int) $product['seller_id'] === $user_id
        || (int) $product['seller_id'] === $other_user_id;
}

// ── File Upload Helper ───────────────────────────────────────
function storeImageUpload($file, $prefix, $max_bytes) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'The image exceeds the upload size limit.'
            : 'The image could not be uploaded. Please try again.';
        return ['filename' => null, 'error' => $error];
    }

    $temporary_path = $file['tmp_name'] ?? '';
    $file_size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($file_size < 1 || $file_size > $max_bytes) {
        return ['filename' => null, 'error' => 'The image exceeds the maximum permitted size limit.'];
    }

    if (!$temporary_path || !is_uploaded_file($temporary_path)) {
        return ['filename' => null, 'error' => 'The uploaded file is not a valid image.'];
    }

    $image_info = getimagesize($temporary_path);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/bmp'  => 'bmp',
        'image/x-ms-bmp' => 'bmp',
        'image/avif' => 'avif',
    ];
    $mime_type = $image_info['mime'] ?? '';
    if (!$mime_type && class_exists('finfo')) {
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $detected_type = $file_info->file($temporary_path);
        if ($detected_type === 'image/avif') {
            $mime_type = $detected_type;
        }
    }
    if ((!$image_info && $mime_type !== 'image/avif') || !isset($extensions[$mime_type])) {
        return ['filename' => null, 'error' => 'Please upload a valid image: JPG, PNG, GIF, WEBP, BMP, or AVIF.'];
    }

    $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        return ['filename' => null, 'error' => 'The uploads directory could not be prepared.'];
    }

    $filename = $prefix . bin2hex(random_bytes(16)) . '.' . $extensions[$mime_type];
    if (!move_uploaded_file($temporary_path, $upload_dir . DIRECTORY_SEPARATOR . $filename)) {
        return ['filename' => null, 'error' => 'The image could not be stored. Please ensure the uploads folder is writable.'];
    }

    return ['filename' => $filename, 'error' => null];
}
