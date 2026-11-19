<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial Vendor & Marketplace Listing
// Nationwide E-Commerce Platform (Ghana)
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'List an Item for Sale | UniMarket Ghana';

$error   = '';
$success = '';

$uid = (int)$_SESSION['user_id'];

// Fetch user's region default
$user_default_region = 'University of Ghana (UG - Legon)';
$u_res = $conn->query("SELECT region FROM users WHERE id=$uid");
if ($u_res && $u_row = $u_res->fetch_assoc()) {
    if (!empty($u_row['region'])) {
        $user_default_region = $u_row['region'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = sanitize($_POST['title'] ?? '');
    $price      = floatval($_POST['price'] ?? 0);
    $cat_id     = intval($_POST['category_id'] ?? 0);
    $condition  = sanitize($_POST['condition_type'] ?? 'New');
    $region     = sanitize($_POST['region'] ?? $user_default_region);
    $desc       = sanitize($_POST['description'] ?? '');
    $seller_id  = $uid;

    if (empty($title) || $price <= 0 || empty($cat_id) || empty($condition)) {
        $error = 'Please fill in all required fields marked with an asterisk (*).';
    } else {
        // Handle product photo upload
        $image_name = null;
        if (!empty($_FILES['product_image']['name'])) {
            $upload = storeImageUpload($_FILES['product_image'], 'product_', 5 * 1024 * 1024);
            if ($upload['error']) {
                $error = $upload['error'];
            } else {
                $image_name = $upload['filename'];
            }
        }

        if (empty($error)) {
            // Check whether products has region or campus column
            $has_region = false;
            $chk = $conn->query("SHOW COLUMNS FROM products LIKE 'region'");
            if ($chk && $chk->num_rows > 0) {
                $has_region = true;
            }

            if ($has_region) {
                $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, title, description, price, condition_type, region, image) VALUES (?,?,?,?,?,?,?,?)");
                if ($stmt) {
                    $stmt->bind_param("iissdsss", $seller_id, $cat_id, $title, $desc, $price, $condition, $region, $image_name);
                    if ($stmt->execute()) {
                        $new_id = $conn->insert_id;
                        header("Location: product.php?id=$new_id&posted=1");
                        exit();
                    } else {
                        $error = 'Could not publish listing: ' . $stmt->error;
                    }
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            } else {
                // Fallback for legacy schema
                $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, title, description, price, condition_type, campus, image) VALUES (?,?,?,?,?,?,?,?)");
                if ($stmt) {
                    $stmt->bind_param("iissdsss", $seller_id, $cat_id, $title, $desc, $price, $condition, $region, $image_name);
                    if ($stmt->execute()) {
                        $new_id = $conn->insert_id;
                        header("Location: product.php?id=$new_id&posted=1");
                        exit();
                    } else {
                        $error = 'Could not publish listing: ' . $stmt->error;
                    }
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
$regions = getGhanaCampuses();

include 'includes/header.php';
?>

<div class="container py-5" style="max-width: 760px;">
    <!-- Breadcrumb / Back Navigation -->
    <a href="dashboard.php" class="text-muted d-inline-flex align-items-center gap-2 mb-4 fw-semibold text-decoration-none" style="font-size:14px;">
        <i class="bi bi-arrow-left"></i> Back to Marketplace
    </a>

    <!-- Page Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:54px;height:54px;background:var(--brand-slate);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--accent-orange);box-shadow:0 4px 12px rgba(15,23,42,0.15);">
            <i class="bi bi-rocket-takeoff-fill"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold" style="font-size:24px;color:var(--brand-slate);">Launch a Student Listing</h2>
            <p class="text-muted mb-0" style="font-size:14px;">Showcase your student venture, products, or services to customers across campus and nationwide</p>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="um-product-card p-4 p-md-5">

            <!-- Photo Upload Area -->
            <div class="mb-4">
                <label class="um-label">Product Image</label>
                <div class="upload-box" onclick="document.getElementById('product_image').click()" style="border:2px dashed var(--border);border-radius:14px;padding:32px;text-align:center;cursor:pointer;background:#fafbfc;transition:all 0.2s ease;">
                    <img id="imagePreview" src="" alt="Photo Preview" style="display:none;max-height:220px;margin:0 auto 12px;border-radius:10px;object-fit:contain;">
                    <div class="upload-placeholder">
                        <div style="font-size:40px;margin-bottom:8px;" class="text-secondary"><i class="bi bi-camera"></i></div>
                        <p class="mb-1 fw-bold text-dark">Click to upload product photo</p>
                        <p class="text-muted mb-0 small">JPG, PNG, GIF, WEBP · Recommended 800×800 or 1000×750 (Max 5MB)</p>
                    </div>
                </div>
                <input type="file" id="product_image" name="product_image" accept="image/*" class="d-none">
            </div>

            <!-- Item Title -->
            <div class="mb-3">
                <label class="um-label">Product Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control um-input"
                       placeholder="e.g. Samsung Galaxy S23 Ultra 256GB Phantom Black..."
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <!-- Price in Ghana Cedis -->
                <div class="col-md-6">
                    <label class="um-label">Price in Ghana Cedis (GH₵) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold text-dark border-end-0" style="border-radius:12px 0 0 12px;border-color:var(--border);">GH₵</span>
                        <input type="number" step="0.01" name="price" id="priceInput" class="form-control um-input"
                               style="border-radius:0 12px 12px 0;"
                               placeholder="e.g. 1250.00" min="0"
                               value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>" required>
                    </div>
                    <small id="pricePreview" class="text-success fw-bold mt-1 d-block" style="font-size:12px;"></small>
                </div>

                <!-- Condition -->
                <div class="col-md-6">
                    <label class="um-label">Item Condition <span class="text-danger">*</span></label>
                    <select name="condition_type" class="form-select um-input" required>
                        <option value="">Select Condition</option>
                        <?php foreach (['New', 'Like New', 'Good', 'Refurbished'] as $c): ?>
                        <option value="<?=$c?>" <?= ((isset($_POST['condition_type']) && $_POST['condition_type']===$c))?'selected':'' ?>>
                            <?=$c?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Category -->
                <div class="col-md-6">
                    <label class="um-label">Product Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select um-input" required>
                        <option value="">Select Category</option>
                        <?php if ($categories): while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= ((isset($_POST['category_id']) && (int)$_POST['category_id']===(int)$cat['id']))?'selected':'' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <!-- Campus / University Institution -->
                <div class="col-md-6">
                    <label class="um-label">Campus / University Institution <span class="text-danger">*</span></label>
                    <select name="region" class="form-select um-input" required>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= ($r === $user_default_region)?'selected':'' ?>>
                            <?= htmlspecialchars($r) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label class="um-label">Product Description</label>
                <textarea name="description" class="form-control um-input" rows="4"
                          placeholder="Provide key details about specifications, package contents, warranty, return conditions, and delivery/pickup arrangements..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <!-- Security Notice -->
            <div class="p-3 mb-4 rounded-3 d-flex align-items-center gap-3" style="background:#F1F5F9;border:1px solid var(--border);">
                <i class="bi bi-shield-check text-success fs-4"></i>
                <div class="small text-muted">
                    <strong class="text-dark">UniMarket Student Founder Protection:</strong> Your listing is backed by verified student seller badges, campus delivery support, and instant Mobile Money escrow settlements.
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-3 pt-2">
                <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-3" style="border-radius:12px;">Cancel</a>
                <button type="submit" class="btn btn-orange flex-fill py-3 fw-bold fs-6">
                    <i class="bi bi-rocket-takeoff-fill me-2"></i> Publish Listing on Campus Marketplace
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// Image preview handler
document.getElementById('product_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const preview = document.getElementById('imagePreview');
            preview.src = evt.target.result;
            preview.style.display = 'block';
            const placeholder = document.querySelector('.upload-placeholder');
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// Live GH₵ price formatting helper
document.getElementById('priceInput')?.addEventListener('input', function() {
    const val = parseFloat(this.value);
    const preview = document.getElementById('pricePreview');
    if (preview) {
        if (!isNaN(val) && val > 0) {
            preview.textContent = 'Listing Price: GH₵ ' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            preview.textContent = '';
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
