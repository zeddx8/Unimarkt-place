<?php
require_once '../includes/db.php';
requireAdmin();

$messages = $conn->query("
    SELECT m.*, 
           s.full_name AS sender_name, s.email AS sender_email, s.is_admin AS sender_is_admin,
           r.full_name AS receiver_name, r.is_admin AS receiver_is_admin,
           p.title AS product_title, p.id AS product_id
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
    JOIN products p ON m.product_id = p.id
    ORDER BY m.created_at DESC
    LIMIT 100
");

$total    = (int)($conn->query("SELECT COUNT(*) FROM messages")->fetch_row()[0] ?? 0);
$today    = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at)=CURDATE()")->fetch_row()[0] ?? 0);
$unread   = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Messages & Audit | UniMarket Admin</title>

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
        <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Customer & Merchant Inquiries</h3>
        <p class="text-muted small mb-4">Monitor communications, ensure platform safety, and mediate disputes</p>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#E0E7FF;color:#4F46E5;">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= $total ?></div>
                        <div class="small text-muted text-uppercase">Total Messages</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#DCFCE7;color:#16A34A;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= $today ?></div>
                        <div class="small text-muted text-uppercase">Sent Today</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon" style="background:#FEF3C7;color:#D97706;">
                        <i class="bi bi-envelope-open"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= $unread ?></div>
                        <div class="small text-muted text-uppercase">Unread</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="um-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Regarding Product</th>
                            <th>Message Content</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                            <th>Mediate</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$messages || $messages->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No messages recorded yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($messages && $m = $messages->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td>
                            <div class="fw-bold text-dark"><?= sanitize($m['sender_name']) ?></div>
                            <div class="text-muted small"><?= sanitize($m['sender_email']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= sanitize($m['receiver_name']) ?></div>
                        </td>
                        <td>
                            <a href="../product.php?id=<?= $m['product_id'] ?>" target="_blank" class="text-dark fw-semibold text-truncate d-block" style="max-width:180px;">
                                <?= sanitize($m['product_title']) ?>
                            </a>
                        </td>
                        <td>
                            <div class="text-truncate text-secondary" style="max-width:240px;">
                                <?= sanitize($m['message']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $m['is_read'] ? 'bg-light text-muted border' : 'bg-warning text-dark' ?>">
                                <?= $m['is_read'] ? 'Read' : 'Unread' ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= timeAgo($m['created_at']) ?></td>
                        <td>
                            <a href="chat.php?product_id=<?= $m['product_id'] ?>&user_id=<?= $m['sender_id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                <i class="bi bi-chat-left-text me-1"></i> Open
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
