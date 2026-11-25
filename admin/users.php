<?php
require_once '../includes/db.php';
requireAdmin();

// Handle actions via POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    requireCsrf('users.php');
    $target_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    if ($target_id > 0) {
        if ($action === 'ban') {
            $st = $conn->prepare("UPDATE users SET is_banned=1 WHERE id=? AND is_admin=0");
            $st->bind_param("i", $target_id);
            $st->execute();
            header('Location: users.php?msg=banned'); exit();
        } elseif ($action === 'unban') {
            $st = $conn->prepare("UPDATE users SET is_banned=0 WHERE id=?");
            $st->bind_param("i", $target_id);
            $st->execute();
            header('Location: users.php?msg=unbanned'); exit();
        } elseif ($action === 'verify') {
            $st = $conn->prepare("UPDATE users SET is_verified=1 WHERE id=?");
            $st->bind_param("i", $target_id);
            $st->execute();
            header('Location: users.php?msg=verified'); exit();
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT u.*, (SELECT COUNT(*) FROM products WHERE seller_id=u.id AND status!='removed') AS listing_count FROM users u WHERE u.is_admin=0";
if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?) ORDER BY u.created_at DESC";
    $like = '%' . $search . '%';
    $u_stmt = $conn->prepare($sql);
    $u_stmt->bind_param("sss", $like, $like, $like);
    $u_stmt->execute();
    $users = $u_stmt->get_result();
} else {
    $sql .= " ORDER BY u.created_at DESC";
    $users = $conn->query($sql);
}

$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Users & Merchants | UniMarket Admin</title>

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
                                        <img src="<?= htmlspecialchars(getAdminImageUrl($u['avatar'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.style.display='none';">
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
                                <form method="POST" action="" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:11.5px;">Verify</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($u['is_banned']): ?>
                                <form method="POST" action="" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="unban">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:11.5px;">Unban</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Ban this user?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="ban">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:11.5px;">Ban</button>
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
