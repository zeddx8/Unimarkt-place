<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Buyer Order History & Tracking
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'My Orders & Tracking';
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$orders = $stmt->get_result();

$item_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");

include 'includes/header.php';
?>

<div class="container py-5" style="max-width: 960px;">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1" style="font-size:26px;color:var(--brand-slate);">My Orders &amp; Deliveries</h2>
            <p class="text-muted small mb-0">Track your campus purchases, Mobile Money settlements, and delivery status</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3 py-2" style="border-radius:var(--radius-md);">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
        </a>
    </div>

    <?php if (!$orders || $orders->num_rows === 0): ?>
    <div class="um-card p-5 text-center my-4">
        <div style="font-size:52px;margin-bottom:12px;" class="text-muted"><i class="bi bi-receipt"></i></div>
        <h4 class="fw-bold text-dark mb-2">No orders placed yet</h4>
        <p class="text-muted small mb-4" style="max-width:380px;margin:0 auto 20px;">
            Once you complete a purchase on UniMarket Ghana, your order reference and live delivery status will appear here.
        </p>
        <a href="dashboard.php" class="btn btn-orange px-4 py-2 fw-bold">
            <i class="bi bi-compass me-1"></i> Explore Campus Listings
        </a>
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php while ($ord = $orders->fetch_assoc()):
            $st = $ord['status'] ?? 'pending';
            $badgeClass = 'badge-status-pending';
            if ($st === 'delivered') $badgeClass = 'badge-status-delivered';
            elseif ($st === 'shipped') $badgeClass = 'badge-status-in-stock';
            elseif ($st === 'processing') $badgeClass = 'badge-status-processing';
            elseif ($st === 'cancelled') $badgeClass = 'badge-status-cancelled';

            $oid = (int)$ord['id'];
            $line_items = [];
            if ($item_stmt) {
                $item_stmt->bind_param("i", $oid);
                $item_stmt->execute();
                $line_items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        ?>
        <div class="um-card p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 pb-3 border-bottom mb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold font-monospace fs-6 text-dark"><?= htmlspecialchars($ord['order_code']) ?></span>
                        <span class="<?= $badgeClass ?>"><?= strtoupper(htmlspecialchars($st)) ?></span>
                    </div>
                    <div class="text-muted small mt-1">
                        Placed <?= timeAgo($ord['created_at']) ?> · <?= date('M j, Y · g:i A', strtotime($ord['created_at'])) ?>
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="text-muted small">Total Paid</div>
                    <div class="fw-bold fs-4 text-dark"><?= formatPrice($ord['total']) ?></div>
                </div>
            </div>

            <div class="row g-3 mb-3 small">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Delivery Destination</div>
                        <div class="text-muted"><?= htmlspecialchars($ord['delivery_address']) ?>, <?= htmlspecialchars($ord['city']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($ord['region']) ?> · <?= htmlspecialchars($ord['phone']) ?></div>
                        <div class="text-dark fw-semibold mt-1">
                            <?= htmlspecialchars(!empty($ord['delivery_tier']) ? $ord['delivery_tier'] : 'Standard Delivery') ?> — <?= formatPrice($ord['delivery_fee'] ?? 0) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-credit-card me-1 text-primary"></i> Payment Details</div>
                        <div class="text-muted"><?= strtoupper(str_replace('_', ' ', htmlspecialchars($ord['payment_method']))) ?></div>
                        <?php if (!empty($ord['momo_number'])): ?>
                        <div class="text-muted font-monospace">MoMo: <?= htmlspecialchars($ord['momo_number']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($line_items)): ?>
            <div class="border-top pt-3">
                <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:0.5px;">Items (<?= count($line_items) ?>)</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($line_items as $li): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <div>
                            <a href="product.php?id=<?= (int)$li['product_id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                <?= htmlspecialchars($li['title']) ?>
                            </a>
                            <div class="text-muted small">Qty: <?= (int)$li['quantity'] ?> × <?= formatPrice($li['price']) ?></div>
                        </div>
                        <span class="fw-bold text-dark"><?= formatPrice($li['price'] * $li['quantity']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
