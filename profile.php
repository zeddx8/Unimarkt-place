<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Account Profile & Commercial Settings
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'Account Settings | UniMarket Ghana';
$uid = (int)$_SESSION['user_id'];

$success = '';
$error   = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$regions = getGhanaCampuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired security token. Please refresh and try again.';
    } else {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $region    = sanitize($_POST['region'] ?? 'Greater Accra Region');
    $city      = sanitize($_POST['city'] ?? 'Accra');
    $address   = sanitize($_POST['address'] ?? '');

    // Handle avatar upload
    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $upload = storeImageUpload($_FILES['avatar'], 'avatar_' . $uid . '_', 2 * 1024 * 1024);
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($upload['filename']) {
            $avatar = $upload['filename'];
        }
    }

    // Handle password change
    if (empty($error) && !empty($_POST['new_password'])) {
        if (!password_verify($_POST['current_password'], $user['password'])) {
            $error = 'Your current password was entered incorrectly.';
        } elseif (strlen($_POST['new_password']) < 6) {
            $error = 'Your new password must be at least 6 characters.';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'The confirmation password did not match.';
        } else {
            $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $ps = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $ps->bind_param("si", $new_hash, $uid);
            $ps->execute();
        }
    }

    if (empty($error)) {
        // Check if modern schema columns exist
        $has_region = false;
        $chk = $conn->query("SHOW COLUMNS FROM users LIKE 'region'");
        if ($chk && $chk->num_rows > 0) {
            $has_region = true;
        }

        if ($has_region) {
            $pu = $conn->prepare("UPDATE users SET full_name=?, phone=?, region=?, city=?, address=?, avatar=? WHERE id=?");
            $pu->bind_param("ssssssi", $full_name, $phone, $region, $city, $address, $avatar, $uid);
        } else {
            // Legacy schema fallback
            $campus = $region;
            $course = $city;
            $level  = $address;
            $pu = $conn->prepare("UPDATE users SET full_name=?, phone=?, campus=?, course=?, level=?, avatar=? WHERE id=?");
            $pu->bind_param("ssssssi", $full_name, $phone, $campus, $course, $level, $avatar, $uid);
        }

        if ($pu && $pu->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['avatar']    = $avatar;
            $_SESSION['region']    = $region;

            $user['full_name'] = $full_name;
            $user['phone']     = $phone;
            $user['region']    = $region;
            $user['city']      = $city;
            $user['address']   = $address;
            $user['avatar']    = $avatar;

            $success = 'Account profile and delivery settings updated successfully!';
        } else {
            $error = 'Could not update profile: ' . ($pu ? $pu->error : $conn->error);
        }
    }
    }
}

// User Metrics
$listings = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid AND status!='removed'")->fetch_row()[0] ?? 0;
$sold_cnt = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid AND status='sold'")->fetch_row()[0] ?? 0;
$saved    = $conn->query("SELECT COUNT(*) FROM wishlist WHERE user_id=$uid")->fetch_row()[0] ?? 0;

include 'includes/header.php';
?>

<div class="container py-5" style="max-width: 840px;">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0" style="font-size:24px;color:var(--brand-slate);">Campus Profile & Venture Settings</h2>
        <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
            <i class="bi bi-mortarboard-fill text-warning me-1"></i> Student Entrepreneur Account
        </span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Profile Header Card -->
    <div class="um-product-card p-4 mb-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="seller-avatar" style="width:84px;height:84px;font-size:32px;flex-shrink:0;">
            <?php $avatarUrl = !empty($user['avatar']) ? getProductImageUrl($user['avatar']) : ''; ?>
            <img class="avatar-preview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="" style="<?= $avatarUrl ? '' : 'display:none;' ?>">
            <span class="avatar-fallback" style="<?= $avatarUrl ? 'display:none;' : '' ?>"><?= getAvatar($user['full_name']) ?></span>
        </div>
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-1" style="color:var(--navy-deep);">
                <?= htmlspecialchars($user['full_name']) ?>
                <?php if (!empty($user['is_verified'])): ?>
                    <i class="bi bi-patch-check-fill ms-1" style="color:var(--amber);" title="Verified Commercial User"></i>
                <?php endif; ?>
            </h4>
            <div class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-light text-dark border px-3 py-1">
                    <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($user['region'] ?? 'Greater Accra Region') ?>
                </span>
                <?php if (!empty($user['city'])): ?>
                    <span class="badge bg-light text-muted border px-3 py-1"><?= htmlspecialchars($user['city']) ?></span>
                <?php endif; ?>
                <span class="badge bg-light text-success border px-3 py-1">
                    <i class="bi bi-patch-check me-1"></i> Verified Campus Member
                </span>
            </div>
        </div>
        <div class="d-flex gap-3 text-center border-start ps-lg-4">
            <div>
                <div class="fw-bold fs-4 text-dark"><?= $listings ?></div>
                <div class="small text-muted text-uppercase" style="font-size:11px;">Ventures</div>
            </div>
            <div>
                <div class="fw-bold fs-4 text-success"><?= $sold_cnt ?></div>
                <div class="small text-muted text-uppercase" style="font-size:11px;">Sold</div>
            </div>
            <div>
                <div class="fw-bold fs-4" style="color:var(--amber);"><?= $saved ?></div>
                <div class="small text-muted text-uppercase" style="font-size:11px;">Wishlist</div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="um-product-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:12px;letter-spacing:0.8px;">Personal & Campus Delivery Details</h6>

            <!-- Avatar -->
            <div class="mb-4">
                <label class="um-label">Profile Photo</label>
                <div class="d-flex align-items-center gap-3">
                    <div class="seller-avatar" style="width:60px;height:60px;font-size:22px;">
                        <img class="avatar-preview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="" style="<?= $avatarUrl ? '' : 'display:none;' ?>">
                        <span class="avatar-fallback" style="<?= $avatarUrl ? 'display:none;' : '' ?>"><?= getAvatar($user['full_name']) ?></span>
                    </div>
                    <div>
                        <label for="avatar" class="btn btn-outline-secondary btn-sm px-3 py-2 mb-0" style="border-radius:10px;cursor:pointer;">
                            <i class="bi bi-camera me-1"></i> Change Photo
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" class="d-none">
                        <div class="text-muted mt-1 small" style="font-size:11.5px;">JPG, PNG, WEBP · Max 2MB</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="um-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control um-input" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="um-label">Phone Number (Ghana +233) <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control um-input" placeholder="e.g. +233 24 123 4567" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="um-label">Campus / University Institution <span class="text-danger">*</span></label>
                    <select name="region" class="form-select um-input" required>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= ((($user['region'] ?? '') === $r)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="um-label">City / Town <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control um-input" placeholder="e.g. Accra, Kumasi, Tema, Takoradi..." value="<?= htmlspecialchars($user['city'] ?? 'Accra') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="um-label">Hostel, Hall or Street Address</label>
                <input type="text" name="address" class="form-control um-input" placeholder="e.g. Jean Nelson Hall, Unity Hall (Conti), Evandy Hostel, East Legon..." value="<?= htmlspecialchars($user['address'] ?? '') ?>">
            </div>
        </div>

        <!-- Security / Password Card -->
        <div class="um-product-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:12px;letter-spacing:0.8px;">Change Password <span class="fw-normal text-muted">(Leave empty to retain current)</span></h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="um-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control um-input" placeholder="••••••••">
                </div>
                <div class="col-md-4">
                    <label class="um-label">New Password</label>
                    <input type="password" name="new_password" class="form-control um-input" placeholder="Min. 6 characters">
                </div>
                <div class="col-md-4">
                    <label class="um-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control um-input" placeholder="Repeat new password">
                </div>
            </div>
        </div>

        <div class="d-flex gap-3">
            <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-3" style="border-radius:12px;">Cancel</a>
            <button type="submit" class="btn btn-orange flex-fill py-3 fw-bold">
                <i class="bi bi-check-circle me-1"></i> Save Profile Details
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
