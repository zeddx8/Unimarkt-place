<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Admin Products Full CRUD Management
// Create, Read, Update, and Delete Commercial Listings
// ZERO Emojis — Professional Vector Icons
// ════════════════════════════════════════════════════════════

require_once '../includes/db.php';
requireAdmin();

$page_title = 'Products Catalog CRUD';

$msg = '';
$error = '';

$admin_id = (int)$_SESSION['user_id'];

// ── 1. HANDLE CREATE PRODUCT ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_product') {
    $title      = sanitize($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price      = floatval($_POST['price'] ?? 0);
    $condition  = sanitize($_POST['condition_type'] ?? 'New');
    $region     = sanitize($_POST['region'] ?? 'Greater Accra Region');
    $desc       = sanitize($_POST['description'] ?? '');
    $image_url  = sanitize($_POST['image_url'] ?? '');
    $seller_id  = $admin_id;

    // Handle file upload if provided
    if (!empty($_FILES['image_file']['name'])) {
        $upload = storeImageUpload($_FILES['image_file'], 'prod_', 5 * 1024 * 1024);
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($upload['filename']) {
            $image_url = 'uploads/' . $upload['filename'];
        }
    }

    if (empty($title) || $price <= 0 || $category_id <= 0) {
        $error = 'Please provide a valid title, category, and positive price.';
    } elseif (empty($error)) {
        $stmt_ins = $conn->prepare("INSERT INTO products (seller_id, category_id, title, description, price, condition_type, region, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')");
        $stmt_ins->bind_param("iissdsss", $seller_id, $category_id, $title, $desc, $price, $condition, $region, $image_url);
        if ($stmt_ins->execute()) {
            $msg = 'Product "' . htmlspecialchars($title) . '" created successfully!';
        } else {
            $error = 'Could not create product: ' . $stmt_ins->error;
        }
    }
}

// ── 2. HANDLE UPDATE PRODUCT ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product') {
    $prod_id    = intval($_POST['product_id'] ?? 0);
    $title      = sanitize($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price      = floatval($_POST['price'] ?? 0);
    $condition  = sanitize($_POST['condition_type'] ?? 'New');
    $region     = sanitize($_POST['region'] ?? 'Greater Accra Region');
    $status     = sanitize($_POST['status'] ?? 'available');
    $desc       = sanitize($_POST['description'] ?? '');
    $image_url  = sanitize($_POST['image_url'] ?? '');

    // Handle file upload if provided
    if (!empty($_FILES['image_file']['name'])) {
        $upload = storeImageUpload($_FILES['image_file'], 'prod_', 5 * 1024 * 1024);
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($upload['filename']) {
            $image_url = 'uploads/' . $upload['filename'];
        }
    }

    if ($prod_id <= 0 || empty($title) || $price <= 0 || $category_id <= 0) {
        $error = 'Please provide valid required fields for updating the product.';
    } elseif (empty($error)) {
        if ($image_url !== '') {
            $stmt_up = $conn->prepare("UPDATE products SET title=?, category_id=?, price=?, condition_type=?, region=?, status=?, description=?, image=? WHERE id=?");
            $stmt_up->bind_param("sidsssssi", $title, $category_id, $price, $condition, $region, $status, $desc, $image_url, $prod_id);
        } else {
            $stmt_up = $conn->prepare("UPDATE products SET title=?, category_id=?, price=?, condition_type=?, region=?, status=?, description=? WHERE id=?");
            $stmt_up->bind_param("sidssssi", $title, $category_id, $price, $condition, $region, $status, $desc, $prod_id);
        }
        if ($stmt_up->execute()) {
            $msg = 'Product #' . $prod_id . ' updated successfully!';
        } else {
            $error = 'Could not update product: ' . $stmt_up->error;
        }
    }
}

// ── 3. HANDLE DELETE PRODUCT ─────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id > 0) {
        $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->bind_param("i", $del_id);
        if ($stmt_del->execute()) {
            $msg = 'Product #' . $del_id . ' permanently deleted.';
        } else {
            $error = 'Delete failed: ' . $conn->error;
        }
    }
}

// ── 4. HANDLE SOFT-DELETE / REMOVE / RESTORE ─────────────────
if (isset($_GET['status_toggle'])) {
    $toggle_id = intval($_GET['id'] ?? 0);
    $new_st = sanitize($_GET['to'] ?? 'available');
    if ($toggle_id > 0 && in_array($new_st, ['available', 'sold', 'removed'], true)) {
        $conn->query("UPDATE products SET status='$new_st' WHERE id=$toggle_id");
        $msg = 'Product #' . $toggle_id . ' status changed to ' . strtoupper($new_st) . '.';
    }
}

// Fetch categories & regions for dropdowns
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$regions = getGhanaCampuses();

// Filter & Search
$search   = sanitize($_GET['q'] ?? '');
$filter   = $_GET['filter'] ?? 'all';
$cat_filt = intval($_GET['cat'] ?? 0);

$where = "1=1";
if ($filter === 'available') $where .= " AND p.status = 'available'";
elseif ($filter === 'sold')  $where .= " AND p.status = 'sold'";
elseif ($filter === 'removed') $where .= " AND p.status = 'removed'";

if ($cat_filt > 0) {
    $where .= " AND p.category_id = $cat_filt";
}
if ($search) {
    $where .= " AND (p.title LIKE '%$search%' OR u.full_name LIKE '%$search%' OR p.region LIKE '%$search%')";
}

$products = $conn->query("
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon, u.full_name AS seller_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE $where
    ORDER BY p.created_at DESC
");

$counts = [
    'all'       => (int)($conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0] ?? 0),
    'available' => (int)($conn->query("SELECT COUNT(*) FROM products WHERE status='available'")->fetch_row()[0] ?? 0),
    'sold'      => (int)($conn->query("SELECT COUNT(*) FROM products WHERE status='sold'")->fetch_row()[0] ?? 0),
    'removed'   => (int)($conn->query("SELECT COUNT(*) FROM products WHERE status='removed'")->fetch_row()[0] ?? 0),
];

$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Catalog CRUD | UniMarket Admin</title>

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
            <a href="orders.php" class="admin-nav-item">
                <i class="bi bi-receipt"></i> Orders & Shipments
            </a>
            <a href="products.php" class="admin-nav-item active">
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
                <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Product Catalog Management (CRUD)</h3>
                <p class="text-muted small mb-0">Create new listings, update prices, manage inventory and categories</p>
            </div>
            <!-- Create Product Modal Trigger -->
            <button type="button" class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#createProductModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Product
            </button>
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
                <?php foreach (['all'=>'All Products','available'=>'Available','sold'=>'Sold','removed'=>'Removed'] as $k => $lbl): ?>
                <a href="?filter=<?= $k ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                   class="btn btn-sm <?= $filter === $k ? 'btn-slate' : 'btn-outline-secondary' ?>">
                    <?= $lbl ?> <span class="badge bg-light text-dark ms-1"><?= $counts[$k] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <input type="text" name="q" class="form-control form-control-sm" style="min-width:240px;border-radius:8px;"
                       placeholder="Search products, seller, region..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-orange btn-sm px-3">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <!-- Products Table -->
        <div class="um-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Seller</th>
                            <th>Region</th>
                            <th>Price (GH₵)</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$products || $products->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No products found matching the criteria.</td>
                    </tr>
                    <?php else: ?>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:44px;height:44px;background:#F1F5F9;border-radius:8px;overflow:hidden;border:1px solid var(--border-color);flex-shrink:0;">
                                    <?php if ($p['image']): ?>
                                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <i class="bi <?= htmlspecialchars($p['cat_icon'] ?? 'bi-box') ?> text-muted p-2 d-block"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="../product.php?id=<?= $p['id'] ?>" target="_blank" class="fw-bold text-dark d-block text-truncate" style="max-width:260px;">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </a>
                                    <span class="text-muted small"><?= (int)$p['views'] ?> views · <?= timeAgo($p['created_at']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi <?= htmlspecialchars($p['cat_icon'] ?? 'bi-tag') ?> me-1"></i>
                                <?= htmlspecialchars($p['cat_name']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($p['seller_name']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($p['region']) ?></td>
                        <td class="fw-bold" style="color:var(--accent-orange);"><?= formatPrice($p['price']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:11px;">
                                <?= htmlspecialchars($p['condition_type']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['status'] === 'available'): ?>
                                <span class="badge-status-in-stock">AVAILABLE</span>
                            <?php elseif ($p['status'] === 'sold'): ?>
                                <span class="badge-status-pending">SOLD</span>
                            <?php else: ?>
                                <span class="badge-status-cancelled">REMOVED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <!-- Edit Button Modal Trigger -->
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2"
                                        data-bs-toggle="modal" data-bs-target="#editProductModal<?= $p['id'] ?>" title="Edit Product">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <?php if ($p['status'] === 'removed'): ?>
                                <a href="?status_toggle=1&id=<?= $p['id'] ?>&to=available" class="btn btn-outline-success btn-sm py-1 px-2" title="Restore Product">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                                <?php else: ?>
                                <a href="?status_toggle=1&id=<?= $p['id'] ?>&to=removed" class="btn btn-outline-warning btn-sm py-1 px-2" title="Soft Remove">
                                    <i class="bi bi-slash-circle"></i>
                                </a>
                                <?php endif; ?>

                                <!-- Hard Delete -->
                                <a href="?delete=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm py-1 px-2"
                                   onclick="return confirm('Are you sure you want to permanently delete this listing?')" title="Delete Permanently">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>

                            <!-- ══ MODAL FOR EDITING PRODUCT ═════════════════ -->
                            <div class="modal fade" id="editProductModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
                                        <form method="POST" action="" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="update_product">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <div class="modal-header bg-light">
                                                <h6 class="modal-title fw-bold">Edit Product #<?= $p['id'] ?>: <?= htmlspecialchars($p['title']) ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="um-field-label">Product Title</label>
                                                        <input type="text" name="title" class="form-control um-input" value="<?= htmlspecialchars($p['title']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="um-field-label">Category</label>
                                                        <select name="category_id" class="form-select um-input" required>
                                                            <?php foreach ($categories as $cat): ?>
                                                            <option value="<?= $cat['id'] ?>" <?= $p['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($cat['name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="um-field-label">Price in Ghana Cedis (GH₵)</label>
                                                        <input type="number" step="0.01" name="price" class="form-control um-input" value="<?= htmlspecialchars($p['price']) ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="um-field-label">Condition</label>
                                                        <select name="condition_type" class="form-select um-input" required>
                                                            <?php foreach (['New', 'Like New', 'Good', 'Refurbished'] as $cond): ?>
                                                            <option value="<?= $cond ?>" <?= $p['condition_type'] === $cond ? 'selected' : '' ?>><?= $cond ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="um-field-label">Campus / University Institution <span class="text-danger">*</span></label>
                                                        <select name="region" class="form-select um-input" required>
                                                            <?php foreach ($regions as $r): ?>
                                                            <option value="<?= htmlspecialchars($r) ?>" <?= $p['region'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="um-field-label">Status</label>
                                                        <select name="status" class="form-select um-input" required>
                                                            <option value="available" <?= $p['status'] === 'available' ? 'selected' : '' ?>>Available (In Stock)</option>
                                                            <option value="sold" <?= $p['status'] === 'sold' ? 'selected' : '' ?>>Sold Out</option>
                                                            <option value="removed" <?= $p['status'] === 'removed' ? 'selected' : '' ?>>Removed</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <label class="um-field-label">Product Image URL (e.g. Unsplash or CDN)</label>
                                                        <input type="url" name="image_url" class="form-control um-input" value="<?= htmlspecialchars($p['image']) ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="um-field-label">Or Upload New File</label>
                                                        <input type="file" name="image_file" class="form-control um-input" accept="image/*">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="um-field-label">Product Description</label>
                                                        <textarea name="description" class="form-control um-input" rows="4"><?= htmlspecialchars($p['description']) ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-orange btn-sm fw-bold">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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

<!-- ══ MODAL FOR CREATING NEW PRODUCT ═══════════════════════════ -->
<div class="modal fade" id="createProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_product">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold">Create New Commercial Product Listing</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="um-field-label">Product Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control um-input" placeholder="e.g. Samsung 65-Inch 4K UHD Smart Television..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="um-field-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select um-input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="um-field-label">Price in Ghana Cedis (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control um-input" placeholder="e.g. 1450.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="um-field-label">Condition <span class="text-danger">*</span></label>
                            <select name="condition_type" class="form-select um-input" required>
                                <option value="New">New</option>
                                <option value="Like New">Like New</option>
                                <option value="Good">Good</option>
                                <option value="Refurbished">Refurbished</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="um-field-label">Campus / University Institution <span class="text-danger">*</span></label>
                            <select name="region" class="form-select um-input" required>
                                <?php foreach ($regions as $r): ?>
                                <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="um-field-label">Image URL (Unsplash or direct image link)</label>
                            <input type="url" name="image_url" class="form-control um-input" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="col-md-4">
                            <label class="um-field-label">Or Upload File</label>
                            <input type="file" name="image_file" class="form-control um-input" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="um-field-label">Product Description</label>
                            <textarea name="description" class="form-control um-input" rows="4" placeholder="Enter key features, specifications, package contents, and warranty details..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-orange btn-sm fw-bold">Publish Product Listing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
