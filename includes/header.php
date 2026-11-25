<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial Header & Global Navigation
// ════════════════════════════════════════════════════════════

$current_page = basename($_SERVER['PHP_SELF']);
$unread_messages = 0;
$wishlist_count = 0;

if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];
    $unread_res = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=$uid AND is_read=0");
    if ($unread_res) {
        $unread_messages = (int)$unread_res->fetch_assoc()['cnt'];
    }
    $wl_res = $conn->query("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id=$uid");
    if ($wl_res) {
        $wishlist_count = (int)$wl_res->fetch_assoc()['cnt'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="UniMarket Ghana is Ghana's trusted nationwide online commercial marketplace. Shop electronics, smartphones, fashion, home essentials & appliances with Mobile Money and doorstep delivery.">
    <meta name="author" content="UniMarket Ghana Commercial Retail">
    <meta property="og:title" content="<?= isset($page_title) ? htmlspecialchars($page_title) . ' — UniMarket Ghana' : 'UniMarket Ghana — Commercial Marketplace' ?>">
    <meta property="og:description" content="Ghana's premier online marketplace. Shop top brands with instant Mobile Money payments and secure nationwide delivery.">
    <meta property="og:type" content="website">

    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — UniMarket Ghana' : 'UniMarket Ghana — Commercial Marketplace' ?></title>

    <!-- Professional SVG Shopping Bag Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Design System Stylesheet -->
    <link href="css/style.css?v=<?= @filemtime(__DIR__ . '/../css/style.css') ?: '2' ?>" rel="stylesheet">
    <script>
        (function () {
            try {
                if (localStorage.getItem('unimarket_theme') === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>

<!-- Top Notice Bar -->
<div class="top-notice-bar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-mortarboard-fill text-warning me-1"></i>
            <span>Ghana's Student Entrepreneurship & Campus Commerce Hub · Legon, KNUST, UCC, UPSA, Ashesi & Nationwide</span>
        </div>
        <div class="d-flex gap-3">
            <span><i class="bi bi-shield-check text-success me-1"></i> Verified Student Founders</span>
            <span><i class="bi bi-phone me-1"></i> MTN MoMo & Telecel Cash</span>
            <span><i class="bi bi-headset me-1"></i> support@unimarket.com.gh</span>
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
<!-- ── STICKY COMMERCIAL HEADER ───────────────────────────────── -->
<header class="um-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <!-- Brand Logo -->
            <a class="navbar-brand-logo me-2" href="dashboard.php" title="UniMarket Ghana Home">
                <div class="brand-icon-box">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span>Uni<span class="brand-accent-text">Market</span></span>
                    <span class="brand-country-badge">CAMPUS GH</span>
                </div>
            </a>

            <!-- Center Search Bar -->
            <div class="flex-grow-1 mx-lg-4 d-none d-md-block" style="max-width: 520px;">
                <form method="GET" action="dashboard.php" class="header-search-wrap">
                    <i class="bi bi-search header-search-icon"></i>
                    <input type="text" name="q" class="header-search-input"
                           placeholder="Search student brands, tech, streetwear, food, campus services..."
                           value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>"
                           autocomplete="off">
                </form>
            </div>

            <!-- Right Action Stack -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <!-- Dark/Light Theme Toggle -->
                <button type="button" id="themeToggleBtn" class="header-action-btn" title="Toggle Dark / Light Mode" aria-label="Toggle theme">
                    <i class="bi bi-moon-stars" id="themeToggleIcon"></i>
                </button>

                <!-- Wishlist -->
                <a href="wishlist.php" class="header-action-btn <?= $current_page=='wishlist.php'?'active':'' ?>" title="Saved Items">
                    <i class="bi bi-heart"></i>
                    <span class="header-badge-count" id="navWishlistBadge" style="<?= $wishlist_count > 0 ? '' : 'display:none;' ?>">
                        <?= $wishlist_count ?>
                    </span>
                </a>

                <!-- Cart Button with Bubble -->
                <button type="button" class="header-action-btn" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-controls="cartDrawer" title="Shopping Cart">
                    <i class="bi bi-cart3"></i>
                    <span class="header-badge-count cart-count-badge" style="display:none;">0</span>
                </button>

                <!-- Messages -->
                <a href="chat.php" class="header-action-btn <?= $current_page=='chat.php'?'active':'' ?>" title="Messages & Inquiries">
                    <i class="bi bi-chat-dots"></i>
                    <?php if ($unread_messages > 0): ?>
                        <span class="header-badge-count" style="background:var(--color-danger);"><?= $unread_messages ?></span>
                    <?php endif; ?>
                </a>

                <!-- User Dropdown Menu -->
                <div class="dropdown ms-1">
                    <button class="header-action-btn border-0 dropdown-toggle p-0 overflow-hidden" data-bs-toggle="dropdown" aria-expanded="false" title="Account Menu">
                        <?php if (!empty($_SESSION['avatar'])): ?>
                            <img src="<?= htmlspecialchars(getProductImageUrl($_SESSION['avatar'])) ?>" alt="<?= sanitize($_SESSION['full_name']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.style.display='none';">
                        <?php else: ?>
                            <span class="fw-bold" style="font-size:14px;color:var(--brand-slate);"><?= getAvatar($_SESSION['full_name']) ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:14px;min-width:240px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark text-truncate" style="font-size:14px;"><?= sanitize($_SESSION['full_name']) ?></div>
                            <div class="text-muted small text-truncate" style="font-size:12px;"><?= sanitize($_SESSION['email']) ?></div>
                            <span class="badge bg-light text-muted border mt-1" style="font-size:10.5px;"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($_SESSION['region'] ?? 'Ghana Campus') ?></span>
                        </li>
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person-badge me-2 text-primary"></i>My Campus Profile & Delivery</a></li>
                        <li><a class="dropdown-item py-2" href="orders.php"><i class="bi bi-receipt me-2 text-info"></i>My Orders & Tracking</a></li>
                        <li><a class="dropdown-item py-2" href="my-listings.php"><i class="bi bi-rocket-takeoff me-2 text-warning"></i>My Student Venture / Listings</a></li>
                        <li><a class="dropdown-item py-2" href="wishlist.php"><i class="bi bi-heart me-2 text-danger"></i>Saved Items</a></li>
                        <li><a class="dropdown-item py-2" href="cart.php"><i class="bi bi-bag-check me-2 text-success"></i>Cart & Checkout</a></li>
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 fw-bold" style="color:var(--accent-orange);" href="admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Campus Admin Console</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>

                <!-- Sell Button -->
                <a href="post.php" class="btn btn-orange btn-sm ms-2 d-none d-sm-inline-flex align-items-center gap-1" style="padding:9px 18px;">
                    <i class="bi bi-rocket-takeoff-fill"></i> Become a Student Seller
                </a>
            </div>
        </div>

        <!-- Mobile Search Bar (Visible only on small viewports) -->
        <div class="d-md-none mt-3">
            <form method="GET" action="dashboard.php" class="header-search-wrap">
                <i class="bi bi-search header-search-icon"></i>
                <input type="text" name="q" class="header-search-input py-2"
                       placeholder="Search student brands, tech, books, food..."
                       value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
            </form>
        </div>
    </div>
</header>
<?php endif; ?>
