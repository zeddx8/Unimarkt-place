<?php
require_once '../includes/db.php';

// Redirect if already logged in as admin
if (isLoggedIn() && !empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit();
}

$error     = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $email_val = $email;

    if (empty($email) || empty($password)) {
        $error = 'Both email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1 LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = 'No administrator account found with that email.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect admin password. Please try again.';
        } else {
            // Admin login success
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['avatar']    = $user['avatar'];
            $_SESSION['region']    = $user['region'] ?? 'Greater Accra Region';
            $_SESSION['is_admin']  = 1;

            header('Location: index.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console Sign In | UniMarket Ghana</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative;
            padding: 24px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(234, 88, 12, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(234, 88, 12, 0.04) 1px, transparent 1px);
            background-size: 36px 36px;
            pointer-events: none;
        }

        .glow-1 {
            position: fixed;
            top: -100px; left: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(234, 88, 12, 0.15) 0%, transparent 65%);
            pointer-events: none;
        }

        .glow-2 {
            position: fixed;
            bottom: -100px; right: -100px;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(22, 163, 74, 0.1) 0%, transparent 65%);
            pointer-events: none;
        }

        .admin-login-card {
            width: 100%;
            max-width: 440px;
            background: #1E293B;
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 44px 38px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5);
            position: relative;
            z-index: 1;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(234, 88, 12, 0.15);
            border: 1px solid rgba(234, 88, 12, 0.35);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 700;
            color: #FB923C;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .admin-title {
            font-size: 26px;
            font-weight: 800;
            color: #FFFFFF;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .admin-subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13.5px;
            margin-bottom: 28px;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.65);
            letter-spacing: .6px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
            margin-bottom: 18px;
        }

        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 16px;
            pointer-events: none;
        }

        .admin-input {
            width: 100%;
            padding: 13px 44px 13px 42px;
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: #FFFFFF;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14.5px;
            outline: none;
            transition: all 0.2s;
        }

        .admin-input::placeholder { color: rgba(255, 255, 255, 0.3); }

        .admin-input:focus {
            border-color: #EA580C;
            background: rgba(234, 88, 12, 0.08);
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.2);
        }

        .admin-input.is-error {
            border-color: #f87171;
            background: rgba(248, 113, 113, 0.08);
        }

        .eye-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            font-size: 16px;
            cursor: pointer;
            padding: 4px;
        }

        .eye-btn:hover { color: #FFFFFF; }

        .error-box {
            background: rgba(248, 113, 113, 0.12);
            border: 1.5px solid rgba(248, 113, 113, 0.3);
            border-left: 4px solid #f87171;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13.5px;
            color: #fca5a5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-admin-login {
            width: 100%;
            padding: 14px;
            background: #EA580C;
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            font-size: 15.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 6px;
        }

        .btn-admin-login:hover {
            background: #C2410C;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(234, 88, 12, 0.35);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13.5px;
            text-decoration: none;
            transition: color 0.2s;
            margin-top: 24px;
        }

        .back-link:hover { color: #FB923C; }

        .security-note {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 10px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.45);
        }
    </style>
</head>
<body>

<div class="glow-1"></div>
<div class="glow-2"></div>

<div class="admin-login-card">

    <div class="admin-badge">
        <i class="bi bi-shield-lock"></i>
        Operations & Admin
    </div>

    <div class="admin-title">Admin Console</div>
    <div class="admin-subtitle">UniMarket Ghana · Commercial Operations Portal</div>

    <?php if ($error): ?>
    <div class="error-box">
        <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="adminForm" novalidate>
        <!-- Email -->
        <div>
            <label class="field-label" for="email">Admin Email</label>
            <div class="field-wrap">
                <i class="bi bi-envelope field-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="admin-input <?= $error ? 'is-error' : '' ?>"
                    placeholder="admin@unimarket.com.gh"
                    value="<?= htmlspecialchars($email_val) ?>"
                    autocomplete="email"
                    required
                >
            </div>
        </div>

        <!-- Password -->
        <div>
            <label class="field-label" for="password">Password</label>
            <div class="field-wrap">
                <i class="bi bi-lock field-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="admin-input <?= $error ? 'is-error' : '' ?>"
                    placeholder="Enter admin password"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="eye-btn" id="eyeBtn" title="Toggle password visibility">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-admin-login">
            <i class="bi bi-shield-check"></i>
            Access Admin Console
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>

    <div class="security-note">
        <i class="bi bi-lock-fill" style="color:rgba(255, 255, 255, 0.3);flex-shrink:0;"></i>
        Restricted to authorized personnel. Session audit active.
    </div>

    <div style="text-align:center;">
        <a href="../index.php" class="back-link">
            <i class="bi bi-arrow-left"></i>
            Back to Marketplace Storefront
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('eyeBtn')?.addEventListener('click', function () {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    const show = pw.type === 'password';
    pw.type        = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    pw.focus();
});
</script>
</body>
</html>
