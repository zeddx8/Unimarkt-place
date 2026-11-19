<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Student Entrepreneurship & Campus Commerce
// Sign-In Portal
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';

// Redirect if already authenticated
if (isLoggedIn()) {
    header('Location: ' . (!empty($_SESSION['is_admin']) ? 'admin/index.php' : 'dashboard.php'));
    exit();
}

$error      = '';
$email_val  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = sanitize($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $email_val = $email;

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = 'No account found with this email. Please create an account to get started.';
        } elseif (!empty($user['is_banned'])) {
            $error = 'This account has been suspended. Please contact student support.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect password. Please verify and try again.';
        } else {
            // Successful Authentication
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['avatar']    = $user['avatar'];
            $_SESSION['region']    = $user['region'] ?? 'Greater Accra Region';
            $_SESSION['is_admin']  = (int)($user['is_admin'] ?? 0);

            header('Location: ' . (!empty($user['is_admin']) ? 'admin/index.php' : 'dashboard.php'));
            exit();
        }
    }
}

// Live platform metrics
$total_listings = 0;
$total_buyers   = 0;
$total_orders   = 0;

$r1 = $conn->query("SELECT COUNT(*) FROM products WHERE status='available'");
if ($r1) $total_listings = (int)$r1->fetch_row()[0];

$r2 = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin=0");
if ($r2) $total_buyers = (int)$r2->fetch_row()[0];

$r3 = $conn->query("SELECT COUNT(*) FROM orders");
if ($r3) $total_orders = (int)$r3->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to UniMarket Ghana — The premier marketplace connecting student entrepreneurs, innovators, and campus shoppers across universities in Ghana.">
    <title>Sign In | UniMarket Ghana — Campus Entrepreneurship Platform</title>

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
            flex: 1.15;
            position: relative;
            background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1600&auto=format&fit=crop&q=80');
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
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            background: #FFFFFF;
        }
        .auth-card-box {
            width: 100%;
            max-width: 440px;
        }
        .form-icon-wrap {
            position: relative;
        }
        .form-icon-wrap .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 17px;
            pointer-events: none;
        }
        .form-icon-wrap .form-control {
            padding-left: 42px;
        }
        @media (max-width: 991px) {
            .auth-image-panel { display: none; }
            .auth-form-panel { background: var(--bg-page); padding: 36px 20px; }
        }
    </style>
</head>
<body>

<div class="auth-split-wrapper">
    <!-- ══ LEFT PANEL: CAMPUS ENTREPRENEURSHIP HERO PANEL ════════════ -->
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
                Empowering Student Entrepreneurs <br>Across Ghanaian Campuses
            </h2>
            <p class="text-white-50" style="font-size:14.5px;line-height:1.6;max-width:460px;">
                The dedicated launchpad for student-founded businesses, tech innovators, fashion creators, and campus services at Legon, KNUST, UCC, UPSA, Ashesi, and nationwide.
            </p>
        </div>

        <!-- Value Proposition Pillars -->
        <div class="auth-image-content d-flex flex-column gap-3 my-4">
            <div class="d-flex align-items-start gap-3">
                <div style="width:42px;height:42px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.14);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;color:var(--accent-orange-light);flex-shrink:0;">
                    <i class="bi bi-rocket-takeoff-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:14px;">Real Student Ventures & Campus Creators</div>
                    <div class="text-white-50" style="font-size:13px;">Support verified student-led businesses from streetwear brands to tech accessories.</div>
                </div>
            </div>

            <div class="d-flex align-items-start gap-3">
                <div style="width:42px;height:42px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.14);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;color:var(--accent-orange-light);flex-shrink:0;">
                    <i class="bi bi-phone-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:14px;">Instant Mobile Money (MoMo) Checkout</div>
                    <div class="text-white-50" style="font-size:13px;">Seamless transactions via MTN MoMo, Telecel Cash & AT Money in Ghana Cedis.</div>
                </div>
            </div>

            <div class="d-flex align-items-start gap-3">
                <div style="width:42px;height:42px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.14);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;color:var(--accent-orange-light);flex-shrink:0;">
                    <i class="bi bi-buildings-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:14px;">Hostel, Campus Landmark & Nationwide Delivery</div>
                    <div class="text-white-50" style="font-size:13px;">Convenient hand-to-hand delivery to dorms, halls, lecture blocks, or doorstep.</div>
                </div>
            </div>
        </div>

        <!-- Live Platform Stats -->
        <div class="auth-image-content d-flex gap-4 pt-4 border-top border-white border-opacity-10">
            <div>
                <div class="fw-bold fs-4 text-white">16+</div>
                <div class="small text-white-50">Campuses & Hubs</div>
            </div>
            <div>
                <div class="fw-bold fs-4 text-white"><?= number_format(max(12, $total_listings)) ?>+</div>
                <div class="small text-white-50">Student Listings</div>
            </div>
            <div>
                <div class="fw-bold fs-4 text-white"><?= number_format(max(4, $total_orders)) ?>+</div>
                <div class="small text-white-50">Campus Orders</div>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT PANEL: AUTHENTICATION FORM ═══════════════════════════ -->
    <div class="auth-form-panel">
        <div class="auth-card-box">
            <!-- Mobile Brand Logo -->
            <div class="d-lg-none mb-4 text-center">
                <div class="d-inline-flex align-items-center gap-2">
                    <div class="brand-icon-box" style="width:36px;height:36px;font-size:18px;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <span class="fs-4 fw-bold text-dark">
                        Uni<span class="brand-accent-text">Market</span>
                    </span>
                    <span class="brand-country-badge">CAMPUS GH</span>
                </div>
                <p class="text-muted small mt-2 mb-0">Ghana's Student Entrepreneurship Platform</p>
            </div>

            <div class="mb-4">
                <h3 class="fw-bold mb-1" style="font-size:26px;color:var(--brand-slate);">Welcome Back</h3>
                <p class="text-muted small mb-0">Sign in to manage your student venture, track campus orders, and shop</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-3 mb-4 d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="bi bi-exclamation-circle-fill fs-5 text-danger flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <!-- Email -->
                <div class="mb-3">
                    <label class="um-field-label" for="email">Email Address</label>
                    <div class="form-icon-wrap">
                        <i class="bi bi-envelope field-icon"></i>
                        <input type="email" id="email" name="email" class="form-control um-input"
                               placeholder="your.name@example.com"
                               value="<?= htmlspecialchars($email_val) ?>"
                               autocomplete="email" required>
                    </div>
                </div>

                <!-- Password with Show/Hide toggle -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="um-field-label mb-0" for="password">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius:12px 0 0 12px;border-color:var(--border-color);">
                            <i class="bi bi-lock text-muted"></i>
                        </span>
                        <input type="password" id="password" name="password" class="form-control um-input border-start-0 border-end-0"
                               placeholder="Enter your password"
                               autocomplete="current-password" required>
                        <button type="button" class="input-group-text bg-white toggle-password" data-target="#password"
                                style="border-radius:0 12px 12px 0;border:1.5px solid var(--border-color);border-left:none;cursor:pointer;"
                                title="Toggle password visibility">
                            <i class="bi bi-eye text-muted" id="pwToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Sign In Button -->
                <button type="submit" id="loginSubmitBtn" class="btn btn-orange w-100 py-3 fw-bold mt-2" style="font-size:15.5px;">
                    <span id="btnText">Sign In to UniMarket <i class="bi bi-arrow-right ms-1"></i></span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                </button>
            </form>

            <!-- Create Account Link -->
            <div class="text-center mt-4 pt-3 border-top" style="border-color:var(--border-color)!important;">
                <span class="text-muted small">New to the campus marketplace?</span>
                <a href="register.php" class="fw-bold ms-1" style="color:var(--accent-orange);font-size:14px;">Create Student / Buyer Account</a>
            </div>

            <!-- Admin Access Link -->
            <div class="text-center mt-3">
                <a href="admin/login.php" class="text-muted small text-decoration-none" style="font-size:12.5px;">
                    <i class="bi bi-shield-lock me-1"></i> Campus Operations & Admin Console
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show/Hide password toggle
document.querySelector('.toggle-password')?.addEventListener('click', function() {
    const pwInput = document.getElementById('password');
    const icon = document.getElementById('pwToggleIcon');
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

// Loading state on form submit
document.getElementById('loginForm')?.addEventListener('submit', function() {
    document.getElementById('btnText').textContent = 'Authenticating...';
    document.getElementById('btnSpinner').classList.remove('d-none');
});
</script>
</body>
</html>
