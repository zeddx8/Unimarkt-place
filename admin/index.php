<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Admin Operations Dashboard
// Live KPI Metrics in GH₵, Order Management, & Quick Actions
// ZERO Emojis — Professional Vector Icons & Deep Slate Palette
// ════════════════════════════════════════════════════════════

require_once '../includes/db.php';
requireAdmin();

$page_title = 'Operations Dashboard';

// Handle quick order status update from dashboard
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = sanitize($_POST['new_status'] ?? '');
        if ($order_id > 0 && in_array($new_status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
            $stmt_up = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt_up->bind_param("si", $new_status, $order_id);
            if ($stmt_up->execute()) {
                $msg = 'Order #' . $order_id . ' status successfully updated to ' . strtoupper($new_status) . '.';
            }
        }
    }
}

// ── Live KPI Metrics ──────────────────────────────────────────
// 1. Total Revenue in GH₵ (excluding cancelled orders)
$rev_res = $conn->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'");
$total_revenue = $rev_res ? (float)$rev_res->fetch_row()[0] : 0.00;

// 2. Total Orders
$ord_res = $conn->query("SELECT COUNT(*) FROM orders");
$total_orders = $ord_res ? (int)$ord_res->fetch_row()[0] : 0;

// 3. Active Products in Stock
$prod_res = $conn->query("SELECT COUNT(*) FROM products WHERE status = 'available'");
$active_products = $prod_res ? (int)$prod_res->fetch_row()[0] : 0;

// 4. Registered Users (excluding admins)
$usr_res = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$total_users = $usr_res ? (int)$usr_res->fetch_row()[0] : 0;

// Auxiliary counts for navigation badges
$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
$total_categories = (int)($conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0] ?? 0);

// Recent Orders with line item summaries
$recent_orders = $conn->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o 
    ORDER BY o.created_at DESC 
    LIMIT 6
");

// Recent Products
$recent_products = $conn->query("
    SELECT p.*, u.full_name AS seller_name, c.name AS cat_name, c.icon AS cat_icon 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status != 'removed' 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | UniMarket Operations Console</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body style="background:#FAFAF9;">

<div class="d-flex flex-column flex-md-row" style="min-height:100vh;">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- ── MAIN CONTENT AREA ─────────────────────────────────────── -->
    <main class="flex-fill p-4" style="overflow-x:hidden;">

        <!-- Top Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Operations Dashboard</h3>
                <p class="text-muted small mb-0">Overview of nationwide commercial revenue, live inventory, and orders</p>
            </div>
            <div class="d-flex gap-2">
                <a href="products.php?action=new" class="btn btn-orange btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Add Product
                </a>
                <a href="categories.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-folder-plus me-1"></i> Manage Categories
                </a>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- ══ LIVE KPI SUMMARY CARDS ═══════════════════════════════════ -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Revenue in GH₵ -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#FFEDD5;color:#EA580C;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size:11.5px;letter-spacing:0.5px;">Total Revenue</div>
                        <div class="fw-bold fs-4 text-dark"><?= formatPrice($total_revenue) ?></div>
                        <span class="small text-success fw-semibold"><i class="bi bi-arrow-up-right me-1"></i>Live Settlements</span>
                    </div>
                </div>
            </div>

            <!-- 2. Total Orders -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#DCFCE7;color:#16A34A;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size:11.5px;letter-spacing:0.5px;">Total Orders</div>
                        <div class="fw-bold fs-4 text-dark"><?= number_format($total_orders) ?></div>
                        <span class="small text-muted">Nationwide Fulfillment</span>
                    </div>
                </div>
            </div>

            <!-- 3. Active Products in Stock -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#E0E7FF;color:#4F46E5;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size:11.5px;letter-spacing:0.5px;">Active Products</div>
                        <div class="fw-bold fs-4 text-dark"><?= number_format($active_products) ?></div>
                        <span class="small text-muted">In Stock & Verified</span>
                    </div>
                </div>
            </div>

            <!-- 4. Registered Users -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#FEF3C7;color:#D97706;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size:11.5px;letter-spacing:0.5px;">Registered Users</div>
                        <div class="fw-bold fs-4 text-dark"><?= number_format($total_users) ?></div>
                        <span class="small text-muted">Customers & Sellers</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ ORDER MANAGEMENT TABLE ═══════════════════════════════════ -->
        <div class="um-card mb-4 overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#FFFFFF;">
                <div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size:16px;">
                        <i class="bi bi-clock-history me-2" style="color:var(--accent-orange);"></i>Recent Customer Orders
                    </h5>
                    <span class="text-muted small">Update shipment and fulfillment statuses across Ghana</span>
                </div>
                <a href="orders.php" class="btn btn-outline-secondary btn-sm">
                    View All Orders <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th>Order Code</th>
                            <th>Customer & Phone</th>
                            <th>Delivery Location</th>
                            <th>Method</th>
                            <th>Total (GH₵)</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recent_orders || $recent_orders->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No orders placed yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php while ($ord = $recent_orders->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td>
                            <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($ord['order_code']) ?></span>
                            <div class="text-muted small"><?= (int)$ord['item_count'] ?> item(s)</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($ord['customer_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($ord['phone']) ?></div>
                        </td>
                        <td>
                            <div class="small fw-semibold"><?= htmlspecialchars($ord['region']) ?></div>
                            <div class="text-muted small text-truncate" style="max-width:160px;"><?= htmlspecialchars($ord['delivery_address']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= strtoupper(str_replace('_', ' ', $ord['payment_method'])) ?>
                            </span>
                        </td>
                        <td class="fw-bold" style="color:var(--brand-slate);">
                            <?= formatPrice($ord['total']) ?>
                        </td>
                        <td>
                            <?php
                            $st = $ord['status'];
                            $badgeClass = 'badge-status-pending';
                            if ($st === 'delivered') $badgeClass = 'badge-status-delivered';
                            elseif ($st === 'shipped') $badgeClass = 'badge-status-in-stock';
                            elseif ($st === 'processing') $badgeClass = 'badge-status-processing';
                            elseif ($st === 'cancelled') $badgeClass = 'badge-status-cancelled';
                            ?>
                            <span class="<?= $badgeClass ?>">
                                <?= strtoupper($st) ?>
                            </span>
                        </td>
                        <td>
                            <!-- Inline Status Update Form -->
                            <form method="POST" action="" class="d-flex align-items-center gap-1">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                <select name="new_status" class="form-select form-select-sm py-1" style="font-size:12px;width:120px;" onchange="this.form.submit()">
                                    <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s_opt): ?>
                                    <option value="<?= $s_opt ?>" <?= $ord['status'] === $s_opt ? 'selected' : '' ?>>
                                        <?= ucfirst($s_opt) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="text-muted small">
                            <?= timeAgo($ord['created_at']) ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ RECENT PRODUCTS INVENTORY ════════════════════════════════ -->
        <div class="um-card overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background:#FFFFFF;">
                <div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size:16px;">
                        <i class="bi bi-box-seam me-2" style="color:var(--accent-orange);"></i>Recent Product Listings
                    </h5>
                    <span class="text-muted small">Manage commercial catalog, price points, and active inventory</span>
                </div>
                <a href="products.php" class="btn btn-outline-secondary btn-sm">
                    Manage All Products <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Merchant</th>
                            <th>Region</th>
                            <th>Price (GH₵)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($prod = $recent_products->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:40px;height:40px;background:#F1F5F9;border-radius:8px;overflow:hidden;border:1px solid var(--border-color);flex-shrink:0;">
                                    <?php $dashImg = getAdminImageUrl($prod['image'] ?? ''); ?>
                                    <?php if ($dashImg): ?>
                                        <img src="<?= htmlspecialchars($dashImg) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.src='<?= DEFAULT_PRODUCT_FALLBACK_IMAGE ?>';">
                                    <?php else: ?>
                                        <i class="bi <?= htmlspecialchars($prod['cat_icon'] ?? 'bi-box') ?> text-muted p-2 d-block"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-bold text-dark text-truncate" style="max-width:260px;">
                                    <?= htmlspecialchars($prod['title']) ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi <?= htmlspecialchars($prod['cat_icon'] ?? 'bi-tag') ?> me-1"></i>
                                <?= htmlspecialchars($prod['cat_name']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($prod['seller_name']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($prod['region']) ?></td>
                        <td class="fw-bold" style="color:var(--accent-orange);"><?= formatPrice($prod['price']) ?></td>
                        <td>
                            <span class="badge <?= $prod['status']==='available' ? 'badge-status-in-stock' : 'badge-status-cancelled' ?>">
                                <?= strtoupper($prod['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="products.php?action=edit&id=<?= $prod['id'] ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Edit Product">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
