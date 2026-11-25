<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Student & Creator Registration Portal
// Campus Entrepreneurship & E-Commerce Platform
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

$error   = '';
$success = '';

// Major Ghanaian Universities & Campuses + Regions
$campuses = getGhanaCampuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired security token. Please refresh and try again.';
    } else {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $phone     = sanitize($_POST['phone'] ?? '');
    $region    = sanitize($_POST['region'] ?? 'University of Ghana (UG - Legon)');
    $city      = sanitize($_POST['city'] ?? 'Accra');
    $address   = sanitize($_POST['address'] ?? '');

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The passwords entered do not match.';
    } else {
        // Check if email already registered
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email address already exists. Please sign in instead.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, region, city, address) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $full_name, $email, $hashed, $phone, $region, $city, $address);

            if ($stmt && $stmt->execute()) {
                $success = 'Your UniMarket student account has been created successfully! You can now sign in to launch your venture or start shopping.';
            } else {
                $error = 'Registration could not be completed: ' . ($stmt ? $stmt->error : $conn->error);
            }
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Join UniMarket Ghana — The campus marketplace empowering student entrepreneurs, innovators, and shoppers across Ghanaian universities.">
    <title>Create Student / Creator Account | UniMarket Ghana</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <style>
        .auth-split-wrapper {
            min-height: 100vh;
            display: flex;
            background: var(--bg-page);
        }
        .auth-image-panel {
            flex: 1;
            position: relative;
            background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1600&auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 56px;
            color: #FFFFFF;
        }
        .auth-image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.94) 0%, rgba(30, 41, 59, 0.90) 100%);
            backdrop-filter: blur(2px);
        }
        .auth-image-content {
            position: relative;
            z-index: 2;
        }
        .auth-form-panel {
            flex: 1.25;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            background: #FFFFFF;
            overflow-y: auto;
        }
        .auth-card-box {
            width: 100%;
            max-width: 560px;
        }
        @media (max-width: 991px) {
            .auth-image-panel { display: none; }
            .auth-form-panel { background: var(--bg-page); padding: 32px 20px; }
        }
    </style>
</head>
<body>

<div class="auth-split-wrapper">
    <!-- ══ LEFT PANEL: CAMPUS ENTREPRENEURSHIP PANEL ════════════ -->
    <div class="auth-image-panel">
        <div class="auth-image-overlay"></div>

        <!-- Top Brand Title -->
        <div class="auth-image-content">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="brand-icon-box">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="fs-3 fw-bold tracking-tight">
                    Uni<span class="brand-accent-text">Market</span>
                    <span class="brand-country-badge ms-2">CAMPUS GH</span>
                </div>
            </div>
            <h2 class="fw-bold text-white mb-2" style="font-size:26px;line-height:1.3;">
                Join Ghana's Leading Student Entrepreneurship Network
            </h2>
            <p class="text-white-50" style="font-size:14.5px;line-height:1.6;max-width:440px;">
                Whether you're building a campus brand, offering creative services, selling gear, or supporting fellow student founders, UniMarket is your commercial platform.
            </p>
        </div>

        <!-- Value Proposition Pillars -->
        <div class="auth-image-content d-flex flex-column gap-3 my-4">
            <div class="d-flex align-items-start gap-3">
                <div style="width:42px;height:42px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.14);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;color:var(--accent-orange-light);flex-shrink:0;">
                    <i class="bi bi-rocket-takeoff-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:14px;">Launch in Under 2 Minutes</div>
                    <div class="text-white-50" style="font-size:13px;">Create your profile and start publishing products to students across your campus and beyond.</div>
                </div>
            </div>

            <div class="d-flex align-items-start gap-3">
                <div style="width:42px;height:42px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.14);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;color:var(--accent-orange-light);flex-shrink:0;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:14px;">Zero Risk & MoMo Escrow</div>
                    <div class="text-white-50" style="font-size:13px;">Payments are held securely via UniMarket Escrow until delivery is confirmed by the buyer.</div>
                </div>
            </div>
        </div>

        <div class="auth-image-content pt-3 border-top border-white border-opacity-10 text-white-50 small">
            &copy; <?= date('Y') ?> UniMarket Ghana · Campus Entrepreneurship & Commercial Platform.
        </div>
    </div>

    <!-- ══ RIGHT PANEL: REGISTRATION FORM ═════════════════════════════ -->
    <div class="auth-form-panel">
        <div class="auth-card-box">
            <!-- Back Link -->
            <a href="index.php" class="text-muted d-inline-flex align-items-center gap-2 mb-3 fw-semibold text-decoration-none small">
                <i class="bi bi-arrow-left"></i> Back to Sign In
            </a>

            <div class="mb-4">
                <h3 class="fw-bold mb-1" style="font-size:26px;color:var(--brand-slate);">Create Account</h3>
                <p class="text-muted small mb-0">Join as a student entrepreneur or campus customer</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-3 mb-4 d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="bi bi-exclamation-circle-fill fs-5 text-danger flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success border-0 small py-3 mb-4" style="border-radius:10px;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-check-circle-fill fs-5 text-success flex-shrink-0"></i>
                    <span class="fw-semibold"><?= htmlspecialchars($success) ?></span>
                </div>
                <div>
                    <a href="index.php" class="btn btn-orange btn-sm fw-bold">Sign In to Your Account <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>
                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-12">
                        <label class="um-field-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control um-input"
                               placeholder="e.g. Kwame Mensah or Abena Osei"
                               value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" required>
                    </div>

                    <!-- Email -->
                    <div class="col-12">
                        <label class="um-field-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control um-input"
                               placeholder="e.g. kwame@st.ug.edu.gh or personal email"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label class="um-field-label">Phone / WhatsApp Number (Ghana) <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control um-input"
                               placeholder="e.g. +233 24 123 4567"
                               value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" required>
                    </div>

                    <!-- Campus / Institution -->
                    <div class="col-md-6">
                        <label class="um-field-label">Campus / University Institution <span class="text-danger">*</span></label>
                        <select name="region" class="form-select um-input" required>
                            <?php foreach ($campuses as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ((isset($_POST['region']) && $_POST['region']===$c))?'selected':'' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- City / Campus Area -->
                    <div class="col-md-6">
                        <label class="um-field-label">Campus Town / City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control um-input"
                               placeholder="e.g. Legon / Accra, Kumasi, Cape Coast..."
                               value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>" required>
                    </div>

                    <!-- Hostel / Delivery Location -->
                    <div class="col-md-6">
                        <label class="um-field-label">Hostel, Hall or Street Location</label>
                        <input type="text" name="address" class="form-control um-input"
                               placeholder="e.g. Pentagon Hall, Unity Hall (Conti), Evandy..."
                               value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>">
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label class="um-field-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="pwReg" class="form-control um-input"
                                   style="border-radius:12px 0 0 12px;border-right:none;" placeholder="Min. 6 characters" required>
                            <button type="button" class="input-group-text bg-white toggle-reg-password"
                                    style="border-radius:0 12px 12px 0;border:1.5px solid var(--border-color);border-left:none;cursor:pointer;"
                                    title="Toggle password visibility">
                                <i class="bi bi-eye text-muted" id="pwRegIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label class="um-field-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="pwConfirmReg" class="form-control um-input"
                                   style="border-radius:12px 0 0 12px;border-right:none;" placeholder="Repeat password" required>
                            <button type="button" class="input-group-text bg-white toggle-reg-confirm"
                                    style="border-radius:0 12px 12px 0;border:1.5px solid var(--border-color);border-left:none;cursor:pointer;"
                                    title="Toggle password visibility">
                                <i class="bi bi-eye text-muted" id="pwConfirmRegIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-orange w-100 py-3 fw-bold fs-6">
                            Create Student / Creator Account <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Already registered on UniMarket?
                <a href="index.php" class="fw-bold ms-1" style="color:var(--accent-orange);">Sign In Here</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show/Hide password toggle
function bindToggle(btnSel, inputId, iconId) {
    document.querySelector(btnSel)?.addEventListener('click', function() {
        const pwInput = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!pwInput || !icon) return;
        if (pwInput.type === 'password') {
            pwInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            pwInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
}
bindToggle('.toggle-reg-password', 'pwReg', 'pwRegIcon');
bindToggle('.toggle-reg-confirm', 'pwConfirmReg', 'pwConfirmRegIcon');
</script>
</body>
</html>
