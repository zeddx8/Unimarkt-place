<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Admin Orders Management
// Status Updates, Shipping Details, and Line Item Inspection
// ZERO Emojis — Professional Vector Icons
// ════════════════════════════════════════════════════════════

require_once '../includes/db.php';
requireAdmin();

$page_title = 'Orders & Shipments Management';

$msg = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    if ($order_id > 0 && in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            $msg = 'Order #' . $order_id . ' status successfully updated to ' . strtoupper($status) . '.';
        } else {
            $error = 'Failed to update order status: ' . $conn->error;
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = sanitize($_GET['q'] ?? '');

$where = "1=1";
if ($filter !== 'all' && in_array($filter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
    $where .= " AND o.status = '$filter'";
}
if ($search) {
    $where .= " AND (o.order_code LIKE '%$search%' OR o.customer_name LIKE '%$search%' OR o.phone LIKE '%$search%' OR o.city LIKE '%$search%')";
}

// Fetch orders with items
$orders = $conn->query("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    WHERE $where
    ORDER BY o.created_at DESC
");

// Counts for filter tabs
$counts = [
    'all'        => (int)($conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0] ?? 0),
    'pending'    => (int)($conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0] ?? 0),
    'processing' => (int)($conn->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetch_row()[0] ?? 0),
    'shipped'    => (int)($conn->query("SELECT COUNT(*) FROM orders WHERE status='shipped'")->fetch_row()[0] ?? 0),
    'delivered'  => (int)($conn->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetch_row()[0] ?? 0),
    'cancelled'  => (int)($conn->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetch_row()[0] ?? 0),
];

$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders & Shipments | UniMarket Admin</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body style="background:#FAFAF9;">

<div class="d-flex" style="min-height:100vh;">

    <!-- ── SIDEBAR NAVIGATION ────────────────────────────────────── -->
    <aside class="admin-sidebar-nav d-none d-md-flex">
        <div class="px-4 py-4 border-bottom border-secondary border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                <div class="brand-icon-box" style="width:34px;height:34px;font-size:16px;">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-white fs-5 leading-none">
                        Uni<span style="color:var(--accent-orange);">Market</span>
                    </div>
                    <span class="small text-white-50" style="font-size:11px;">Admin Console</span>
                </div>
            </div>
        </div>

        <nav class="py-3 flex-grow-1">
            <a href="index.php" class="admin-nav-item">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="orders.php" class="admin-nav-item active">
                <i class="bi bi-receipt"></i> Orders & Shipments
            </a>
            <a href="products.php" class="admin-nav-item">
                <i class="bi bi-box-seam"></i> Products CRUD
            </a>
            <a href="categories.php" class="admin-nav-item">
                <i class="bi bi-tags"></i> Categories CRUD
            </a>
            <a href="users.php" class="admin-nav-item">
                <i class="bi bi-people"></i> Users & Merchants
            </a>
            <a href="reports.php" class="admin-nav-item">
                <i class="bi bi-flag"></i> Reports
                <?php if ($pending_reports > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= $pending_reports ?></span>
                <?php endif; ?>
            </a>
            <a href="messages.php" class="admin-nav-item">
                <i class="bi bi-chat-dots"></i> Inquiries
                <?php if ($unread_messages > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto"><?= $unread_messages ?></span>
                <?php endif; ?>
            </a>
        </nav>

        <div class="p-3 border-top border-secondary border-opacity-25">
            <a href="../dashboard.php" target="_blank" class="admin-nav-item py-2 px-3 rounded-2 text-white-50 mb-1">
                <i class="bi bi-box-arrow-up-right"></i> View Live Site
            </a>
            <a href="../logout.php" class="admin-nav-link" style="color:#f87171;padding:10px 20px;">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- ── MAIN CONTENT ──────────────────────────────────────────── -->
    <main class="flex-fill p-4" style="overflow-x:hidden;">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Customer Orders & Logistics</h3>
                <p class="text-muted small mb-0">Track payments, customer delivery addresses, and fulfillment</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filter Tabs & Search -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach (['all'=>'All Orders','pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $k => $lbl): ?>
                <a href="?filter=<?= $k ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                   class="btn btn-sm <?= $filter === $k ? 'btn-slate' : 'btn-outline-secondary' ?>">
                    <?= $lbl ?> <span class="badge bg-light text-dark ms-1"><?= $counts[$k] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <input type="text" name="q" class="form-control form-control-sm" style="min-width:240px;border-radius:8px;"
                       placeholder="Search by code, customer, phone..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-orange btn-sm px-3">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="um-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th>Order Code</th>
                            <th>Customer Info</th>
                            <th>Delivery Destination</th>
                            <th>Payment</th>
                            <th>Total (GH₵)</th>
                            <th>Status</th>
                            <th>Actions</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$orders || $orders->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No orders found matching your filters.</td>
                    </tr>
                    <?php else: ?>
                    <?php while ($ord = $orders->fetch_assoc()):
                        $order_id = $ord['id'];
                        $items_res = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");
                    ?>
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
                            <div class="text-muted small text-truncate" style="max-width:180px;"><?= htmlspecialchars($ord['delivery_address']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= strtoupper(str_replace('_', ' ', $ord['payment_method'])) ?>
                            </span>
                            <?php if ($ord['momo_number']): ?>
                                <div class="text-muted small font-monospace"><?= htmlspecialchars($ord['momo_number']) ?></div>
                            <?php endif; ?>
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
                            <span class="<?= $badgeClass ?>"><?= strtoupper($st) ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <!-- Status change dropdown -->
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                    <select name="status" class="form-select form-select-sm py-1" style="font-size:12px;width:120px;" onchange="this.form.submit()">
                                        <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $ord['status'] === $opt ? 'selected' : '' ?>>
                                            <?= ucfirst($opt) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>

                                <!-- View Details Modal Trigger -->
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#orderModal<?= $ord['id'] ?>" title="View Order Items">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                            <!-- Modal for Order Items -->
                            <div class="modal fade" id="orderModal<?= $ord['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
                                        <div class="modal-header bg-light">
                                            <h6 class="modal-title fw-bold">Order Details: <?= htmlspecialchars($ord['order_code']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3 p-3 bg-light rounded-3 small">
                                                <div><strong>Customer:</strong> <?= htmlspecialchars($ord['customer_name']) ?> (<?= htmlspecialchars($ord['phone']) ?>)</div>
                                                <div><strong>Delivery Destination:</strong> <?= htmlspecialchars($ord['delivery_address']) ?>, <?= htmlspecialchars($ord['region']) ?></div>
                                                <div><strong>Payment Method:</strong> <?= strtoupper(str_replace('_',' ',$ord['payment_method'])) ?></div>
                                            </div>
                                            <h6 class="fw-bold mb-2 small text-uppercase text-muted">Purchased Line Items</h6>
                                            <ul class="list-group list-group-flush mb-3">
                                                <?php while ($item = $items_res->fetch_assoc()): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <div>
                                                        <div class="fw-bold small"><?= htmlspecialchars($item['title']) ?></div>
                                                        <div class="text-muted small">Qty: <?= (int)$item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                                                    </div>
                                                    <span class="fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                                                </li>
                                                <?php endwhile; ?>
                                            </ul>
                                            <div class="d-flex justify-content-between pt-2 border-top fw-bold">
                                                <span>Grand Total:</span>
                                                <span class="text-primary"><?= formatPrice($ord['total']) ?></span>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
