<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Core Commercial Database & Utilities
// Platform: Universal Nationwide Marketplace (Ghana)
// ════════════════════════════════════════════════════════════

// Set Ghana Timezone (GMT / UTC+0)
date_default_timezone_set('Africa/Accra');

// Database credentials with Cloud & Environment variable fallbacks (Railway, Vercel, Docker, cPanel)
$db_url = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: '');
if (!empty($db_url) && (strpos($db_url, 'mysql://') === 0 || strpos($db_url, 'mysqli://') === 0)) {
    $parsed_db = parse_url($db_url);
    $default_host = $parsed_db['host'] ?? 'localhost';
    $default_user = $parsed_db['user'] ?? 'root';
    $default_pass = $parsed_db['pass'] ?? '';
    $default_name = ltrim($parsed_db['path'] ?? '', '/');
    $default_port = (int)($parsed_db['port'] ?? 3306);
} else {
    $default_host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
    // If user mistakenly configured their own public web app domain as DB_HOST, fall back to Railway internal DNS
    if (strpos($default_host, '.railway.app') !== false) {
        $default_host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
    }
    $default_user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
    $default_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');
    $default_name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'unimarket');
    $default_port = (int)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306));
}

define('DB_HOST', $default_host);
define('DB_USER', $default_user);
define('DB_PASS', $default_pass);
define('DB_NAME', $default_name);
define('DB_PORT', $default_port);

// ── Database Connection ──────────────────────────────────────
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Auto-recovery / auto-creation of empty database if it does not exist yet (auto-seeding disabled)
if (!$conn || $conn->connect_error) {
    $root_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, "", DB_PORT);
    if ($root_conn && !$root_conn->connect_error) {
        $root_conn->set_charset("utf8mb4");
        $root_conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $root_conn->close();

        // Reconnect to database (no auto-seeding)
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    }
}

if (!$conn || $conn->connect_error) {
    die('<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <title>Database Setup Required — UniMarket Ghana</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: "Plus Jakarta Sans", sans-serif; background: #0F172A; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .setup-card { background: #1E293B; border: 1.5px solid #2563EB; border-radius: 20px; max-width: 600px; width: 100%; padding: 36px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
            .brand-accent { color: #2563EB; }
            code { background: rgba(0,0,0,0.3); color: #93C5FD; padding: 2px 6px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="setup-card text-center">
            <div style="font-size: 48px; margin-bottom: 16px; color: #2563EB;"><i class="bi bi-database-exclamation"></i></div>
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
            <a href="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php') . '" class="btn btn-primary px-4 py-2 fw-bold" style="background:#2563EB;border-color:#2563EB;">Retry Connection</a>
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
        @$conn->query("ALTER TABLE users MODIFY COLUMN avatar MEDIUMTEXT DEFAULT NULL");
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
        @$conn->query("ALTER TABLE products MODIFY COLUMN image MEDIUMTEXT DEFAULT NULL");
    }

    // Check orders table columns
    $o_cols = $conn->query("SHOW COLUMNS FROM orders");
    if ($o_cols) {
        $ocols = [];
        while ($c = $o_cols->fetch_assoc()) { $ocols[] = $c['Field']; }
        if (!in_array('delivery_tier', $ocols)) {
            @$conn->query("ALTER TABLE orders ADD COLUMN delivery_tier VARCHAR(120) DEFAULT NULL AFTER delivery_fee");
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

function enforceActiveUser($redirectPath = 'index.php') {
    global $conn;
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid < 1 || !$conn) {
        return false;
    }
    $stmt = $conn->prepare("SELECT is_banned, is_admin FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return true;
    }
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !empty($row['is_banned'])) {
        session_unset();
        session_destroy();
        header('Location: ' . $redirectPath . '?banned=1');
        exit();
    }
    $_SESSION['is_admin'] = (int)($row['is_admin'] ?? 0);
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
    enforceActiveUser('index.php');
}

function requireAdmin() {
    if (!isLoggedIn() || empty($_SESSION['is_admin'])) {
        header('Location: ../admin/login.php');
        exit();
    }
    enforceActiveUser('../index.php');
    if (empty($_SESSION['is_admin'])) {
        header('Location: ../admin/login.php');
        exit();
    }
}

// ── CSRF Protection Helpers ──────────────────────────────────
function getCsrfToken() {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken($token) {
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf($redirectUrl = null) {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCsrfToken($token)) {
        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit();
        }
        http_response_code(403);
        exit('Invalid or expired security token. Please refresh and try again.');
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
 * Returns coordinates and canonical Ghanaian region mapping for campuses and regions.
 */
function getGhanaLocationDirectory() {
    return [
        // Universities & Campuses
        'University of Ghana (UG - Legon)'                       => ['region' => 'Greater Accra Region',    'lat' => 5.6506,  'lng' => -0.1870, 'is_campus' => true],
        'Kwame Nkrumah Univ of Science & Tech (KNUST - Kumasi)'  => ['region' => 'Ashanti Region (Kumasi)', 'lat' => 6.6745,  'lng' => -1.5716, 'is_campus' => true],
        'University of Cape Coast (UCC - Cape Coast)'            => ['region' => 'Central Region (Cape Coast)', 'lat' => 5.1036, 'lng' => -1.2825, 'is_campus' => true],
        'University of Professional Studies, Accra (UPSA)'       => ['region' => 'Greater Accra Region',    'lat' => 5.6626,  'lng' => -0.1665, 'is_campus' => true],
        'Ashesi University (Berekuso)'                           => ['region' => 'Eastern Region (Koforidua)', 'lat' => 5.7597, 'lng' => -0.2199, 'is_campus' => true],
        'Accra Technical University (ATU)'                       => ['region' => 'Greater Accra Region',    'lat' => 5.5545,  'lng' => -0.2072, 'is_campus' => true],
        'Kumasi Technical University (KsTU)'                     => ['region' => 'Ashanti Region (Kumasi)', 'lat' => 6.6915,  'lng' => -1.6152, 'is_campus' => true],
        'Cape Coast Technical University (CCTU)'                 => ['region' => 'Central Region (Cape Coast)', 'lat' => 5.1310, 'lng' => -1.2891, 'is_campus' => true],
        'Ghana Communication Technology Univ (GCTU)'             => ['region' => 'Greater Accra Region',    'lat' => 5.5960,  'lng' => -0.2233, 'is_campus' => true],
        'GIMPA (Greenhill, Achimota)'                            => ['region' => 'Greater Accra Region',    'lat' => 5.6297,  'lng' => -0.2089, 'is_campus' => true],
        'University of Education, Winneba (UEW)'                 => ['region' => 'Central Region (Cape Coast)', 'lat' => 5.3396, 'lng' => -0.6254, 'is_campus' => true],
        'University of Mines and Technology (UMaT - Tarkwa)'     => ['region' => 'Western Region (Takoradi)', 'lat' => 5.2989, 'lng' => -1.9990, 'is_campus' => true],
        'Academic City University College'                       => ['region' => 'Greater Accra Region',    'lat' => 5.7069,  'lng' => -0.1753, 'is_campus' => true],
        'Pentecost University (Sowutuom)'                        => ['region' => 'Greater Accra Region',    'lat' => 5.6203,  'lng' => -0.2738, 'is_campus' => true],
        'Central University (Miotso / Dawhenya)'                 => ['region' => 'Greater Accra Region',    'lat' => 5.7647,  'lng' => 0.0528,  'is_campus' => true],
        'Ho Technical University (HTU)'                          => ['region' => 'Volta Region (Ho)',       'lat' => 6.5979,  'lng' => 0.4648,  'is_campus' => true],
        'Tamale Technical University (TaTU)'                     => ['region' => 'Northern Region (Tamale)', 'lat' => 9.4330, 'lng' => -0.8400, 'is_campus' => true],

        // Non-Campus & Regional Hubs
        'Greater Accra (Non-Campus / General)'                   => ['region' => 'Greater Accra Region',      'lat' => 5.6037,  'lng' => -0.1870, 'is_campus' => false],
        'Ashanti Region (Non-Campus / General)'                  => ['region' => 'Ashanti Region (Kumasi)',   'lat' => 6.6885,  'lng' => -1.6244, 'is_campus' => false],
        'Western Region (Non-Campus / General)'                  => ['region' => 'Western Region (Takoradi)', 'lat' => 4.8986,  'lng' => -1.7603, 'is_campus' => false],
        'Other Ghanaian Campus / Region'                         => ['region' => 'Greater Accra Region',      'lat' => 5.6037,  'lng' => -0.1870, 'is_campus' => false],

        'Greater Accra Region'                                   => ['region' => 'Greater Accra Region',      'lat' => 5.6037,  'lng' => -0.1870, 'is_campus' => false],
        'Ashanti Region (Kumasi)'                                => ['region' => 'Ashanti Region (Kumasi)',   'lat' => 6.6885,  'lng' => -1.6244, 'is_campus' => false],
        'Western Region (Takoradi)'                              => ['region' => 'Western Region (Takoradi)', 'lat' => 4.8986,  'lng' => -1.7603, 'is_campus' => false],
        'Central Region (Cape Coast)'                            => ['region' => 'Central Region (Cape Coast)', 'lat' => 5.1053, 'lng' => -1.2466, 'is_campus' => false],
        'Eastern Region (Koforidua)'                             => ['region' => 'Eastern Region (Koforidua)', 'lat' => 6.0941, 'lng' => -0.2591, 'is_campus' => false],
        'Volta Region (Ho)'                                      => ['region' => 'Volta Region (Ho)',         'lat' => 6.6101,  'lng' => 0.4703,  'is_campus' => false],
        'Northern Region (Tamale)'                               => ['region' => 'Northern Region (Tamale)',  'lat' => 9.4034,  'lng' => -0.8424, 'is_campus' => false],
        'Bono Region (Sunyani)'                                  => ['region' => 'Bono Region (Sunyani)',     'lat' => 7.3349,  'lng' => -2.3123, 'is_campus' => false],
        'Upper East Region (Bolgatanga)'                         => ['region' => 'Upper East Region (Bolgatanga)', 'lat' => 10.7856, 'lng' => -0.8514, 'is_campus' => false],
        'Upper West Region (Wa)'                                 => ['region' => 'Upper West Region (Wa)',    'lat' => 10.0601, 'lng' => -2.5099, 'is_campus' => false],
        'Oti Region'                                             => ['region' => 'Oti Region',                'lat' => 8.0750,  'lng' => 0.1750,  'is_campus' => false],
        'Ahafo Region'                                           => ['region' => 'Ahafo Region',              'lat' => 6.8200,  'lng' => -2.5167, 'is_campus' => false],
        'Bono East Region'                                       => ['region' => 'Bono East Region',          'lat' => 7.5833,  'lng' => -1.9333, 'is_campus' => false],
        'North East Region'                                      => ['region' => 'North East Region',         'lat' => 10.5250, 'lng' => -0.3667, 'is_campus' => false],
        'Savannah Region'                                        => ['region' => 'Savannah Region',           'lat' => 9.0833,  'lng' => -1.8167, 'is_campus' => false],
        'Western North Region'                                   => ['region' => 'Western North Region',      'lat' => 6.2000,  'lng' => -2.4833, 'is_campus' => false],
    ];
}

/**
 * Resolves a campus or region string into canonical location metadata.
 */
function resolveGhanaLocation($locationName, $explicitRegion = '') {
    $dir = getGhanaLocationDirectory();
    $loc = trim((string)$locationName);
    $reg = trim((string)$explicitRegion);

    $matchedKey = null;
    if ($loc !== '' && isset($dir[$loc])) {
        $matchedKey = $loc;
    } elseif ($loc !== '') {
        foreach ($dir as $key => $meta) {
            if (strcasecmp($key, $loc) === 0 || stripos($key, $loc) !== false || stripos($loc, $key) !== false) {
                $matchedKey = $key;
                break;
            }
        }
    }

    $base = $matchedKey !== null
        ? array_merge($dir[$matchedKey], ['canonical' => $matchedKey])
        : ['canonical' => ($loc ?: 'Greater Accra Region'), 'region' => 'Greater Accra Region', 'lat' => 5.6037, 'lng' => -0.1870, 'is_campus' => false];

    if ($reg !== '' && isset($dir[$reg])) {
        $base['region'] = $dir[$reg]['region'];
        if (!$base['is_campus']) {
            $base['lat'] = $dir[$reg]['lat'];
            $base['lng'] = $dir[$reg]['lng'];
        }
    }

    return $base;
}

/**
 * Computes great-circle distance in kilometers between two coordinates via Haversine formula.
 */
function haversineDistanceKm($lat1, $lng1, $lat2, $lng2) {
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadiusKm * $c;
}

/**
 * Calculates tiered delivery fee for a single seller shipment.
 * - Tier 1: Same Campus -> GH₵ 5.00
 * - Tier 2: Same Region (Outside Campus) -> GH₵ 15.00
 * - Tier 3: Inter-Regional -> GH₵ 25.00 base + GH₵ 0.15/km (rounded to nearest cedi)
 */
function calculateSellerDeliveryFee($buyerCampus, $buyerRegion, $sellerLocation) {
    $buyer  = resolveGhanaLocation($buyerCampus, $buyerRegion);
    $seller = resolveGhanaLocation($sellerLocation);

    // Tier 1: Same Campus Delivery
    if ($buyer['is_campus'] && $seller['is_campus'] && strcasecmp($buyer['canonical'], $seller['canonical']) === 0) {
        return [
            'fee'         => 5.00,
            'tier'        => 'Same-Campus Delivery',
            'distance_km' => 0,
        ];
    }

    // Tier 2: Same Region (Outside Campus) Delivery
    if (strcasecmp($buyer['region'], $seller['region']) === 0) {
        return [
            'fee'         => 15.00,
            'tier'        => 'Same-Region Delivery',
            'distance_km' => 0,
        ];
    }

    // Tier 3: Outside Region (Inter-Regional) Distance-Based Delivery
    $km = max(1, (int)round(haversineDistanceKm($seller['lat'], $seller['lng'], $buyer['lat'], $buyer['lng'])));
    $fee = (float)round(25.00 + (0.15 * $km), 0);

    return [
        'fee'         => $fee,
        'tier'        => "Inter-Regional Delivery (~{$km} km)",
        'distance_km' => $km,
    ];
}

/**
 * Calculates total & per-seller delivery breakdown for a validated cart.
 */
function calculateCartDeliverySummary($buyerCampus, $buyerRegion, $items) {
    if (empty($items)) {
        return [
            'delivery_fee'  => 0.00,
            'delivery_tier' => 'Standard Delivery',
            'breakdown'     => [],
        ];
    }

    $sellerGroups = [];
    foreach ($items as $item) {
        $sid = (int)($item['seller_id'] ?? 0);
        $sLoc = trim((string)($item['seller_region'] ?? ($item['region'] ?? 'Greater Accra Region')));
        $groupKey = $sid . '|' . strtolower($sLoc);
        if (!isset($sellerGroups[$groupKey])) {
            $sellerGroups[$groupKey] = [
                'seller_name'     => $item['seller_name'] ?? 'Student Seller',
                'seller_location' => $sLoc,
            ];
        }
    }

    $totalFee = 0.00;
    $breakdown = [];
    foreach ($sellerGroups as $grp) {
        $calc = calculateSellerDeliveryFee($buyerCampus, $buyerRegion, $grp['seller_location']);
        $totalFee = round($totalFee + $calc['fee'], 2);
        $breakdown[] = [
            'seller_name'     => $grp['seller_name'],
            'seller_location' => $grp['seller_location'],
            'fee'             => $calc['fee'],
            'tier'            => $calc['tier'],
            'distance_km'     => $calc['distance_km'],
        ];
    }

    $summaryTier = count($breakdown) === 1
        ? $breakdown[0]['tier']
        : 'Multi-Seller Delivery (' . count($breakdown) . ' sellers)';

    return [
        'delivery_fee'  => $totalFee,
        'delivery_tier' => $summaryTier,
        'breakdown'     => $breakdown,
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

// ── Default Fallback Assets ──────────────────────────────────
define('DEFAULT_PRODUCT_FALLBACK_IMAGE', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80');

/**
 * Resolves product image paths into valid web URLs.
 * Handles full external URLs (Unsplash/CDN), data URIs, and uploaded filenames
 * while stripping duplicate 'uploads/' prefixes.
 */
function getProductImageUrl($image) {
    if (empty($image) || !is_string($image)) {
        return DEFAULT_PRODUCT_FALLBACK_IMAGE;
    }
    $image = trim($image);
    if ($image === '') {
        return DEFAULT_PRODUCT_FALLBACK_IMAGE;
    }

    // Direct HTTP/HTTPS URL or Data URI
    if (preg_match('#^https?://#i', $image) || strpos($image, 'data:image/') === 0) {
        return $image;
    }

    // Strip leading slashes and duplicate 'uploads/' prefixes
    $clean = ltrim($image, '/\\');
    while (stripos($clean, 'uploads/') === 0 || stripos($clean, 'uploads\\') === 0) {
        $clean = substr($clean, 8);
    }

    if (empty($clean)) {
        return DEFAULT_PRODUCT_FALLBACK_IMAGE;
    }

    if (preg_match('#^https?://#i', $clean)) {
        return $clean;
    }

    return 'uploads/' . $clean;
}

/**
 * Resolves product/avatar image URLs for templates inside /admin/.
 */
function getAdminImageUrl($image) {
    $url = getProductImageUrl($image);
    if ($url && !preg_match('#^https?://#i', $url) && strpos($url, 'data:image/') !== 0 && strpos($url, '/') !== 0) {
        return '../' . $url;
    }
    return $url;
}

// ── Chat Permissions Helper ──────────────────────────────────
function canAccessProductChat($conn, $product_id, $user_id, $other_user_id, $is_admin = false) {
    $product_id = (int)$product_id;
    $user_id = (int)$user_id;
    $other_user_id = (int)$other_user_id;

    if ($product_id < 1 || $user_id < 1 || $other_user_id < 1 || $user_id === $other_user_id) {
        return false;
    }

    $product_stmt = $conn->prepare("SELECT seller_id FROM products WHERE id=?");
    if (!$product_stmt) {
        return false;
    }
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product = $product_stmt->get_result()->fetch_assoc();
    if (!$product) {
        return false;
    }

    $seller_id = (int) $product['seller_id'];

    // Direct Buyer-to-Seller Communication:
    // If one of the participants is the owner/seller of the listing, chat is ALWAYS allowed
    if ($seller_id === $user_id || $seller_id === $other_user_id) {
        return true;
    }

    $other_stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
    if (!$other_stmt) {
        return false;
    }
    $other_stmt->bind_param("i", $other_user_id);
    $other_stmt->execute();
    $other_user = $other_stmt->get_result()->fetch_assoc();
    if (!$other_user) {
        return false;
    }

    // Admin mediator access:
    if ($is_admin) {
        if (!empty($other_user['is_admin'])) {
            return false;
        }

        $history_stmt = $conn->prepare("
            SELECT id FROM messages
            WHERE product_id=? AND (sender_id=? OR receiver_id=?)
            LIMIT 1
        ");
        $history_stmt->bind_param("iii", $product_id, $other_user_id, $other_user_id);
        $history_stmt->execute();
        return $history_stmt->get_result()->num_rows > 0;
    }

    // Customer chatting with an admin support representative:
    if (!empty($other_user['is_admin'])) {
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

    return false;
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
    $dir_ready = is_dir($upload_dir) || @mkdir($upload_dir, 0755, true) || is_dir($upload_dir);
    $filename = $prefix . bin2hex(random_bytes(16)) . '.' . $extensions[$mime_type];

    if ($dir_ready && is_writable($upload_dir) && @move_uploaded_file($temporary_path, $upload_dir . DIRECTORY_SEPARATOR . $filename)) {
        return ['filename' => $filename, 'error' => null];
    }

    // Fallback for read-only serverless filesystems (e.g., Vercel): encode as Data URI if <= 1.5MB
    if ($file_size <= 1572864 && is_readable($temporary_path)) {
        $raw = @file_get_contents($temporary_path);
        if ($raw !== false) {
            $data_uri = 'data:' . $mime_type . ';base64,' . base64_encode($raw);
            return ['filename' => $data_uri, 'error' => null];
        }
    }

    return ['filename' => null, 'error' => 'The image could not be stored. Please ensure the uploads folder is writable or upload an image under 1.5MB.'];
}
