<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial Nationwide Checkout Gateway
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'Secure Checkout & Payment';
$uid = (int)$_SESSION['user_id'];

// Fetch current user details
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$user_stmt->bind_param("i", $uid);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();

$regions = getGhanaCampuses();
$ghana_regions_list = getGhanaRegions();
$location_dir = getGhanaLocationDirectory();
$user_resolved_loc = resolveGhanaLocation($current_user['region'] ?? 'Greater Accra Region');
$order_placed = false;
$order_info = [];
$checkout_error = '';

// Live AJAX Delivery Fee Calculation Endpoint
if (isset($_GET['action']) && $_GET['action'] === 'calculate_delivery' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $payload = json_decode(file_get_contents('php://input'), true);
    $raw_items    = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $buyer_campus = trim((string)($payload['buyer_campus'] ?? ($current_user['region'] ?? 'Greater Accra Region')));
    $buyer_region = trim((string)($payload['buyer_region'] ?? ''));
    $promo_code   = strtoupper(trim((string)($payload['promo_code'] ?? '')));

    $prod_stmt = $conn->prepare("
        SELECT p.id, p.seller_id, p.title, p.price, p.region, p.status, u.full_name AS seller_name
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ? LIMIT 1
    ");

    $validated = [];
    $subtotal = 0.00;
    if ($prod_stmt) {
        foreach ($raw_items as $entry) {
            $pid = (int)($entry['id'] ?? 0);
            $qty = max(1, min(99, (int)($entry['quantity'] ?? 1)));
            if ($pid < 1) continue;
            $prod_stmt->bind_param("i", $pid);
            $prod_stmt->execute();
            $prod = $prod_stmt->get_result()->fetch_assoc();
            if ($prod && $prod['status'] === 'available' && (int)$prod['seller_id'] !== $uid) {
                $unit_price = round((float)$prod['price'], 2);
                $subtotal = round($subtotal + ($unit_price * $qty), 2);
                $validated[] = [
                    'product_id'    => $pid,
                    'seller_id'     => (int)$prod['seller_id'],
                    'seller_name'   => $prod['seller_name'] ?? 'Student Seller',
                    'seller_region' => $prod['region'] ?? 'Greater Accra Region',
                    'price'         => $unit_price,
                    'quantity'      => $qty,
                ];
            }
        }
    }

    $delivery = calculateCartDeliverySummary($buyer_campus, $buyer_region, $validated);
    $discount = in_array($promo_code, ['UNIMARKET20', 'WELCOMEGH', 'GHANA2026'], true)
        ? min($subtotal, 20.00)
        : 0.00;
    $total = max(0.00, round($subtotal + $delivery['delivery_fee'] - $discount, 2));

    echo json_encode([
        'status'        => 'ok',
        'subtotal'      => $subtotal,
        'delivery_fee'  => $delivery['delivery_fee'],
        'delivery_tier' => $delivery['delivery_tier'],
        'breakdown'     => $delivery['breakdown'],
        'discount'      => $discount,
        'total'         => $total,
    ]);
    exit();
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $checkout_error = 'Invalid or expired security token. Please refresh and try again.';
    } else {
        $region           = sanitize($_POST['region'] ?? 'Greater Accra Region');
        $buyer_region     = sanitize($_POST['buyer_region'] ?? '');
        $city             = sanitize($_POST['city'] ?? 'Accra');
        $delivery_address = sanitize($_POST['delivery_address'] ?? 'Home / Office Address');
        $phone            = sanitize($_POST['phone'] ?? ($current_user['phone'] ?? ''));
        $payment_method   = sanitize($_POST['payment_method'] ?? 'mtn_momo');
        $allowed_methods  = ['mtn_momo', 'telecel_cash', 'at_money', 'card', 'cash_on_delivery'];
        if (!in_array($payment_method, $allowed_methods, true)) {
            $payment_method = 'mtn_momo';
        }
        $momo_number = ($payment_method === 'card' || $payment_method === 'cash_on_delivery')
            ? ''
            : sanitize($_POST['momo_number'] ?? $phone);

        // Parse & validate cart items from authoritative DB prices
        $raw_items = json_decode($_POST['cart_items'] ?? '[]', true);
        if (!is_array($raw_items) || empty($raw_items)) {
            $raw_items = $_SESSION['cart'] ?? [];
        }

        $validated_items = [];
        $subtotal = 0.00;
        $prod_stmt = $conn->prepare("
            SELECT p.id, p.seller_id, p.title, p.price, p.region, p.status, u.full_name AS seller_name
            FROM products p
            JOIN users u ON p.seller_id = u.id
            WHERE p.id = ? LIMIT 1
        ");

        if (is_array($raw_items) && $prod_stmt) {
            foreach ($raw_items as $entry) {
                $pid = (int)($entry['id'] ?? 0);
                $qty = max(1, min(99, (int)($entry['quantity'] ?? 1)));
                if ($pid < 1) continue;

                $prod_stmt->bind_param("i", $pid);
                $prod_stmt->execute();
                $prod = $prod_stmt->get_result()->fetch_assoc();
                if (!$prod || $prod['status'] !== 'available') {
                    $checkout_error = 'One or more items in your cart are no longer available.';
                    break;
                }
                if ((int)$prod['seller_id'] === $uid) {
                    $checkout_error = 'You cannot purchase your own listing.';
                    break;
                }
                $unit_price = round((float)$prod['price'], 2);
                $subtotal = round($subtotal + ($unit_price * $qty), 2);
                $validated_items[] = [
                    'product_id'    => $pid,
                    'seller_id'     => (int)$prod['seller_id'],
                    'seller_name'   => $prod['seller_name'] ?? 'Student Seller',
                    'seller_region' => $prod['region'] ?? 'Greater Accra Region',
                    'title'         => $prod['title'],
                    'price'         => $unit_price,
                    'quantity'      => $qty,
                ];
            }
        }

        if (empty($checkout_error) && empty($validated_items)) {
            $checkout_error = 'Your cart is empty. Please add available items before checking out.';
        }

        if (empty($checkout_error) && (empty($city) || empty($delivery_address) || empty($phone))) {
            $checkout_error = 'Please fill in all required campus delivery and phone details.';
        }

        if (empty($checkout_error)) {
            // Server-side tiered delivery & promo validation
            $delivery = calculateCartDeliverySummary($region, $buyer_region, $validated_items);
            $delivery_fee  = $delivery['delivery_fee'];
            $delivery_tier = $delivery['delivery_tier'];

            $promo_code = strtoupper(trim((string)($_POST['promo_code'] ?? '')));
            $discount = in_array($promo_code, ['UNIMARKET20', 'WELCOMEGH', 'GHANA2026'], true)
                ? min($subtotal, 20.00)
                : 0.00;
            $total         = max(0.00, round($subtotal + $delivery_fee - $discount, 2));
            $customer_name = $current_user['full_name'] ?? ($_SESSION['full_name'] ?? 'UniMarket Buyer');
            $order_code    = 'UM-GH-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));

            $conn->begin_transaction();
            try {
                $order_stmt = $conn->prepare("
                    INSERT INTO orders (order_code, user_id, customer_name, region, city, delivery_address, phone, payment_method, momo_number, subtotal, delivery_fee, delivery_tier, discount, total, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processing')
                ");
                if (!$order_stmt) {
                    throw new Exception('Order preparation failed.');
                }
                $order_stmt->bind_param(
                    "sisssssssddsdd",
                    $order_code,
                    $uid,
                    $customer_name,
                    $region,
                    $city,
                    $delivery_address,
                    $phone,
                    $payment_method,
                    $momo_number,
                    $subtotal,
                    $delivery_fee,
                    $delivery_tier,
                    $discount,
                    $total
                );
                if (!$order_stmt->execute()) {
                    throw new Exception('Order insert failed: ' . $order_stmt->error);
                }

                $new_order_id = (int)$conn->insert_id;
                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, title, price, quantity) VALUES (?, ?, ?, ?, ?)");
                if (!$item_stmt) {
                    throw new Exception('Order items preparation failed.');
                }
                foreach ($validated_items as $vi) {
                    $item_stmt->bind_param("iisdi", $new_order_id, $vi['product_id'], $vi['title'], $vi['price'], $vi['quantity']);
                    if (!$item_stmt->execute()) {
                        throw new Exception('Order item insert failed: ' . $item_stmt->error);
                    }
                }

                $conn->commit();
                $_SESSION['cart'] = [];
                $order_placed = true;
                $order_info = [
                    'order_code'       => $order_code,
                    'region'           => $region,
                    'buyer_region'     => $buyer_region,
                    'city'             => $city,
                    'delivery_address' => $delivery_address,
                    'phone'            => $phone,
                    'payment_method'   => $payment_method,
                    'momo_number'      => $momo_number,
                    'delivery_fee'     => $delivery_fee,
                    'delivery_tier'    => $delivery_tier,
                    'total'            => $total,
                ];
            } catch (Exception $e) {
                $conn->rollback();
                $checkout_error = 'Could not complete your order. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5" style="max-width: 960px;">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php" class="text-muted">Cart</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Checkout</li>
        </ol>
    </nav>

    <?php if ($order_placed): ?>
    <!-- ── ORDER SUCCESS CONFIRMATION ────────────────────────────── -->
    <div class="um-card p-4 p-md-5 text-center">
        <div class="fs-1 text-success mb-3"><i class="bi bi-box-seam" style="font-size:64px;"></i></div>
        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 mb-3" style="font-size:13px;border-radius:20px;">
            Order Placed Successfully
        </span>
        <h2 class="fw-bold mb-2">Thank you for your purchase!</h2>
        <p class="text-muted mb-4" style="max-width:540px;margin:0 auto;">
            Your order reference is <strong><?= $order_info['order_code'] ?></strong>. A confirmation has been registered for <?= sanitize($current_user['full_name']) ?>.
        </p>

        <!-- Order Summary Card -->
        <div class="p-4 rounded-4 bg-light border text-start mb-4 mx-auto" style="max-width:600px;">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <span class="text-muted small">Total Paid in Ghana Cedis:</span>
                <span class="fw-bold fs-4 text-dark"><?= formatPrice($order_info['total']) ?></span>
            </div>
            <div class="small mb-2">
                <strong>Delivery Tier:</strong> <?= htmlspecialchars($order_info['delivery_tier'] ?? 'Standard Delivery') ?> (<?= formatPrice($order_info['delivery_fee'] ?? 0) ?>)
            </div>
            <div class="small mb-2">
                <strong>Delivery Destination:</strong> <?= htmlspecialchars($order_info['delivery_address']) ?>, <?= htmlspecialchars($order_info['city']) ?> (<?= htmlspecialchars($order_info['region']) ?>)
            </div>
            <div class="small mb-2">
                <strong>Contact Phone:</strong> <?= htmlspecialchars($order_info['phone']) ?>
            </div>
            <div class="small mb-3">
                <strong>Payment Method:</strong> <?= strtoupper(str_replace('_', ' ', $order_info['payment_method'])) ?>
            </div>

            <?php if (strpos($order_info['payment_method'], 'momo') !== false || strpos($order_info['payment_method'], 'telecel') !== false): ?>
            <div class="alert alert-warning mb-0 small d-flex align-items-center gap-2">
                <i class="bi bi-phone fs-4 text-warning"></i>
                <div>
                    <strong>Mobile Money USSD Prompt Dispatched:</strong><br>
                    A prompt for <?= formatPrice($order_info['total']) ?> was sent to <strong><?= htmlspecialchars($order_info['momo_number']) ?></strong>.
                    If the popup did not appear automatically, dial <strong>*170#</strong> (MTN) or <strong>*110#</strong> (Telecel) to approve the transaction.
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-success mb-0 small d-flex align-items-center gap-2">
                <i class="bi bi-truck fs-4 text-success"></i>
                <div>
                    <strong>Order Dispatched for Delivery:</strong><br>
                    Our nationwide courier partner will contact you at your phone number prior to arrival at your doorstep.
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="dashboard.php" class="btn btn-navy px-4 py-3">
                <i class="bi bi-arrow-left me-1"></i> Return to Shopping
            </a>
            <a href="orders.php" class="btn btn-emerald px-4 py-3">
                <i class="bi bi-receipt me-1"></i> View Order Status
            </a>
        </div>
    </div>

    <!-- Clear Client Cart -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.UniCart) {
                UniCart.clearCart();
            }
        });
    </script>

    <?php else: ?>
    <!-- ── CHECKOUT FORM ─────────────────────────────────────────── -->
    <?php if (!empty($checkout_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($checkout_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <div class="row g-4">
        <!-- Left: Destination & Payment -->
        <div class="col-lg-7">
            <form method="POST" id="checkoutForm">
                <?= csrfField() ?>
                <!-- Section 1: Delivery Address -->
                <div class="um-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge bg-dark rounded-circle" style="width:24px;height:24px;line-height:16px;">1</span>
                        <h5 class="fw-bold mb-0" style="font-size:16px;">Campus & Delivery Destination</h5>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="um-field-label">Campus / University Institution <span class="text-danger">*</span></label>
                            <select name="region" id="checkoutRegion" class="form-select um-input" required>
                                <?php foreach ($regions as $r): ?>
                                <option value="<?= htmlspecialchars($r) ?>" <?= (($current_user['region'] ?? '') === $r) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="um-field-label">Delivery Region <span class="text-danger">*</span></label>
                            <select name="buyer_region" id="checkoutBuyerRegion" class="form-select um-input" required>
                                <?php foreach ($ghana_regions_list as $gr): ?>
                                <option value="<?= htmlspecialchars($gr) ?>" <?= (($user_resolved_loc['region'] ?? '') === $gr) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($gr) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="um-field-label">Campus Area / City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control um-input"
                                   placeholder="e.g. Legon / Accra, Kumasi, Cape Coast..."
                                   value="<?= htmlspecialchars($current_user['city'] ?? 'Accra') ?>" required>
                        </div>
                        <div class="col-md-7">
                            <label class="um-field-label">Hostel, Hall, or Street Address <span class="text-danger">*</span></label>
                            <input type="text" name="delivery_address" class="form-control um-input"
                                   placeholder="e.g. Jean Nelson Hall, Unity Hall (Conti), Evandy, East Legon..."
                                   value="<?= htmlspecialchars($current_user['address'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="um-field-label">Recipient Phone Number (Ghana) <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" id="checkoutPhone" class="form-control um-input"
                               placeholder="e.g. +233 24 123 4567"
                               value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" required>
                        <small class="text-muted" style="font-size:11px;">The campus courier or student seller will contact you on this number.</small>
                    </div>
                </div>

                <!-- Section 2: Payment Methods -->
                <div class="um-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge bg-dark rounded-circle" style="width:24px;height:24px;line-height:16px;">2</span>
                        <h5 class="fw-bold mb-0" style="font-size:16px;">Select Payment Method</h5>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-3">
                        <!-- MTN Mobile Money -->
                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-white" style="cursor:pointer;" for="pay_mtn">
                            <input type="radio" name="payment_method" id="pay_mtn" value="mtn_momo" checked class="form-check-input mt-0">
                            <div class="p-2 px-3 rounded-2 fw-bold" style="background:#FFCC00;color:#000;font-size:12px;">MTN</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark" style="font-size:14px;">MTN Mobile Money (MoMo)</div>
                                <div class="small text-muted">Direct phone prompt with instant approval (*170#)</div>
                            </div>
                        </label>

                        <!-- Telecel Cash -->
                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-white" style="cursor:pointer;" for="pay_telecel">
                            <input type="radio" name="payment_method" id="pay_telecel" value="telecel_cash" class="form-check-input mt-0">
                            <div class="p-2 px-3 rounded-2 fw-bold" style="background:#E60000;color:#fff;font-size:12px;">TEL</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark" style="font-size:14px;">Telecel Cash (Vodafone Cash)</div>
                                <div class="small text-muted">Wallet USSD payment authorization (*110#)</div>
                            </div>
                        </label>

                        <!-- AT Money -->
                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-white" style="cursor:pointer;" for="pay_at">
                            <input type="radio" name="payment_method" id="pay_at" value="at_money" class="form-check-input mt-0">
                            <div class="p-2 px-3 rounded-2 fw-bold" style="background:#004B93;color:#fff;font-size:12px;">AT</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark" style="font-size:14px;">AT Money (AirtelTigo)</div>
                                <div class="small text-muted">Direct wallet debit</div>
                            </div>
                        </label>

                        <!-- Credit / Debit Card -->
                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-white" style="cursor:pointer;" for="pay_card">
                            <input type="radio" name="payment_method" id="pay_card" value="card" class="form-check-input mt-0">
                            <div class="p-2 px-3 rounded-2 fw-bold" style="background:#0F172A;color:#fff;font-size:12px;"><i class="bi bi-credit-card"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark" style="font-size:14px;">Debit / Credit Card</div>
                                <div class="small text-muted">Visa, Mastercard, and Gh-Link enabled Ghanaian bank cards</div>
                            </div>
                        </label>

                        <!-- Cash on Delivery -->
                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-white" style="cursor:pointer;" for="pay_cod">
                            <input type="radio" name="payment_method" id="pay_cod" value="cash_on_delivery" class="form-check-input mt-0">
                            <div class="p-2 px-3 rounded-2 fw-bold" style="background:#059669;color:#fff;font-size:12px;"><i class="bi bi-cash-stack"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark" style="font-size:14px;">Cash on Delivery (Doorstep)</div>
                                <div class="small text-muted">Pay in cash when package arrives at your home or office</div>
                            </div>
                        </label>
                    </div>

                    <!-- MoMo Number Field -->
                    <div id="momoNumberGroup" class="p-3 rounded-3 bg-light border mt-3">
                        <label class="um-field-label" style="font-size:12px;">Mobile Money Number for Prompt</label>
                        <input type="tel" name="momo_number" id="momoNumber" class="form-control um-input"
                               placeholder="e.g. 0244567890"
                               value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>">
                        <small class="text-muted" style="font-size:11px;">The prompt will be sent directly to this phone number.</small>
                    </div>
                </div>

                <!-- Hidden inputs for form submission -->
                <input type="hidden" name="cart_items" id="hiddenCartItems" value="[]">
                <input type="hidden" name="promo_code" id="hiddenPromoCode" value="">
                <input type="hidden" name="place_order" value="1">

                <button type="submit" id="btnSubmitOrder" class="btn btn-orange w-100 py-3 fw-bold fs-6">
                    <i class="bi bi-lock-fill me-2"></i> Confirm Order & Pay in Ghana Cedis
                </button>
            </form>
        </div>

        <!-- Right: Order Breakdown -->
        <div class="col-lg-5">
            <div class="um-card p-4 sticky-top" style="top:90px;">
                <h5 class="fw-bold mb-3 pb-2 border-bottom" style="font-size:16px;">Order Summary</h5>
                <div id="checkoutItemsList" class="mb-3" style="max-height:280px;overflow-y:auto;">
                    <!-- Injected dynamically via JS -->
                </div>

                <div class="d-flex justify-content-between mb-2 small text-muted">
                    <span>Subtotal:</span>
                    <span class="fw-bold text-dark" id="checkoutSubtotal">GH₵ 0.00</span>
                </div>

                <div class="d-flex justify-content-between align-items-start mb-2 small text-muted gap-2">
                    <span id="checkoutDeliveryTierLabel">Delivery Fee:</span>
                    <span class="fw-bold text-dark flex-shrink-0" id="checkoutDeliveryFee">GH₵ 0.00</span>
                </div>
                <div id="checkoutDeliveryBreakdown" class="mb-2 ps-2 border-start small text-muted" style="display:none;font-size:12px;"></div>

                <div class="d-flex justify-content-between mb-3 small text-muted" id="checkoutDiscountRow" style="display:none;">
                    <span>Promotional Discount:</span>
                    <span class="text-success fw-bold" id="checkoutDiscountAmount">-GH₵ 0.00</span>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top mb-3">
                    <span class="fw-bold fs-5 text-dark">Total:</span>
                    <span class="fw-bold fs-3 text-dark" id="checkoutTotalAmount">GH₵ 0.00</span>
                </div>

                <div class="p-3 rounded-3 bg-light border small text-muted">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-mortarboard-fill text-warning me-1"></i> Student Venture Escrow Protection</div>
                    Funds are secured by UniMarket Escrow until you inspect and accept your delivery at your campus hostel, hall, or home address.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
window.UM_LOCATION_DIR = <?= json_encode($location_dir, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

document.addEventListener('DOMContentLoaded', function () {
    const items = UniCart.getItems();
    const promoCode = (sessionStorage.getItem('unimarket_promo_code') || '').trim();
    let fallbackDiscount = parseFloat(sessionStorage.getItem('unimarket_promo_discount') || '0');
    if (isNaN(fallbackDiscount) || fallbackDiscount < 0) fallbackDiscount = 0;

    if (items.length === 0 && !<?= $order_placed ? 'true' : 'false' ?>) {
        window.location.href = 'cart.php';
        return;
    }

    const itemsContainer = document.getElementById('checkoutItemsList');
    const subtotalEl = document.getElementById('checkoutSubtotal');
    const deliveryTierEl = document.getElementById('checkoutDeliveryTierLabel');
    const deliveryFeeEl = document.getElementById('checkoutDeliveryFee');
    const deliveryBreakdownEl = document.getElementById('checkoutDeliveryBreakdown');
    const totalEl = document.getElementById('checkoutTotalAmount');
    const hiddenCartItems = document.getElementById('hiddenCartItems');
    const hiddenPromoCode = document.getElementById('hiddenPromoCode');
    const discountRow = document.getElementById('checkoutDiscountRow');
    const discountAmount = document.getElementById('checkoutDiscountAmount');
    const campusSelect = document.getElementById('checkoutRegion');
    const regionSelect = document.getElementById('checkoutBuyerRegion');

    let subtotal = 0;
    let itemsHtml = '';
    const esc = window.escapeHtml || (s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])));

    items.forEach(item => {
        const qty = Math.max(1, parseInt(item.quantity, 10) || 1);
        const line = (Number(item.price) || 0) * qty;
        subtotal += line;
        itemsHtml += `
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <div class="text-truncate me-2" style="max-width:200px;">
                    <span class="fw-bold text-dark">${qty}x</span> ${esc(item.title)}
                </div>
                <span class="fw-bold">${window.formatGHS(line)}</span>
            </div>
        `;
    });

    if (itemsContainer) itemsContainer.innerHTML = itemsHtml;
    const payloadItems = items.map(i => ({
        id: Number(i.id),
        quantity: Math.max(1, parseInt(i.quantity, 10) || 1)
    }));
    if (hiddenCartItems) hiddenCartItems.value = JSON.stringify(payloadItems);
    if (hiddenPromoCode) hiddenPromoCode.value = promoCode;

    function updateSummaryUI(data) {
        const sub = Number(data.subtotal ?? subtotal) || 0;
        const fee = Number(data.delivery_fee ?? 0) || 0;
        const disc = Number(data.discount ?? 0) || 0;
        const tot = Number(data.total ?? Math.max(0, sub + fee - disc)) || 0;

        if (subtotalEl) subtotalEl.textContent = window.formatGHS(sub);
        if (deliveryTierEl) deliveryTierEl.textContent = data.delivery_tier || 'Delivery Fee';
        if (deliveryFeeEl) deliveryFeeEl.textContent = window.formatGHS(fee);
        if (totalEl) totalEl.textContent = window.formatGHS(tot);

        if (disc > 0 && discountRow && discountAmount) {
            discountRow.style.display = 'flex';
            discountAmount.textContent = '- ' + window.formatGHS(disc);
        } else if (discountRow) {
            discountRow.style.display = 'none';
        }

        if (deliveryBreakdownEl) {
            const breakdown = Array.isArray(data.breakdown) ? data.breakdown : [];
            if (breakdown.length > 1) {
                deliveryBreakdownEl.innerHTML = breakdown.map(b => `
                    <div class="d-flex justify-content-between align-items-center py-1 gap-2">
                        <span class="text-truncate">${esc(b.seller_name)} · ${esc(b.tier)}</span>
                        <span class="fw-semibold text-dark flex-shrink-0">${window.formatGHS(b.fee)}</span>
                    </div>
                `).join('');
                deliveryBreakdownEl.style.display = 'block';
            } else {
                deliveryBreakdownEl.style.display = 'none';
                deliveryBreakdownEl.innerHTML = '';
            }
        }
    }

    function recalculateDelivery() {
        const buyerCampus = campusSelect ? campusSelect.value : '';
        const buyerRegion = regionSelect ? regionSelect.value : '';

        fetch('checkout.php?action=calculate_delivery', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: payloadItems,
                buyer_campus: buyerCampus,
                buyer_region: buyerRegion,
                promo_code: promoCode
            })
        })
        .then(res => {
            if (!res.ok) throw new Error('Delivery calculation failed');
            return res.json();
        })
        .then(data => {
            if (data && data.status === 'ok') {
                updateSummaryUI(data);
            }
        })
        .catch(() => {
            updateSummaryUI({
                subtotal: subtotal,
                delivery_fee: 15.00,
                delivery_tier: 'Standard Delivery',
                discount: fallbackDiscount,
                total: Math.max(0, subtotal + 15.00 - fallbackDiscount)
            });
        });
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function () {
            const meta = window.UM_LOCATION_DIR && window.UM_LOCATION_DIR[this.value];
            if (meta && meta.region && regionSelect) {
                regionSelect.value = meta.region;
            }
            recalculateDelivery();
        });
    }

    if (regionSelect) {
        regionSelect.addEventListener('change', recalculateDelivery);
    }

    // Initial calculation on page load
    recalculateDelivery();

    // Toggle MoMo field based on selected payment
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const momoGroup = document.getElementById('momoNumberGroup');
            if (momoGroup) {
                if (this.value === 'cash_on_delivery' || this.value === 'card') {
                    momoGroup.style.display = 'none';
                } else {
                    momoGroup.style.display = 'block';
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
