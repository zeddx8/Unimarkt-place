<?php
require_once '../includes/db.php';
requireAdmin();

// Handle actions
if (isset($_GET['ban'])) {
    $bid = intval($_GET['ban']);
    $conn->query("UPDATE users SET is_banned=1 WHERE id=$bid AND is_admin=0");
    header('Location: users.php?msg=banned'); exit();
}
if (isset($_GET['unban'])) {
    $bid = intval($_GET['unban']);
    $conn->query("UPDATE users SET is_banned=0 WHERE id=$bid");
    header('Location: users.php?msg=unbanned'); exit();
}
if (isset($_GET['verify'])) {
    $vid = intval($_GET['verify']);
    $conn->query("UPDATE users SET is_verified=1 WHERE id=$vid");
    header('Location: users.php?msg=verified'); exit();
}

$search = sanitize($_GET['q'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM products WHERE seller_id=u.id AND status!='removed') AS listing_count FROM users u WHERE u.is_admin=0";
if ($search) $sql .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";
$sql .= " ORDER BY u.created_at DESC";
$users = $conn->query($sql);

$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Merchants | UniMarket Admin</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            <a href="orders.php" class="admin-nav-item">
                <i class="bi bi-receipt"></i> Orders & Shipments
            </a>
            <a href="products.php" class="admin-nav-item">
                <i class="bi bi-box-seam"></i> Products CRUD
            </a>
            <a href="categories.php" class="admin-nav-item">
                <i class="bi bi-tags"></i> Categories CRUD
            </a>
            <a href="users.php" class="admin-nav-item active">
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

    <main class="flex-fill p-4" style="overflow-x:hidden;">
        <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Users & Merchant Accounts</h3>
        <p class="text-muted small mb-4">Manage verified buyers and merchants across all regions in Ghana</p>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>Action completed successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" class="d-flex gap-2 my-3" style="max-width:440px;">
            <input type="text" name="q" class="form-control um-input" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-orange px-4">Search</button>
        </form>

        <div class="um-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:14px;">
                    <thead style="background:#F8FAFC;">
                        <tr><th>User</th><th>Region</th><th>City</th><th>Phone</th><th>Listings</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$users || $users->num_rows === 0): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No users found.</td></tr>
                    <?php endif; ?>
                    <?php while ($users && $u = $users->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);<?= $u['is_banned'] ? 'opacity:.6;' : '' ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="seller-avatar" style="width:36px;height:36px;font-size:13px;border-radius:50%;background:var(--brand-slate);color:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                    <?php if ($u['avatar']): ?>
                                        <img src="<?= htmlspecialchars($u['avatar']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <?= getAvatar($u['full_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= sanitize($u['full_name']) ?></div>
                                    <div class="text-muted small"><?= sanitize($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><?= sanitize($u['region'] ?? 'Greater Accra') ?></td>
                        <td class="text-muted small"><?= sanitize($u['city'] ?? 'Accra') ?></td>
                        <td class="text-muted small"><?= sanitize($u['phone']) ?: '—' ?></td>
                        <td class="fw-bold"><?= $u['listing_count'] ?></td>
                        <td>
                            <?php if ($u['is_banned']): ?>
                                <span class="badge-status-cancelled">BANNED</span>
                            <?php elseif ($u['is_verified']): ?>
                                <span class="badge-status-in-stock">VERIFIED</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">UNVERIFIED</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= timeAgo($u['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if (!$u['is_verified'] && !$u['is_banned']): ?>
                                <a href="?verify=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:11.5px;">Verify</a>
                                <?php endif; ?>
                                <?php if ($u['is_banned']): ?>
                                <a href="?unban=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:11.5px;">Unban</a>
                                <?php else: ?>
                                <a href="?ban=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:11.5px;" onclick="return confirm('Ban this user?')">Ban</a>
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
