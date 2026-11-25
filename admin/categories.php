<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Admin Categories Full CRUD Management
// Create, Read, Update, and Delete Marketplace Categories
// ZERO Emojis — Professional Vector Icons
// ════════════════════════════════════════════════════════════

require_once '../includes/db.php';
requireAdmin();

$page_title = 'Categories Catalog CRUD';

$msg = '';
$error = '';

// ── 1. HANDLE CREATE CATEGORY ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_category') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $name  = sanitize($_POST['name'] ?? '');
        $slug  = sanitize($_POST['slug'] ?? '');
        $icon  = sanitize($_POST['icon'] ?? 'bi-tag');
        $image = sanitize($_POST['image'] ?? '');

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }

        if (empty($name) || empty($slug)) {
            $error = 'Category name and slug are required.';
        } else {
            $stmt_ins = $conn->prepare("INSERT INTO categories (name, slug, icon, image) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("ssss", $name, $slug, $icon, $image);
            if ($stmt_ins->execute()) {
                $msg = 'Category "' . htmlspecialchars($name) . '" created successfully!';
            } else {
                $error = 'Could not create category: ' . $conn->error;
            }
        }
    }
}

// ── 2. HANDLE UPDATE CATEGORY ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_category') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $cat_id = intval($_POST['category_id'] ?? 0);
        $name   = sanitize($_POST['name'] ?? '');
        $slug   = sanitize($_POST['slug'] ?? '');
        $icon   = sanitize($_POST['icon'] ?? 'bi-tag');
        $image  = sanitize($_POST['image'] ?? '');

        if ($cat_id <= 0 || empty($name) || empty($slug)) {
            $error = 'Please provide valid category details.';
        } else {
            $stmt_up = $conn->prepare("UPDATE categories SET name = ?, slug = ?, icon = ?, image = ? WHERE id = ?");
            $stmt_up->bind_param("ssssi", $name, $slug, $icon, $image, $cat_id);
            if ($stmt_up->execute()) {
                $msg = 'Category #' . $cat_id . ' updated successfully!';
            } else {
                $error = 'Could not update category: ' . $conn->error;
            }
        }
    }
}

// ── 3. HANDLE DELETE CATEGORY ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $del_id = intval($_POST['category_id'] ?? 0);
        if ($del_id > 0) {
            $chk_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $chk_stmt->bind_param("i", $del_id);
            $chk_stmt->execute();
            $chk_p = $chk_stmt->get_result()->fetch_row()[0] ?? 0;
            if ($chk_p > 0) {
                $error = 'Cannot delete category #' . $del_id . ' because it currently contains ' . $chk_p . ' active products. Please reassign or delete the products first.';
            } else {
                $del_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $del_stmt->bind_param("i", $del_id);
                $del_stmt->execute();
                $msg = 'Category #' . $del_id . ' deleted successfully.';
            }
        }
    }
}

// Fetch all categories with products count
$categories = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count 
    FROM categories c 
    ORDER BY c.id ASC
");

$pending_reports = (int)($conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetch_row()[0] ?? 0);
$unread_messages = (int)($conn->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetch_row()[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Catalog CRUD | UniMarket Admin</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body style="background:#FAFAF9;">

<div class="d-flex flex-column flex-md-row" style="min-height:100vh;">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- ── MAIN CONTENT ──────────────────────────────────────────── -->
    <main class="flex-fill p-4" style="overflow-x:hidden;">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="color:var(--brand-slate);">Categories Catalog Management (CRUD)</h3>
                <p class="text-muted small mb-0">Create, organize, and update commercial department categories with vector icons</p>
            </div>
            <!-- Create Category Modal Trigger -->
            <button type="button" class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#createCatModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Category
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

        <!-- Categories Table -->
        <div class="um-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="font-size:13.5px;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th>ID</th>
                            <th>Icon & Name</th>
                            <th>Slug Identifier</th>
                            <th>Image Preview</th>
                            <th>Active Listings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$categories || $categories->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No categories defined yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td>
                            <span class="fw-bold text-muted font-monospace">#<?= $cat['id'] ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;background:var(--accent-orange-light);color:var(--accent-orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;">
                                    <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                                </div>
                                <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($cat['name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <code class="small text-secondary"><?= htmlspecialchars($cat['slug']) ?></code>
                        </td>
                        <td>
                            <?php if (!empty($cat['image'])): ?>
                                <img src="<?= htmlspecialchars($cat['image']) ?>" alt="" style="width:48px;height:32px;border-radius:6px;object-fit:cover;border:1px solid var(--border-color);">
                            <?php else: ?>
                                <span class="text-muted small">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                <?= (int)$cat['product_count'] ?> products
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <!-- Edit Modal Trigger -->
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2"
                                        data-bs-toggle="modal" data-bs-target="#editCatModal<?= $cat['id'] ?>" title="Edit Category">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- Delete Form -->
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- ══ MODAL FOR EDITING CATEGORY ═══════════════ -->
                            <div class="modal fade" id="editCatModal<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
                                        <form method="POST" action="">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="update_category">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <div class="modal-header bg-light">
                                                <h6 class="modal-title fw-bold">Edit Category: <?= htmlspecialchars($cat['name']) ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="um-field-label">Category Name</label>
                                                    <input type="text" name="name" class="form-control um-input" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="um-field-label">URL Slug</label>
                                                    <input type="text" name="slug" class="form-control um-input" value="<?= htmlspecialchars($cat['slug']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="um-field-label">Bootstrap Vector Icon Class (e.g. bi-phone, bi-laptop, bi-tv)</label>
                                                    <input type="text" name="icon" class="form-control um-input" value="<?= htmlspecialchars($cat['icon']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="um-field-label">Hero / Thumbnail Image URL</label>
                                                    <input type="url" name="image" class="form-control um-input" value="<?= htmlspecialchars($cat['image']) ?>">
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

<!-- ══ MODAL FOR CREATING CATEGORY ═══════════════════════════════ -->
<div class="modal fade" id="createCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_category">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold">Create New Commercial Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="um-field-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control um-input" placeholder="e.g. Watches & Jewelry..." required>
                    </div>
                    <div class="mb-3">
                        <label class="um-field-label">URL Slug (leave empty to auto-generate)</label>
                        <input type="text" name="slug" class="form-control um-input" placeholder="e.g. watches-jewelry">
                    </div>
                    <div class="mb-3">
                        <label class="um-field-label">Bootstrap Vector Icon Class <span class="text-danger">*</span></label>
                        <input type="text" name="icon" class="form-control um-input" value="bi-tag" placeholder="e.g. bi-smartwatch or bi-gem" required>
                    </div>
                    <div class="mb-3">
                        <label class="um-field-label">Category Banner Image URL</label>
                        <input type="url" name="image" class="form-control um-input" placeholder="https://images.unsplash.com/...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-orange btn-sm fw-bold">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
