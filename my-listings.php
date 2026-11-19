<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Merchant & Seller Inventory Management
// Nationwide Commercial E-Commerce Platform
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'My Inventory & Listings | UniMarket Ghana';
$uid = (int)$_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';
$where  = "p.seller_id = $uid";
if ($filter === 'available') $where .= " AND p.status = 'available'";
elseif ($filter === 'sold')  $where .= " AND p.status = 'sold'";

$products = $conn->query("
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon
    FROM products p JOIN categories c ON p.category_id=c.id
    WHERE $where ORDER BY p.created_at DESC
");

$total     = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid")->fetch_row()[0] ?? 0;
$available = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid AND status='available'")->fetch_row()[0] ?? 0;
$sold      = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid AND status='sold'")->fetch_row()[0] ?? 0;

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1" style="font-size:24px;color:var(--brand-slate);">Student Venture Inventory & Drops</h2>
            <p class="text-muted mb-0 small">Manage, track, and scale your student business across Ghanaian campuses and nationwide</p>
        </div>
        <a href="post.php" class="btn btn-orange px-4 py-2 fw-bold d-inline-flex align-items-center gap-1">
            <i class="bi bi-rocket-takeoff-fill me-1"></i> Launch New Listing
        </a>
    </div>

    <!-- Filter Metric Cards -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ["bi-rocket-takeoff", $total, "Venture Listings", "all", "text-primary"],
            ["bi-check-circle", $available, "Active & In Stock", "available", "text-success"],
            ["bi-bag-check", $sold, "Campus Orders Fulfilled", "sold", "text-muted"]
        ] as $s): ?>
        <div class="col-4">
            <a href="?filter=<?= $s[3] ?>" class="text-decoration-none d-block">
                <div class="um-product-card p-3 text-center <?= $filter===$s[3]?'border-success':'' ?>" style="<?= $filter===$s[3]?'background:#f0fdf4;':'' ?>">
                    <div style="font-size:24px;margin-bottom:2px;" class="<?= $s[4] ?>"><i class="bi <?= $s[0] ?>"></i></div>
                    <div class="fw-bold fs-4 text-dark"><?= $s[1] ?></div>
                    <div class="small text-muted fw-semibold"><?= $s[2] ?></div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$products || $products->num_rows === 0): ?>
    <div class="empty-state-box my-5 text-center">
        <div class="empty-state-icon text-muted mb-2"><i class="bi bi-rocket-takeoff" style="font-size:48px;"></i></div>
        <h4 class="empty-state-title">No student listings active</h4>
        <p class="empty-state-desc">You do not have any listings in this view. Ready to showcase your products to student peers and customers across Ghana?</p>
        <a href="post.php" class="btn btn-orange px-4 py-2 fw-bold">
            <i class="bi bi-plus-circle me-1"></i> Launch Your First Student Listing
        </a>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php while ($p = $products->fetch_assoc()):
            $cleanTitle = htmlspecialchars($p['title']);
            $imgSrc = !empty($p['image']) ? 'uploads/' . htmlspecialchars($p['image']) : '';
            $location = htmlspecialchars($p['region'] ?? 'Greater Accra Region');
        ?>
        <div class="col-12">
            <div class="um-product-card p-3 d-flex align-items-center gap-3 flex-wrap">
                <!-- Image Thumbnail -->
                <div style="width:76px;height:76px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:32px;overflow:hidden;flex-shrink:0;border:1px solid #e2e8f0;">
                    <?php if ($imgSrc): ?>
                        <img src="<?= $imgSrc ?>" alt="<?= $cleanTitle ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="bi <?= htmlspecialchars($p['cat_icon']) ?> text-secondary"></i>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div class="flex-grow-1" style="min-width:200px;">
                    <a href="product.php?id=<?= $p['id'] ?>" class="fw-bold text-dark d-block text-truncate" style="font-size:15px;max-width:400px;">
                        <?= $cleanTitle ?>
                    </a>
                    <div class="fw-bold text-dark mt-1" style="font-size:16px;">
                        <?= formatPrice($p['price']) ?>
                    </div>
                    <div class="d-flex gap-2 mt-1 align-items-center flex-wrap small text-muted">
                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size:11px;">
                            <?= htmlspecialchars($p['condition_type']) ?>
                        </span>
                        <span><i class="bi bi-geo-alt me-1 text-danger"></i><?= $location ?></span>
                        <span>•</span>
                        <span><i class="bi bi-eye me-1"></i><?= (int)$p['views'] ?> views</span>
                        <span>•</span>
                        <span><?= timeAgo($p['created_at']) ?></span>
                    </div>
                </div>

                <!-- Status Badge -->
                <div>
                    <span class="badge px-3 py-2 fw-bold" style="font-size:11.5px;border-radius:8px;background:<?= $p['status']==='sold'?'#fef2f2':'#ecfdf5' ?>;color:<?= $p['status']==='sold'?'#b91c1c':'#047857' ?>;">
                        <?= strtoupper($p['status']) ?>
                    </span>
                </div>

                <!-- Action Controls -->
                <div class="d-flex gap-2 flex-shrink-0 ms-auto">
                    <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold" style="border-radius:10px;">
                        View
                    </a>
                    <?php if ($p['status'] === 'available'): ?>
                    <a href="mark-sold.php?id=<?= $p['id'] ?>" class="btn btn-outline-success btn-sm px-3 py-2 fw-bold mark-sold-btn" style="border-radius:10px;">
                        Mark Sold
                    </a>
                    <?php endif; ?>
                    <a href="delete-product.php?id=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm px-3 py-2 fw-bold delete-btn" style="border-radius:10px;">
                        <i class="bi bi-trash3"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
