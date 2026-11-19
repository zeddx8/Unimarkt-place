<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Shopping Cart Management
// ════════════════════════════════════════════════════════════

require_once 'includes/db.php';
requireLogin();

$page_title = 'Shopping Cart';

// Handle AJAX sync from client UniCart engine
if (isset($_GET['action']) && $_GET['action'] === 'sync' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (isset($data['cart']) && is_array($data['cart'])) {
        $_SESSION['cart'] = $data['cart'];
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'count' => count($_SESSION['cart'])]);
        exit();
    }
}

// Remove item action fallback
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($i) use ($remove_id) {
            return (int)($i['id'] ?? 0) !== $remove_id;
        }));
    }
    header('Location: cart.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-muted">Home</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Shopping Cart</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1" style="font-size:26px;">Shopping Cart</h2>
            <p class="text-muted small mb-0">Review items before proceeding to secure Mobile Money & Card checkout to support student entrepreneurs</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3 py-2" style="border-radius:var(--radius-md);">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
        </a>
    </div>

    <div class="row g-4" id="fullCartView">
        <!-- Left: Item Table -->
        <div class="col-lg-8">
            <div class="um-card p-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="cartTable">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th style="min-width:260px;">Product</th>
                                <th>Unit Price</th>
                                <th class="text-center">Quantity</th>
                                <th>Total</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="fullCartTableBody">
                            <!-- Injected dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <div id="fullCartEmptyMessage" class="text-center py-5" style="display:none;">
                    <div style="font-size:52px;margin-bottom:12px;" class="text-muted"><i class="bi bi-cart-x"></i></div>
                    <h4 class="fw-bold text-dark mb-2">Your shopping cart is empty</h4>
                    <p class="text-muted small mb-4" style="max-width:360px;margin:0 auto 20px;">You haven't added any products to your cart yet. Explore student brand drops and campus essentials.</p>
                    <a href="dashboard.php" class="btn btn-navy px-4 py-2">
                        <i class="bi bi-compass me-1"></i> Start Shopping
                    </a>
                </div>
            </div>

            <!-- Buyer Guarantee Box -->
            <div class="p-3 mt-3 rounded-3 d-flex align-items-center gap-3 bg-white border">
                <div style="font-size:32px;color:var(--accent-emerald);"><i class="bi bi-shield-check"></i></div>
                <div class="small">
                    <strong class="text-dark">UniMarket Campus Escrow & Buyer Protection:</strong>
                    <div class="text-muted">Funds are held in secure escrow until your delivery arrives safely at your campus hostel or address. You have 7 days to request replacement or refund if items do not match description.</div>
                </div>
            </div>
        </div>

        <!-- Right: Order Summary -->
        <div class="col-lg-4">
            <div class="um-card p-4">
                <h5 class="fw-bold mb-3" style="font-size:18px;">Order Summary</h5>

                <div class="d-flex justify-content-between mb-2 small text-muted">
                    <span>Items Subtotal:</span>
                    <span class="fw-bold text-dark" id="summarySubtotal">GH₵ 0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2 small text-muted">
                    <span>Estimated Shipping:</span>
                    <span class="text-success fw-bold">GH₵ 25.00</span>
                </div>

                <hr class="my-3">

                <!-- Promo Code -->
                <div class="mb-3">
                    <label class="um-field-label" style="font-size:12px;">Promotional Discount Code</label>
                    <div class="input-group">
                        <input type="text" id="promoInput" class="form-control um-input py-2" placeholder="e.g. UNIMARKET20">
                        <button type="button" class="btn btn-navy px-3" id="applyPromoBtn" style="border-radius:0 12px 12px 0;">Apply</button>
                    </div>
                    <div id="promoNotice" class="small mt-1" style="display:none;"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 pt-2 border-top">
                    <span class="fw-bold text-dark fs-6">Estimated Total:</span>
                    <span class="fw-bold text-dark fs-4" id="summaryGrandTotal">GH₵ 0.00</span>
                </div>

                <a href="checkout.php" id="btnProceedCheckout" class="btn btn-orange w-100 py-3 fw-bold disabled">
                    <i class="bi bi-lock-fill me-1"></i> Proceed to Checkout
                </a>

                <div class="text-center mt-3 text-muted small">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Supports MTN MoMo, Telecel Cash & Visa / Mastercard
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    renderFullCartPage();

    const promoBtn = document.getElementById('applyPromoBtn');
    const promoInput = document.getElementById('promoInput');
    const promoNotice = document.getElementById('promoNotice');

    if (promoBtn) {
        promoBtn.addEventListener('click', function () {
            const code = promoInput.value.trim().toUpperCase();
            if (code === 'UNIMARKET20' || code === 'WELCOMEGH' || code === 'GHANA2026') {
                promoNotice.className = 'small text-success mt-1 fw-bold';
                promoNotice.textContent = 'Coupon applied! GH₵ 20.00 discount activated.';
                promoNotice.style.display = 'block';
                sessionStorage.setItem('unimarket_promo_discount', '20.00');
                renderFullCartPage();
            } else if (code) {
                promoNotice.className = 'small text-danger mt-1';
                promoNotice.textContent = 'Invalid promo code. Try UNIMARKET20.';
                promoNotice.style.display = 'block';
            }
        });
    }
});

function renderFullCartPage() {
    const tbody = document.getElementById('fullCartTableBody');
    const emptyBox = document.getElementById('fullCartEmptyMessage');
    const subtotalEl = document.getElementById('summarySubtotal');
    const grandTotalEl = document.getElementById('summaryGrandTotal');
    const checkoutBtn = document.getElementById('btnProceedCheckout');
    const cartTable = document.getElementById('cartTable');

    const items = UniCart.getItems();
    const discount = parseFloat(sessionStorage.getItem('unimarket_promo_discount') || '0');
    const shipping = items.length > 0 ? 25.00 : 0.00;

    if (items.length === 0) {
        if (tbody) tbody.innerHTML = '';
        if (cartTable) cartTable.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'block';
        if (subtotalEl) subtotalEl.textContent = window.formatGHS(0);
        if (grandTotalEl) grandTotalEl.textContent = window.formatGHS(0);
        if (checkoutBtn) {
            checkoutBtn.classList.add('disabled');
            checkoutBtn.setAttribute('disabled', 'disabled');
        }
        return;
    }

    if (cartTable) cartTable.style.display = 'table';
    if (emptyBox) emptyBox.style.display = 'none';
    if (checkoutBtn) {
        checkoutBtn.classList.remove('disabled');
        checkoutBtn.removeAttribute('disabled');
    }

    let rowsHtml = '';
    let total = 0;

    items.forEach(item => {
        const lineTotal = item.price * item.quantity;
        total += lineTotal;
        const imgTag = item.image
            ? `<img src="uploads/${item.image}" alt="${item.title}" style="width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #E2E8F0;">`
            : `<div style="width:54px;height:54px;background:#F1F5F9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:24px;">${item.cat_icon ? ('<i class="bi ' + item.cat_icon + ' text-secondary fs-4"></i>') : '<i class="bi bi-box text-secondary fs-4"></i>'}</div>`;

        rowsHtml += `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${imgTag}
                        <div>
                            <div class="fw-bold text-dark" style="font-size:14.5px;">${item.title}</div>
                            <div class="text-muted small">${item.region || 'Nationwide'} · Store: ${item.seller || 'Merchant'}</div>
                        </div>
                    </div>
                </td>
                <td class="fw-bold">${window.formatGHS(item.price)}</td>
                <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-2 bg-light border rounded-3 p-1">
                        <button type="button" class="btn btn-sm btn-link text-dark p-0 px-1 text-decoration-none fw-bold" onclick="updateQty(${item.id}, -1)">−</button>
                        <span class="fw-bold px-2">${item.quantity}</span>
                        <button type="button" class="btn btn-sm btn-link text-dark p-0 px-1 text-decoration-none fw-bold" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                </td>
                <td class="fw-bold text-dark">${window.formatGHS(lineTotal)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeCartItem(${item.id})" title="Remove">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    if (tbody) tbody.innerHTML = rowsHtml;
    const finalTotal = Math.max(0, total + shipping - discount);

    if (subtotalEl) subtotalEl.textContent = window.formatGHS(total);
    if (grandTotalEl) grandTotalEl.textContent = window.formatGHS(finalTotal);
}

function updateQty(id, delta) {
    UniCart.updateQuantity(id, delta);
    renderFullCartPage();
}

function removeCartItem(id) {
    UniCart.removeItem(id);
    renderFullCartPage();
}
</script>

<?php include 'includes/footer.php'; ?>
