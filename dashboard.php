<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial Marketplace Catalog & Feed
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'Nationwide Marketplace & Online Shopping';

// ── Fetch Retail Categories ──────────────────────────────────
$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC");
$regions = getGhanaCampuses();

// ── Build Filter Query ───────────────────────────────────────
$where   = ["p.status = 'available'"];
$params  = [];
$types   = '';

// Keyword search
if (!empty($_GET['q'])) {
    $q = '%' . trim($_GET['q']) . '%';
    $where[] = "(p.title LIKE ? OR p.description LIKE ? OR p.region LIKE ? OR u.full_name LIKE ?)";
    $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q;
    $types .= 'ssss';
}

// Category filter
if (!empty($_GET['cat']) && is_numeric($_GET['cat'])) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$_GET['cat'];
    $types .= 'i';
}

// Campus / Region filter
if (!empty($_GET['region'])) {
    $where[] = "(p.region LIKE ? OR u.region LIKE ?)";
    $params[] = '%' . trim($_GET['region']) . '%';
    $params[] = '%' . trim($_GET['region']) . '%';
    $types .= 'ss';
}

// Condition filter
if (!empty($_GET['condition']) && in_array($_GET['condition'], ['New','Like New','Good','Refurbished'], true)) {
    $where[] = "p.condition_type = ?";
    $params[] = $_GET['condition'];
    $types .= 's';
}

// Sorting
$sort_options = [
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'oldest'     => 'p.created_at ASC',
    'popular'    => 'p.views DESC',
];
$sort_key = $_GET['sort'] ?? '';
$sort = (is_string($sort_key) && isset($sort_options[$sort_key]))
    ? $sort_options[$sort_key]
    : 'p.created_at DESC';

$sql = "SELECT p.*, c.name AS cat_name, c.icon AS cat_icon, u.full_name AS seller_name, u.avatar AS seller_avatar, u.is_verified AS seller_verified
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $sort";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $conn->query($sql);
}

// Counts for live stats
$total_available = $conn->query("SELECT COUNT(*) FROM products WHERE status='available'")->fetch_row()[0] ?? 0;
$total_sold      = $conn->query("SELECT COUNT(*) FROM products WHERE status='sold'")->fetch_row()[0] ?? 0;
$total_sellers   = $conn->query("SELECT COUNT(DISTINCT seller_id) FROM products")->fetch_row()[0] ?? 0;

// User Wishlist IDs
$uid = (int)$_SESSION['user_id'];
$wl_res = $conn->query("SELECT product_id FROM wishlist WHERE user_id=$uid");
$wl_ids = [];
if ($wl_res) {
    while ($w = $wl_res->fetch_assoc()) {
        $wl_ids[] = (int)$w['product_id'];
    }
}

include 'includes/header.php';
?>

<!-- ── HERO BANNER ────────────────────────────────────────────── -->
<section class="commercial-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <!-- Left Hero Content -->
            <div class="col-lg-7">
                <div class="hero-pill-badge">
                    <i class="bi bi-rocket-takeoff-fill text-warning me-1"></i> Ghana's Student Entrepreneurship Platform
                </div>
                <h1 class="hero-main-title">
                    <span style="color:#FFFFFF;">Discover &amp; Support</span>
                    <span style="color:#EA580C;">Student Entrepreneurs</span><br>
                    <span style="color:#F59E0B;font-size:0.86em;font-weight:700;">Across Ghanaian Campuses</span>
                </h1>
                <p class="hero-subtext">
                    Shop student-founded brands, custom streetwear, tech gadgets, hostel essentials, books, snacks, and creative campus services from verified student entrepreneurs at CCTU, UCC ,Legon, KNUST, UPSA, and nationwide.
                </p>

                <!-- Search Bar with Region / Campus Selector -->
                <form method="GET" action="dashboard.php" class="p-2 bg-white rounded-4 shadow-lg d-flex align-items-center gap-2 mb-3" style="max-width:620px;">
                    <i class="bi bi-search text-muted ms-2 fs-5"></i>
                    <input type="text" name="q" id="liveSearch" class="form-control border-0 shadow-none px-2 py-2"
                           placeholder="Search student brands, tech, fashion, food, campus services..."
                           value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>"
                           autocomplete="off">

                    <select name="region" class="form-select border-0 bg-light d-none d-md-block" style="max-width:180px;font-size:13px;font-weight:600;">
                        <option value="">All Campuses & Hubs</option>
                        <option value="Legon" <?= (isset($_GET['region']) && strpos($_GET['region'],'Legon')!==false)?'selected':'' ?>>UG - Legon</option>
                        <option value="KNUST" <?= (isset($_GET['region']) && strpos($_GET['region'],'KNUST')!==false)?'selected':'' ?>>KNUST - Kumasi</option>
                        <option value="UCC" <?= (isset($_GET['region']) && strpos($_GET['region'],'UCC')!==false)?'selected':'' ?>>UCC - Cape Coast</option>
                        <option value="Cape Coast Technical University (CCTU)" <?= (isset($_GET['region']) && (strpos($_GET['region'],'CCTU')!==false || strpos($_GET['region'],'Cape Coast Technical')!==false))?'selected':'' ?>>Cape Coast Technical (CCTU)</option>
                        <option value="UPSA" <?= (isset($_GET['region']) && strpos($_GET['region'],'UPSA')!==false)?'selected':'' ?>>UPSA - Accra</option>
                        <option value="Ashesi" <?= (isset($_GET['region']) && strpos($_GET['region'],'Ashesi')!==false)?'selected':'' ?>>Ashesi University</option>
                        <option value="Greater Accra" <?= (isset($_GET['region']) && strpos($_GET['region'],'Accra')!==false && strpos($_GET['region'],'UPSA')===false)?'selected':'' ?>>Greater Accra Hub</option>
                        <option value="Ashanti" <?= (isset($_GET['region']) && strpos($_GET['region'],'Ashanti')!==false && strpos($_GET['region'],'KNUST')===false)?'selected':'' ?>>Ashanti Hub</option>
                    </select>

                    <?php if (!empty($_GET['cat'])): ?>
                        <input type="hidden" name="cat" value="<?= (int)$_GET['cat'] ?>">
                    <?php endif; ?>

                    <button type="submit" class="btn btn-orange px-4 py-2 fw-bold text-nowrap">
                        Search
                    </button>
                </form>

                <div class="d-flex align-items-center gap-3 text-white-50 small flex-wrap">
                    <span><i class="bi bi-mortarboard-fill text-warning me-1"></i> Verified Student Founders</span>
                    <span>•</span>
                    <span><i class="bi bi-lightning-charge text-warning me-1"></i> Instant MoMo Checkout</span>
                    <span>•</span>
                    <span><i class="bi bi-building text-info me-1"></i> Campus & Hostel Delivery</span>
                </div>
            </div>

            <!-- Right Hero Metrics -->
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="trust-metric-card">
                            <div class="fs-4 mb-1 text-primary"><i class="bi bi-rocket-takeoff"></i></div>
                            <div class="trust-metric-value"><?= number_format($total_available) ?>+</div>
                            <div class="trust-metric-label">Student Listings</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="trust-metric-card">
                            <div class="fs-4 mb-1 text-success"><i class="bi bi-shield-check"></i></div>
                            <div class="trust-metric-value"><?= number_format($total_sold) ?>+</div>
                            <div class="trust-metric-label">Orders Fulfilled</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="trust-metric-card">
                            <div class="fs-4 mb-1 text-warning"><i class="bi bi-mortarboard"></i></div>
                            <div class="trust-metric-value"><?= number_format($total_sellers) ?>+</div>
                            <div class="trust-metric-label">Student Ventures</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="trust-metric-card">
                            <div class="fs-4 mb-1" style="color:var(--accent-orange);"><i class="bi bi-buildings"></i></div>
                            <div class="trust-metric-value">16+</div>
                            <div class="trust-metric-label">Campuses & Hubs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── STICKY CATEGORIES STRIP ───────────────────────────────── -->
<div class="cat-filter-strip">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <!-- Category Chips -->
            <div class="cat-scroll-container d-flex gap-2 overflow-auto pb-1 flex-nowrap" style="scrollbar-width:none;-ms-overflow-style:none;">
                <a href="dashboard.php<?= !empty($_GET['region']) ? '?region='.urlencode($_GET['region']) : '' ?>"
                   class="cat-chip <?= empty($_GET['cat']) ? 'active' : '' ?>">
                    <i class="bi bi-grid-fill me-1"></i> All Ventures
                </a>
                <?php
                if ($cats) {
                    $cats->data_seek(0);
                    while ($cat = $cats->fetch_assoc()):
                        $isActive = (isset($_GET['cat']) && (int)$_GET['cat'] === (int)$cat['id']);
                        $queryData = $_GET;
                        $queryData['cat'] = $cat['id'];
                ?>
                <a href="dashboard.php?<?= http_build_query($queryData) ?>"
                   class="cat-chip <?= $isActive ? 'active' : '' ?>">
                    <i class="bi <?= htmlspecialchars($cat['icon']) ?> me-1"></i> <?= htmlspecialchars($cat['name']) ?>
                </a>
                <?php
                    endwhile;
                }
                ?>
            </div>

            <!-- Filters & Sort -->
            <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                <!-- Campus Filter Dropdown -->
                <select class="form-select um-input py-2" style="font-size:13px;width:auto;"
                        onchange="location='dashboard.php?<?= http_build_query(array_merge($_GET, ['region'=>''])) ?>&region='+encodeURIComponent(this.value)">
                    <option value="">All Campuses &amp; Hubs</option>
                    <option value="University of Ghana (UG - Legon)" <?= (isset($_GET['region']) && strpos($_GET['region'],'Legon')!==false)?'selected':'' ?>>UG - Legon</option>
                    <option value="Kwame Nkrumah Univ of Science & Tech (KNUST - Kumasi)" <?= (isset($_GET['region']) && strpos($_GET['region'],'KNUST')!==false)?'selected':'' ?>>KNUST - Kumasi</option>
                    <option value="University of Cape Coast (UCC - Cape Coast)" <?= (isset($_GET['region']) && strpos($_GET['region'],'UCC')!==false)?'selected':'' ?>>UCC - Cape Coast</option>
                    <option value="Cape Coast Technical University (CCTU)" <?= (isset($_GET['region']) && (strpos($_GET['region'],'CCTU')!==false || strpos($_GET['region'],'Cape Coast Technical')!==false))?'selected':'' ?>>Cape Coast Technical (CCTU)</option>
                    <option value="University of Professional Studies, Accra (UPSA)" <?= (isset($_GET['region']) && strpos($_GET['region'],'UPSA')!==false)?'selected':'' ?>>UPSA - Accra</option>
                    <option value="Ashesi University (Berekuso)" <?= (isset($_GET['region']) && strpos($_GET['region'],'Ashesi')!==false)?'selected':'' ?>>Ashesi University</option>
                    <option value="Accra Technical University (ATU)" <?= (isset($_GET['region']) && strpos($_GET['region'],'ATU')!==false)?'selected':'' ?>>ATU - Accra</option>
                    <option value="Kumasi Technical University (KsTU)" <?= (isset($_GET['region']) && strpos($_GET['region'],'KsTU')!==false)?'selected':'' ?>>KsTU - Kumasi</option>
                    <option value="Ho Technical University (HTU)" <?= (isset($_GET['region']) && strpos($_GET['region'],'HTU')!==false)?'selected':'' ?>>HTU - Ho</option>
                    <option value="Tamale Technical University (TaTU)" <?= (isset($_GET['region']) && strpos($_GET['region'],'TaTU')!==false)?'selected':'' ?>>TaTU - Tamale</option>
                </select>

                <!-- Condition Dropdown -->
                <select class="form-select um-input py-2" style="font-size:13px;width:auto;"
                        onchange="location='dashboard.php?<?= http_build_query(array_merge($_GET, ['condition'=>''])) ?>&condition='+this.value">
                    <option value="">All Conditions</option>
                    <option value="New" <?= (($_GET['condition']??'')==='New')?'selected':'' ?>>Brand New / Original</option>
                    <option value="Like New" <?= (($_GET['condition']??'')==='Like New')?'selected':'' ?>>Like New / Mint</option>
                    <option value="Refurbished" <?= (($_GET['condition']??'')==='Refurbished')?'selected':'' ?>>Refurbished</option>
                    <option value="Good" <?= (($_GET['condition']??'')==='Good')?'selected':'' ?>>Gently Used</option>
                </select>

                <!-- Sort Dropdown -->
                <select class="form-select um-input py-2" style="font-size:13px;width:auto;"
                        onchange="location='dashboard.php?<?= http_build_query(array_merge($_GET, ['sort'=>''])) ?>&sort='+this.value">
                    <option value="newest" <?= ($_GET['sort']??'')==='newest'?'selected':'' ?>>Newest Drops</option>
                    <option value="popular" <?= ($_GET['sort']??'')==='popular'?'selected':'' ?>>Trending on Campus</option>
                    <option value="price_asc" <?= ($_GET['sort']??'')==='price_asc'?'selected':'' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= ($_GET['sort']??'')==='price_desc'?'selected':'' ?>>Price: High to Low</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ── BECOME A STUDENT SELLER BANNER ────────────────────────── -->
<div class="container mt-4 mb-2">
    <div class="p-4 p-md-5 rounded-4 text-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4" style="background:linear-gradient(135deg, var(--brand-slate) 0%, #0F172A 100%);border:1px solid rgba(255,255,255,0.08);box-shadow:var(--shadow-md);">
        <div>
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background:rgba(234,88,12,0.18);border:1px solid rgba(234,88,12,0.35);color:#FDBA74;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                <i class="bi bi-rocket-takeoff-fill"></i> For Student Founders & Creators
            </div>
            <h3 class="fw-bold mb-2 text-white" style="font-size:24px;">Turn Your Campus Hustle into a Real Business</h3>
            <p class="text-white-50 mb-0" style="max-width:580px;font-size:14px;line-height:1.6;">
                Launch your student-run brand on UniMarket. Reach thousands of buyers across Ghanaian universities with instant Mobile Money settlements and campus delivery.
            </p>
        </div>
        <div class="flex-shrink-0">
            <a href="post.php" class="btn btn-orange px-4 py-3 fw-bold fs-6 d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-circle-fill"></i> Become a Student Seller
            </a>
        </div>
    </div>
</div>

<!-- ── PRODUCT CATALOG GRID ──────────────────────────────────── -->
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="font-size:22px;">Campus Ventures & Marketplace Drops</h3>
            <p class="text-muted small mb-0">Browse student-owned brand releases, gear, study essentials, and verified listings across Ghana</p>
        </div>
        <span class="badge bg-light text-muted border px-3 py-2 fw-semibold" style="font-size:12.5px;">
            <?= $products ? $products->num_rows : 0 ?> listings active
        </span>
    </div>

    <?php if (!$products || $products->num_rows === 0): ?>
    <!-- Empty State -->
    <div class="text-center py-5 my-4">
        <div style="font-size:54px;margin-bottom:12px;" class="text-muted"><i class="bi bi-search"></i></div>
        <h4 class="fw-bold text-dark mb-2">No student listings found</h4>
        <p class="text-muted small mb-4" style="max-width:380px;margin:0 auto 20px;">We could not find products matching your filters. Try clearing keywords or exploring all campus departments.</p>
        <a href="dashboard.php" class="btn btn-navy px-4 py-2">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
        </a>
    </div>
    <?php else: ?>
    <!-- Responsive Product Grid -->
    <div class="row g-3 g-md-4">
        <?php while ($p = $products->fetch_assoc()):
            $isWishlisted = in_array((int)$p['id'], $wl_ids, true);
            $cleanTitle = htmlspecialchars($p['title']);
            $cleanSeller = htmlspecialchars($p['seller_name']);
            $cleanRegion = htmlspecialchars($p['region'] ?? 'Ghana Campus');
            $ratingVal = number_format((float)($p['rating'] ?? 4.8), 1);
            $reviewsCount = (int)($p['reviews_count'] ?? 18);
            $imgSrc = !empty($p['image']) ? 'uploads/' . htmlspecialchars($p['image']) : '';
        ?>
        <div class="col-6 col-md-4 col-lg-3 product-grid-item"
             data-title="<?= strtolower($cleanTitle) ?>"
             data-seller="<?= strtolower($cleanSeller) ?>"
             data-cat="<?= (int)$p['category_id'] ?>"
             data-region="<?= strtolower($cleanRegion) ?>">

            <div class="um-card">
                <!-- Media Aspect Ratio Container -->
                <div class="card-media-box">
                    <a href="product.php?id=<?= $p['id'] ?>" class="w-100 h-100 d-flex align-items-center justify-content-center">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= $cleanTitle ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-media-placeholder"><i class="bi <?= htmlspecialchars($p['cat_icon']) ?> fs-1 text-secondary"></i></div>
                        <?php endif; ?>
                    </a>

                    <!-- Floating Tags -->
                    <div class="card-floating-tags">
                        <span class="badge-tag badge-tag-condition">
                            <i class="bi bi-mortarboard-fill me-1"></i><?= htmlspecialchars($p['condition_type']) ?>
                        </span>
                        <span class="badge-tag badge-tag-region" title="<?= $cleanRegion ?>">
                            <i class="bi bi-geo-alt-fill me-1"></i><?= $cleanRegion ?>
                        </span>
                    </div>

                    <!-- Wishlist Button -->
                    <?php if ((int)$p['seller_id'] !== $uid): ?>
                    <button type="button" class="card-wish-btn <?= $isWishlisted ? 'active' : '' ?>"
                            data-id="<?= $p['id'] ?>" title="Save to wishlist">
                        <i class="bi <?= $isWishlisted ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                    </button>
                    <?php endif; ?>

                    <?php if ($p['status'] === 'sold'): ?>
                    <div class="position-absolute inset-0 d-flex align-items-center justify-content-center bg-dark bg-opacity-75" style="inset:0;z-index:4;">
                        <span class="badge bg-danger px-3 py-2 fw-bold">SOLD OUT</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card Body -->
                <div class="card-main-content">
                    <div class="card-category-slug">
                        <i class="bi <?= htmlspecialchars($p['cat_icon']) ?> text-primary me-1"></i> <?= htmlspecialchars($p['cat_name']) ?>
                    </div>

                    <a href="product.php?id=<?= $p['id'] ?>" class="card-title-link" title="<?= $cleanTitle ?>">
                        <?= $cleanTitle ?>
                    </a>

                    <!-- Star Ratings Component (Warm Amber #D97706) -->
                    <div class="product-rating-row">
                        <div class="stars-amber">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                        </div>
                        <span class="rating-score"><?= $ratingVal ?></span>
                        <span class="rating-count">(<?= $reviewsCount ?>)</span>
                    </div>

                    <div class="card-price-value">
                        <?= formatPrice($p['price']) ?>
                    </div>

                    <!-- Seller info -->
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top small text-muted mb-3">
                        <span class="text-truncate" style="max-width:130px;" title="Student Founder: <?= $cleanSeller ?>">
                            <i class="bi bi-mortarboard me-1 text-primary"></i><?= $cleanSeller ?>
                        </span>
                        <span style="font-size:11px;"><?= timeAgo($p['created_at']) ?></span>
                    </div>

                    <!-- CTA Bar -->
                    <div class="card-cta-bar">
                        <?php if ($p['status'] === 'available' && (int)$p['seller_id'] !== $uid): ?>
                        <button type="button" class="btn-add-cart-orange btn-add-to-cart"
                                data-id="<?= $p['id'] ?>"
                                data-title="<?= $cleanTitle ?>"
                                data-price="<?= $p['price'] ?>"
                                data-image="<?= htmlspecialchars($p['image'] ?? '') ?>"
                                data-cat-icon="<?= htmlspecialchars($p['cat_icon']) ?>"
                                data-seller="<?= $cleanSeller ?>"
                                data-region="<?= $cleanRegion ?>">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                        <?php endif; ?>

                        <a href="product.php?id=<?= $p['id'] ?>" class="btn-card-details <?= ((int)$p['seller_id'] === $uid || $p['status'] !== 'available') ? 'w-100' : '' ?>" title="View Details">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
