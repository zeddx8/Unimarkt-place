<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Saved Items / Wishlist
// Nationwide Commercial E-Commerce Platform
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'Saved Items | UniMarket Ghana';
$uid = (int)$_SESSION['user_id'];

$products = $conn->query("
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon, u.full_name AS seller_name, u.avatar AS seller_avatar
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE w.user_id = $uid
    ORDER BY w.created_at DESC
");

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:50px;height:50px;background:#fee2e2;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;">
                <i class="bi bi-heart-fill text-danger"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-1" style="font-size:24px;color:var(--brand-slate);">Saved Wishlist</h2>
                <p class="text-muted mb-0 small">Items you have bookmarked from student founders and campus drops</p>
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold" style="border-radius:10px;">
            <i class="bi bi-compass me-1"></i> Browse Marketplace
        </a>
    </div>

    <?php if (!$products || $products->num_rows === 0): ?>
    <div class="empty-state-box my-5 text-center">
        <div class="empty-state-icon text-muted mb-2"><i class="bi bi-heart" style="font-size:48px;"></i></div>
        <h4 class="empty-state-title">No saved items yet</h4>
        <p class="empty-state-desc">Click the heart icon on any student listing to bookmark items you're interested in purchasing.</p>
        <a href="dashboard.php" class="btn btn-orange px-4 py-2 fw-bold">
            <i class="bi bi-compass me-1"></i> Explore Campus Drops
        </a>
    </div>
    <?php else: ?>
    <div class="row g-3 g-md-4">
        <?php while ($p = $products->fetch_assoc()):
            $cleanTitle = htmlspecialchars($p['title']);
            $cleanSeller = htmlspecialchars($p['seller_name']);
            $cleanRegion = htmlspecialchars($p['region'] ?? 'Greater Accra Region');
            $imgSrc = !empty($p['image']) ? 'uploads/' . htmlspecialchars($p['image']) : '';
        ?>
        <div class="col-6 col-md-4 col-lg-3 product-card-wrap">
            <div class="um-product-card">
                <!-- Thumbnail -->
                <div class="product-thumb-container">
                    <a href="product.php?id=<?= $p['id'] ?>" class="w-100 h-100 d-flex align-items-center justify-content-center">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= $cleanTitle ?>">
                        <?php else: ?>
                            <div class="product-thumb-fallback"><i class="bi <?= htmlspecialchars($p['cat_icon']) ?> text-secondary fs-3"></i></div>
                        <?php endif; ?>
                    </a>

                    <!-- Remove from saved -->
                    <button type="button" class="floating-wish-btn active" data-id="<?= $p['id'] ?>" title="Remove from wishlist">
                        <i class="bi bi-heart-fill"></i>
                    </button>

                    <?php if ($p['status'] === 'sold'): ?>
                        <div class="sold-overlay"><span class="sold-pill">OUT OF STOCK</span></div>
                    <?php endif; ?>
                </div>

                <div class="card-content">
                    <div class="product-category-lbl"><i class="bi <?= htmlspecialchars($p['cat_icon']) ?> text-primary me-1"></i> <?= htmlspecialchars($p['cat_name']) ?></div>
                    <a href="product.php?id=<?= $p['id'] ?>" class="product-title" title="<?= $cleanTitle ?>">
                        <?= $cleanTitle ?>
                    </a>
                    <div class="product-price-tag mb-2">
                        <?= formatPrice($p['price']) ?>
                    </div>

                    <div class="card-action-bar mt-auto">
                        <?php if ($p['status'] === 'available'): ?>
                        <button type="button" class="btn-add-cart btn-add-to-cart"
                                data-id="<?= $p['id'] ?>"
                                data-title="<?= $cleanTitle ?>"
                                data-price="<?= $p['price'] ?>"
                                data-image="<?= htmlspecialchars($p['image'] ?? '') ?>"
                                data-cat-icon="<?= htmlspecialchars($p['cat_icon']) ?>"
                                data-seller="<?= $cleanSeller ?>"
                                data-region="<?= $cleanRegion ?>">
                            <i class="bi bi-bag-plus"></i> Add to Cart
                        </button>
                        <?php endif; ?>
                        <a href="product.php?id=<?= $p['id'] ?>" class="btn-quick-view <?= ($p['status'] !== 'available') ? 'w-100' : '' ?>" title="View Details">
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
