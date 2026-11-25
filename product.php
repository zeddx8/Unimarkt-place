<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial Product Details & Order Gateway
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit(); }

// ── Fetch Product Details ────────────────────────────────────
$stmt = $conn->prepare("
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon,
           u.full_name AS seller_name, u.email AS seller_email,
           u.phone AS seller_phone, u.avatar AS seller_avatar,
           u.region AS seller_region, u.city AS seller_city,
           u.is_verified AS seller_verified
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) { header('Location: dashboard.php'); exit(); }

// Increment view counter (skip if viewed by seller)
$uid = (int)$_SESSION['user_id'];
if ((int)$p['seller_id'] !== $uid) {
    $conn->query("UPDATE products SET views = views + 1 WHERE id = $id");
}

// Check wishlist
$wl = false;
$wl_query = $conn->query("SELECT id FROM wishlist WHERE user_id=$uid AND product_id=$id");
if ($wl_query && $wl_query->num_rows > 0) {
    $wl = true;
}

// Handle report
$report_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_reason'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $reason  = sanitize($_POST['report_reason']);
        $details = sanitize($_POST['report_details'] ?? '');
        $stmt2   = $conn->prepare("INSERT INTO reports (reporter_id, product_id, reason, details) VALUES (?,?,?,?)");
        if ($stmt2) {
            $stmt2->bind_param("iiss", $uid, $id, $reason, $details);
            $stmt2->execute();
            $report_success = true;
        }
    }
}

// Related / Other products from this merchant
$other = $conn->prepare("SELECT p.id, p.title, p.price, p.image, p.region, p.rating, p.reviews_count, c.icon AS cat_icon FROM products p JOIN categories c ON p.category_id=c.id WHERE p.seller_id=? AND p.id!=? AND p.status='available' LIMIT 4");
$other->bind_param("ii", $p['seller_id'], $id);
$other->execute();
$others = $other->get_result();

$cleanTitle = htmlspecialchars($p['title']);
$cleanSeller = htmlspecialchars($p['seller_name']);
$cleanRegion = htmlspecialchars($p['region'] ?? 'Greater Accra');
$ratingVal = number_format((float)($p['rating'] ?? 4.8), 1);
$reviewsCount = (int)($p['reviews_count'] ?? 18);
$page_title = $cleanTitle;

include 'includes/header.php';
?>

<div class="container py-5">
    <?php if (!empty($_GET['posted'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <strong>Listing Published!</strong> Your product is now live across UniMarket Ghana for shoppers nationwide.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <a href="dashboard.php" class="text-muted d-inline-flex align-items-center gap-1 mb-4 fw-semibold" style="font-size:14px;">
        <i class="bi bi-arrow-left"></i> Back to Catalog
    </a>

    <div class="row g-4">
        <!-- ── Left Column: Media Box & Full Details ── -->
        <div class="col-lg-7">
            <!-- Product Media Viewport -->
            <div class="um-card overflow-hidden mb-4 p-0">
                <div class="card-media-box product-detail-media">
                    <?php 
                        $pImg = getProductImageUrl($p['image'] ?? '');
                    ?>
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= $pImg ?>" alt="<?= $cleanTitle ?>" style="object-fit:contain;background:#fff;" onerror="this.onerror=null; this.src='<?= DEFAULT_PRODUCT_FALLBACK_IMAGE ?>';">
                    <?php else: ?>
                        <div class="card-media-placeholder" style="font-size:96px;"><i class="bi <?= htmlspecialchars($p['cat_icon']) ?> text-secondary"></i></div>
                    <?php endif; ?>

                    <?php if ($p['status'] === 'sold'): ?>
                        <div class="position-absolute inset-0 d-flex align-items-center justify-content-center bg-dark bg-opacity-75" style="inset:0;z-index:4;">
                            <span class="badge bg-danger fs-6 px-4 py-2 fw-bold">SOLD OUT</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Specs & Overview Card -->
            <div class="um-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="card-category-slug mb-1"><i class="bi <?= htmlspecialchars($p['cat_icon']) ?> text-primary me-1"></i><?= htmlspecialchars($p['cat_name']) ?></div>
                        <h1 class="fw-bold mb-2" style="font-size:24px;line-height:1.3;color:var(--brand-navy);"><?= $cleanTitle ?></h1>
                    </div>

                    <?php if ((int)$p['seller_id'] !== $uid): ?>
                    <button type="button" class="card-wish-btn position-static <?= $wl ? 'active' : '' ?>"
                            data-id="<?= $p['id'] ?>" title="Save to Wishlist" style="width:42px;height:42px;font-size:18px;">
                        <i class="bi <?= $wl ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Star Ratings Component (Warm Amber #D97706) -->
                <div class="product-rating-row mb-3">
                    <div class="stars-amber fs-6">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-half"></i>
                    </div>
                    <span class="rating-score fs-6"><?= $ratingVal ?></span>
                    <span class="rating-count">(<?= $reviewsCount ?> customer reviews)</span>
                    <span class="text-muted ms-2">• Verified Student Venture</span>
                </div>

                <!-- Price Tag -->
                <div class="card-price-value fs-2 text-dark mb-3">
                    <?= formatPrice($p['price']) ?>
                    <span class="fs-6 text-muted fw-normal ms-2">(Ghana Cedis)</span>
                </div>

                <!-- Attributes Row -->
                <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom">
                    <span class="badge-tag badge-tag-condition px-3 py-2 fs-6">
                        <i class="bi bi-mortarboard-fill me-1"></i><?= htmlspecialchars($p['condition_type']) ?>
                    </span>
                    <span class="badge bg-light text-muted border px-3 py-2 fw-normal d-inline-flex align-items-center">
                        <i class="bi bi-eye me-1"></i> <?= (int)$p['views'] + 1 ?> views
                    </span>
                    <span class="badge bg-light text-muted border px-3 py-2 fw-normal d-inline-flex align-items-center">
                        <i class="bi bi-buildings text-success me-1"></i> Campus & Doorstep Delivery
                    </span>
                </div>

                <!-- Seller Campus & Location Highlight (Exclusive to Product Details) -->
                <div class="p-3 mb-4 rounded-3 d-flex align-items-center gap-3" style="background:#FFF7ED;border:1.5px solid #FED7AA;">
                    <div style="font-size:26px;color:#EA580C;"><i class="bi bi-geo-alt-fill"></i></div>
                    <div>
                        <div class="small text-uppercase fw-bold text-muted" style="letter-spacing:0.5px;">Campus Location</div>
                        <div class="fs-6 fw-bold text-dark"><?= $cleanRegion ?></div>
                        <div class="small text-muted">Available for verified direct campus pickup or student delivery</div>
                    </div>
                </div>

                <!-- Detailed Description -->
                <?php if (!empty($p['description'])): ?>
                <div>
                    <h6 class="fw-bold text-uppercase text-muted mb-2" style="font-size:12px;letter-spacing:0.6px;">Product Specifications</h6>
                    <p class="text-secondary" style="font-size:15px;line-height:1.75;white-space:pre-line;"><?= sanitize($p['description']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Commercial Buyer Protection Box -->
                <div class="p-3 mt-4 rounded-3 bg-light border d-flex align-items-center gap-3">
                    <div style="font-size:32px;color:var(--accent-emerald);"><i class="bi bi-shield-check"></i></div>
                    <div class="small text-muted">
                        <strong class="text-dark">UniMarket Campus Escrow & Buyer Protection:</strong>
                        <div>Every order is backed by our 7-day student guarantee. Inspect your delivery at your hall, hostel, or doorstep before funds are released to the student seller.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Right Column: Merchant & Checkout Actions ── -->
        <div class="col-lg-5">
            <!-- Merchant Profile Card -->
            <div class="um-card p-4 mb-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:12px;letter-spacing:0.6px;">Student Founder / Venture</h6>
                <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
                    <div class="seller-avatar" style="width:54px;height:54px;font-size:20px;border-radius:50%;background:var(--brand-navy);color:#fff;display:flex;align-items:center;justify-content:center;">
                        <?php if (!empty($p['seller_avatar'])): ?>
                            <img src="<?= htmlspecialchars(getProductImageUrl($p['seller_avatar'])) ?>" alt="<?= $cleanSeller ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.style.display='none';">
                        <?php else: ?>
                            <?= getAvatar($p['seller_name']) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark">
                            <?= $cleanSeller ?>
                            <?php if (!empty($p['seller_verified'])): ?>
                                <i class="bi bi-patch-check-fill text-primary ms-1" title="Verified Student Founder"></i>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($p['seller_city'] ?? 'Campus') ?>, <?= $cleanRegion ?></div>
                        <div class="text-success small fw-semibold"><i class="bi bi-shield-check me-1"></i>Verified Student Entrepreneur</div>
                    </div>
                </div>

                <?php if ((int)$p['seller_id'] === $uid): ?>
                    <!-- Merchant Owner Actions -->
                    <div class="d-grid gap-2">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold py-2 mb-2">You are managing this student listing</span>
                        <a href="edit-product.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary py-2 fw-bold">
                            <i class="bi bi-pencil-square me-1"></i> Edit Listing
                        </a>
                        <?php if ($p['status'] === 'available'): ?>
                        <form method="POST" action="mark-sold.php" class="d-grid">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-outline-success py-2 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Mark as Sold
                            </button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" action="delete-product.php" class="d-grid" onsubmit="return confirm('Remove this listing from inventory?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger py-2 fw-bold">
                                <i class="bi bi-trash3 me-1"></i> Remove from Inventory
                            </button>
                        </form>
                    </div>

                <?php elseif ($p['status'] === 'available'): ?>
                    <!-- Customer Checkout Buttons -->
                    <div class="d-grid gap-3">
                        <!-- Add to Cart (Vibrant Orange) -->
                        <button type="button" class="btn btn-orange py-3 fw-bold fs-6 btn-add-to-cart"
                                data-id="<?= $p['id'] ?>"
                                data-title="<?= $cleanTitle ?>"
                                data-price="<?= $p['price'] ?>"
                                data-image="<?= htmlspecialchars($p['image'] ?? '') ?>"
                                data-cat-icon="<?= htmlspecialchars($p['cat_icon']) ?>"
                                data-seller="<?= $cleanSeller ?>"
                                data-region="<?= $cleanRegion ?>">
                            <i class="bi bi-cart-plus-fill me-2 fs-5"></i> Add to Cart & Buy (GH₵)
                        </button>

                        <!-- Chat with Merchant -->
                        <a href="chat.php?product_id=<?= $p['id'] ?>&seller_id=<?= $p['seller_id'] ?>"
                           class="btn btn-navy py-3 fw-bold" style="font-size:15px;">
                            <i class="bi bi-chat-left-text me-2"></i> Inquire with Student Founder
                        </a>

                        <!-- WhatsApp Hotline -->
                        <?php if (!empty($p['seller_phone'])):
                            $cleanPhone = preg_replace('/[^0-9]/', '', $p['seller_phone']);
                            if (strpos($cleanPhone, '0') === 0) {
                                $cleanPhone = '233' . substr($cleanPhone, 1);
                            }
                        ?>
                        <a href="https://wa.me/<?= $cleanPhone ?>?text=Hello+<?= urlencode($cleanSeller) ?>%2C+I+am+inquiring+about+the+item+%22<?= urlencode($cleanTitle) ?>%22+on+UniMarket+Ghana."
                           target="_blank" class="btn py-3 fw-bold" style="background:#25d366;color:white;border-radius:var(--radius-md);font-size:15px;">
                            <i class="bi bi-whatsapp me-2 fs-5"></i> WhatsApp Student Founder
                        </a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="alert alert-secondary text-center mb-0">
                        <i class="bi bi-x-circle-fill text-danger fs-4 d-block mb-1"></i>
                        This item has already been purchased.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Report Listing Card -->
            <?php if ((int)$p['seller_id'] !== $uid): ?>
            <div class="um-card p-4">
                <button class="btn btn-link text-danger text-decoration-none p-0 fw-bold small" data-bs-toggle="collapse" data-bs-target="#reportForm">
                    <i class="bi bi-flag me-1"></i> Report an Issue or Concern
                </button>

                <div class="collapse mt-3" id="reportForm">
                    <?php if ($report_success): ?>
                    <div class="alert alert-success small mb-0">
                        <i class="bi bi-check-circle me-1"></i> Your report has been submitted to the compliance team for review.
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="um-field-label" style="font-size:12px;">Reason</label>
                            <select name="report_reason" class="form-select um-input py-2" style="font-size:13px;" required>
                                <option value="">Select a reason</option>
                                <option>Inaccurate product specifications</option>
                                <option>Suspected counterfeit item</option>
                                <option>Incorrect price in Ghana Cedis</option>
                                <option>Merchant unresponsive</option>
                                <option>Already sold</option>
                                <option>Other policy violation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="um-field-label" style="font-size:12px;">Additional Details (Optional)</label>
                            <textarea name="report_details" class="form-control um-input" rows="2" placeholder="Provide extra information..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm w-100 py-2 fw-bold">Submit Report</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Other Inventory from this Merchant ── -->
    <?php if ($others && $others->num_rows > 0): ?>
    <div class="mt-5 pt-4 border-top">
        <h4 class="fw-bold mb-4" style="font-size:22px;">More from <?= $cleanSeller ?></h4>
        <div class="row g-3 g-md-4">
            <?php while ($o = $others->fetch_assoc()):
                $oTitle = htmlspecialchars($o['title']);
                $oImg = getProductImageUrl($o['image'] ?? '');
            ?>
            <div class="col-6 col-md-3">
                <div class="um-card h-100">
                    <div class="card-media-box">
                        <a href="product.php?id=<?= $o['id'] ?>" class="w-100 h-100 d-flex align-items-center justify-content-center">
                            <?php if ($oImg): ?>
                                <img src="<?= $oImg ?>" alt="<?= $oTitle ?>" onerror="this.onerror=null; this.src='<?= DEFAULT_PRODUCT_FALLBACK_IMAGE ?>';">
                            <?php else: ?>
                                <div class="card-media-placeholder fs-2"><i class="bi <?= htmlspecialchars($o['cat_icon']) ?> text-secondary"></i></div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="card-main-content p-3">
                        <a href="product.php?id=<?= $o['id'] ?>" class="card-title-link" style="min-height:36px;font-size:13.5px;" title="<?= $oTitle ?>">
                            <?= $oTitle ?>
                        </a>
                        <div class="card-price-value fs-5 mb-0">
                            <?= formatPrice($o['price']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
