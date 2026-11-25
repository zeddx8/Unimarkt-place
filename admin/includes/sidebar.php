<?php
$admin_active_page = basename($_SERVER['PHP_SELF']);
if ($admin_active_page === 'chat.php') {
    $admin_active_page = 'messages.php';
}
$nav_pending_reports = isset($pending_reports) ? (int)$pending_reports : (isset($pending) ? (int)$pending : 0);
$nav_unread_messages = isset($unread_messages) ? (int)$unread_messages : (isset($unread) ? (int)$unread : 0);
?>
<!-- ── MOBILE ADMIN TOP BAR (< md) ─────────────────────────────── -->
<div class="d-md-none d-flex align-items-center justify-content-between px-3 py-3 text-white" style="background:var(--brand-slate);">
    <div class="d-flex align-items-center gap-2">
        <div class="brand-icon-box" style="width:30px;height:30px;font-size:14px;">
            <i class="bi bi-bag-check-fill"></i>
        </div>
        <span class="fw-bold fs-6">Uni<span style="color:var(--accent-orange);">Market</span> <small class="text-white-50 fw-normal">Admin</small></span>
    </div>
    <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileDrawer" aria-controls="adminMobileDrawer">
        <i class="bi bi-list fs-5"></i>
    </button>
</div>

<!-- ── MOBILE ADMIN OFFCANVAS DRAWER ────────────────────────────── -->
<div class="offcanvas offcanvas-start text-white" tabindex="-1" id="adminMobileDrawer" style="background:var(--brand-slate);width:260px;">
    <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-icon-box" style="width:32px;height:32px;font-size:15px;">
                <i class="bi bi-bag-check-fill"></i>
            </div>
            <span class="fw-bold fs-5">Uni<span style="color:var(--accent-orange);">Market</span></span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <nav class="py-3 flex-grow-1">
            <a href="index.php" class="admin-nav-item <?= $admin_active_page === 'index.php' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="orders.php" class="admin-nav-item <?= $admin_active_page === 'orders.php' ? 'active' : '' ?>"><i class="bi bi-receipt"></i> Orders &amp; Shipments</a>
            <a href="products.php" class="admin-nav-item <?= $admin_active_page === 'products.php' ? 'active' : '' ?>"><i class="bi bi-box-seam"></i> Products CRUD</a>
            <a href="categories.php" class="admin-nav-item <?= $admin_active_page === 'categories.php' ? 'active' : '' ?>"><i class="bi bi-tags"></i> Categories CRUD</a>
            <a href="users.php" class="admin-nav-item <?= $admin_active_page === 'users.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Users &amp; Merchants</a>
            <a href="reports.php" class="admin-nav-item <?= $admin_active_page === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-flag"></i> Reports
                <?php if ($nav_pending_reports > 0): ?><span class="badge bg-danger ms-auto"><?= $nav_pending_reports ?></span><?php endif; ?>
            </a>
            <a href="messages.php" class="admin-nav-item <?= $admin_active_page === 'messages.php' ? 'active' : '' ?>">
                <i class="bi bi-chat-dots"></i> Inquiries
                <?php if ($nav_unread_messages > 0): ?><span class="badge bg-warning text-dark ms-auto"><?= $nav_unread_messages ?></span><?php endif; ?>
            </a>
        </nav>
        <div class="p-3 border-top border-secondary border-opacity-25">
            <a href="../dashboard.php" target="_blank" class="admin-nav-item py-2 px-3 rounded-2 text-white-50 mb-1"><i class="bi bi-box-arrow-up-right"></i> View Live Site</a>
            <a href="../logout.php" class="admin-nav-item py-2 px-3 rounded-2 text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
        </div>
    </div>
</div>

<!-- ── DESKTOP SIDEBAR NAVIGATION ──────────────────────────────── -->
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
        <a href="index.php" class="admin-nav-item <?= $admin_active_page === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="orders.php" class="admin-nav-item <?= $admin_active_page === 'orders.php' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Orders &amp; Shipments
        </a>
        <a href="products.php" class="admin-nav-item <?= $admin_active_page === 'products.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Products CRUD
        </a>
        <a href="categories.php" class="admin-nav-item <?= $admin_active_page === 'categories.php' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Categories CRUD
        </a>
        <a href="users.php" class="admin-nav-item <?= $admin_active_page === 'users.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Users &amp; Merchants
        </a>
        <a href="reports.php" class="admin-nav-item <?= $admin_active_page === 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-flag"></i> Reports
            <?php if ($nav_pending_reports > 0): ?>
                <span class="badge bg-danger ms-auto"><?= $nav_pending_reports ?></span>
            <?php endif; ?>
        </a>
        <a href="messages.php" class="admin-nav-item <?= $admin_active_page === 'messages.php' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots"></i> Inquiries
            <?php if ($nav_unread_messages > 0): ?>
                <span class="badge bg-warning text-dark ms-auto"><?= $nav_unread_messages ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="p-3 border-top border-secondary border-opacity-25">
        <a href="../dashboard.php" target="_blank" class="admin-nav-item py-2 px-3 rounded-2 text-white-50 mb-1">
            <i class="bi bi-box-arrow-up-right"></i> View Live Site
        </a>
        <a href="../logout.php" class="admin-nav-item py-2 px-3 rounded-2 text-danger">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</aside>
