<?php
require_once '../includes/db.php';
requireAdmin();

// Handle report moderation via POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireCsrf('reports.php');
    $filter_redirect = in_array($_POST['filter'] ?? 'pending', ['all', 'pending', 'reviewed', 'resolved'], true) ? $_POST['filter'] : 'pending';
    if ($_POST['action'] === 'resolve') {
        $rid = intval($_POST['rid'] ?? 0);
        if ($rid > 0) {
            $st = $conn->prepare("UPDATE reports SET status='resolved' WHERE id=?");
            $st->bind_param("i", $rid);
            $st->execute();
        }
        header('Location: reports.php?msg=resolved&filter=' . urlencode($filter_redirect)); exit();
    } elseif ($_POST['action'] === 'remove_product') {
        $pid = intval($_POST['pid'] ?? 0);
        $rid = intval($_POST['rid'] ?? 0);
        if ($pid > 0) {
            $st1 = $conn->prepare("UPDATE products SET status='removed' WHERE id=?");
            $st1->bind_param("i", $pid);
            $st1->execute();
        }
        if ($rid > 0) {
            $st2 = $conn->prepare("UPDATE reports SET status='reviewed' WHERE id=?");
            $st2->bind_param("i", $rid);
            $st2->execute();
        }
        header('Location: reports.php?msg=product_removed&filter=' . urlencode($filter_redirect)); exit();
    }
}

$filter  = $_GET['filter'] ?? 'pending';
if (!is_string($filter) || !in_array($filter, ['all', 'pending', 'reviewed', 'resolved'], true)) {
    $filter = 'pending';
}
$reports_sql = "
    SELECT r.*, u.full_name AS reporter_name, u.email AS reporter_email,
           p.title AS product_title, p.id AS product_id, p.status AS product_status,
           s.full_name AS seller_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    JOIN products p ON r.product_id = p.id
    JOIN users s ON p.seller_id = s.id
";
if ($filter === 'all') {
    $reports = $conn->query($reports_sql . " ORDER BY r.created_at DESC");
} else {
    $r_stmt = $conn->prepare($reports_sql . " WHERE r.status = ? ORDER BY r.created_at DESC");
    $r_stmt->bind_param("s", $filter);
    $r_stmt->execute();
    $reports = $r_stmt->get_result();
}

$pending  = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$reviewed = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='reviewed'")->fetch_row()[0] ?? 0);
$resolved = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='resolved'")->fetch_row()[0] ?? 0);

$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dispute Reports | UniMarket Admin</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body style="background:#FAFAF9;">

<div class="d-flex flex-column flex-md-row" style="min-height:100vh;">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-fill p-4" style="overflow-x:hidden;">
        <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Customer Reports & Safety</h3>
        <p class="text-muted small mb-4">Review reported listings, investigate grievances, and enforce buyer safety</p>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $_GET['msg'] === 'product_removed' ? 'Product listing removed and report marked as reviewed.' : 'Report resolved successfully.' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 my-3">
            <div class="col-4">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#FEE2E2;color:#DC2626;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= $pending ?></div>
                        <div class="small text-muted text-uppercase">Pending</div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#FEF3C7;color:#D97706;">
                        <i class="bi bi-search"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= $reviewed ?></div>
                        <div class="small text-muted text-uppercase">Reviewed</div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#DCFCE7;color:#16A34A;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= $resolved ?></div>
                        <div class="small text-muted text-uppercase">Resolved</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="d-flex gap-2 mb-3">
            <?php foreach (['pending'=>'Pending','reviewed'=>'Reviewed','resolved'=>'Resolved','all'=>'All Reports'] as $k => $lbl): ?>
            <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter===$k ? 'btn-slate' : 'btn-outline-secondary' ?>">
                <?= $lbl ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="um-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr><th>Product</th><th>Seller</th><th>Reported By</th><th>Reason</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$reports || $reports->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No reports found under this filter.</td></tr>
                    <?php endif; ?>
                    <?php while ($reports && $r = $reports->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td>
                            <a href="../product.php?id=<?= $r['product_id'] ?>" target="_blank" class="fw-bold text-dark text-truncate d-block" style="max-width:200px;">
                                <?= sanitize($r['product_title']) ?>
                            </a>
                            <span class="badge <?= $r['product_status']==='removed' ? 'bg-danger' : 'bg-success' ?> small">
                                <?= strtoupper($r['product_status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= sanitize($r['seller_name']) ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= sanitize($r['reporter_name']) ?></div>
                            <div class="text-muted small"><?= sanitize($r['reporter_email']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-danger border"><?= sanitize($r['reason']) ?></span>
                            <?php if ($r['details']): ?>
                                <div class="text-muted small mt-1"><?= sanitize($r['details']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $r['status']==='pending' ? 'badge-status-cancelled' : ($r['status']==='reviewed' ? 'badge-status-processing' : 'badge-status-delivered') ?>">
                                <?= strtoupper($r['status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= timeAgo($r['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if ($r['status'] !== 'resolved'): ?>
                                <form method="POST" action="" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="resolve">
                                    <input type="hidden" name="rid" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:11px;">Resolve</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['product_status'] !== 'removed'): ?>
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Remove this product listing?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="remove_product">
                                    <input type="hidden" name="pid" value="<?= (int)$r['product_id'] ?>">
                                    <input type="hidden" name="rid" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:11px;">Remove Item</button>
                                </form>
                                <?php endif; ?>
                            </div>
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
