<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Edit Student Venture Listing
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'Edit Listing | UniMarket Ghana';
$uid = (int)$_SESSION['user_id'];
$pid = intval($_GET['id'] ?? 0);

if ($pid < 1) {
    header('Location: my-listings.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ? AND status != 'removed' LIMIT 1");
$stmt->bind_param("ii", $pid, $uid);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: my-listings.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired security token. Please refresh and try again.';
    } else {
        $title     = sanitize($_POST['title'] ?? '');
        $price     = floatval($_POST['price'] ?? 0);
        $cat_id    = intval($_POST['category_id'] ?? 0);
        $condition = sanitize($_POST['condition_type'] ?? 'New');
        $region    = sanitize($_POST['region'] ?? ($product['region'] ?? 'Greater Accra Region'));
        $desc      = sanitize($_POST['description'] ?? '');
        $image_val = $product['image'];

        if (!empty($_POST['image_url'])) {
            $image_val = sanitize(trim($_POST['image_url']));
        }
        if (!empty($_FILES['product_image']['name'])) {
            $upload = storeImageUpload($_FILES['product_image'], 'product_', 5 * 1024 * 1024);
            if ($upload['error']) {
                $error = $upload['error'];
            } elseif ($upload['filename']) {
                $image_val = $upload['filename'];
            }
        }

        if (empty($error) && (empty($title) || $price <= 0 || $cat_id <= 0 || empty($condition))) {
            $error = 'Please fill in all required fields marked with an asterisk (*).';
        }

        if (empty($error)) {
            $up = $conn->prepare("UPDATE products SET category_id=?, title=?, description=?, price=?, condition_type=?, region=?, image=? WHERE id=? AND seller_id=?");
            if ($up) {
                $up->bind_param("issdsssii", $cat_id, $title, $desc, $price, $condition, $region, $image_val, $pid, $uid);
                if ($up->execute()) {
                    header("Location: product.php?id=$pid");
                    exit();
                } else {
                    $error = 'Could not update listing: ' . $up->error;
                }
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
$regions = getGhanaCampuses();
$currentImg = !empty($product['image']) ? getProductImageUrl($product['image']) : '';

include 'includes/header.php';
?>

<div class="container py-5" style="max-width: 760px;">
    <a href="my-listings.php" class="text-muted d-inline-flex align-items-center gap-2 mb-4 fw-semibold text-decoration-none" style="font-size:14px;">
        <i class="bi bi-arrow-left"></i> Back to My Listings
    </a>

    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:54px;height:54px;background:var(--brand-slate);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--accent-orange);">
            <i class="bi bi-pencil-square"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold" style="font-size:24px;color:var(--brand-slate);">Edit Student Listing</h2>
            <p class="text-muted mb-0" style="font-size:14px;">Update pricing, campus location, specifications, or product photo</p>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="um-product-card p-4 p-md-5">
            <div class="mb-4">
                <label class="um-label">Product Image</label>
                <div class="upload-box" onclick="document.getElementById('product_image').click()" style="border:2px dashed var(--border);border-radius:14px;padding:24px;text-align:center;cursor:pointer;background:#fafbfc;">
                    <img id="imagePreview" src="<?= htmlspecialchars($currentImg) ?>" alt="Photo Preview" style="<?= $currentImg ? 'display:block;' : 'display:none;' ?>max-height:220px;margin:0 auto 12px;border-radius:10px;object-fit:contain;">
                    <div class="upload-placeholder" style="<?= $currentImg ? 'display:none;' : '' ?>">
                        <div style="font-size:36px;margin-bottom:8px;" class="text-secondary"><i class="bi bi-camera"></i></div>
                        <p class="mb-1 fw-bold text-dark">Click to upload a new photo</p>
                    </div>
                    <p class="text-muted mb-0 small">Click above to replace photo (Max 5MB)</p>
                </div>
                <input type="file" id="product_image" name="product_image" accept="image/*" class="d-none">
                <div class="mt-2 text-start">
                    <input type="url" name="image_url" class="form-control um-input" style="font-size:13px;"
                           placeholder="Or paste direct image URL (optional)"
                           value="<?= preg_match('#^https?://#i', (string)$product['image']) ? htmlspecialchars($product['image']) : '' ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="um-label">Product Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control um-input" value="<?= htmlspecialchars($product['title']) ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="um-label">Price in Ghana Cedis (GH₵) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold text-dark border-end-0" style="border-radius:12px 0 0 12px;border-color:var(--border);">GH₵</span>
                        <input type="number" step="0.01" name="price" class="form-control um-input" style="border-radius:0 12px 12px 0;" min="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="um-label">Item Condition <span class="text-danger">*</span></label>
                    <select name="condition_type" class="form-select um-input" required>
                        <?php foreach (['New', 'Like New', 'Good', 'Refurbished'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($product['condition_type'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="um-label">Product Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select um-input" required>
                        <?php if ($categories): while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="um-label">Campus / University Institution <span class="text-danger">*</span></label>
                    <select name="region" class="form-select um-input" required>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= ($r === $product['region']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="um-label">Product Description</label>
                <textarea name="description" class="form-control um-input" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-3 pt-2">
                <a href="my-listings.php" class="btn btn-outline-secondary px-4 py-3" style="border-radius:12px;">Cancel</a>
                <button type="submit" class="btn btn-orange flex-fill py-3 fw-bold fs-6">
                    <i class="bi bi-check-circle me-1"></i> Save Listing Changes
                </button>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
