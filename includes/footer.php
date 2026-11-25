<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial Marketplace Footer
// ZERO Emojis — Vector Icons & Clean Layout
// ════════════════════════════════════════════════════════════
?>

<!-- ── SLIDE-OUT CART DRAWER ─────────────────────────────────── -->
<div class="offcanvas offcanvas-end um-cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
    <div class="cart-drawer-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cart3 fs-4" style="color:var(--accent-orange);"></i>
            <div>
                <h5 class="offcanvas-title fw-bold mb-0 text-white" id="cartDrawerLabel" style="font-size:16px;">
                    Shopping Cart
                </h5>
                <span class="small" style="color:#94A3B8;">Nationwide Doorstep Delivery</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Drawer Items Area -->
    <div class="cart-drawer-body flex-grow-1" id="cartDrawerBody">
        <!-- Empty State Container -->
        <div id="cartDrawerEmpty" class="text-center py-5">
            <div class="mb-3 text-muted" style="font-size:48px;">
                <i class="bi bi-cart-x"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Your cart is empty</h5>
            <p class="text-muted small mb-4" style="max-width:300px;margin:0 auto 20px;">Explore electronics, appliances, fashion, and home essentials with instant Ghana Cedis checkout.</p>
            <a href="dashboard.php" class="btn btn-slate px-4 py-2" data-bs-dismiss="offcanvas">
                <i class="bi bi-compass me-1"></i> Start Shopping
            </a>
        </div>

        <!-- Rendered Items -->
        <div id="cartDrawerItems"></div>
    </div>

    <!-- Drawer Footer -->
    <div class="cart-drawer-footer">
        <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Subtotal</span>
            <span class="fw-bold text-dark" id="cartDrawerSubtotal">GH₵ 0.00</span>
        </div>
        <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Delivery Estimate</span>
            <span class="text-success fw-semibold">Calculated at Checkout</span>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top my-2">
            <span class="fw-bold text-dark">Estimated Total:</span>
            <span class="fw-bold text-dark fs-5" id="cartDrawerTotal">GH₵ 0.00</span>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="checkout.php" id="cartDrawerCheckoutBtn" class="btn btn-orange py-3 fw-bold disabled" style="font-size:15px;">
                <i class="bi bi-lock-fill me-1"></i> Proceed to Checkout
            </a>
            <a href="cart.php" class="btn btn-outline-secondary btn-sm py-2" style="border-radius:10px;">
                View Cart Summary
            </a>
        </div>

        <div class="text-center mt-3 small text-muted d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-shield-check text-success"></i> MTN MoMo · Telecel Cash · Gh-Link Secure
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
<!-- ── MULTI-COLUMN COMMERCIAL FOOTER ────────────────────────── -->
<footer class="um-footer-main">
    <div class="container footer-inner-top">
        <div class="row g-4">
            <!-- Brand & Commercial Mission -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="brand-icon-box" style="width:34px;height:34px;font-size:16px;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <span class="fw-bold fs-4 text-white">
                        Uni<span style="color:var(--accent-orange);">Market</span>
                    </span>
                    <span class="brand-country-badge">CAMPUS GH</span>
                </div>
                <p class="mb-3" style="line-height:1.6;font-size:13.5px;color:#94A3B8;">
                    UniMarket Ghana is the flagship commerce ecosystem powering student entrepreneurship across universities and colleges in Ghana. Connecting ambitious student founders, creators, and campus brands with customers nationwide through instant Mobile Money and seamless campus-to-doorstep delivery.
                </p>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <span class="badge bg-dark text-light border border-secondary px-2 py-1"><i class="bi bi-check2-circle text-warning me-1"></i> MTN MoMo</span>
                    <span class="badge bg-dark text-light border border-secondary px-2 py-1"><i class="bi bi-check2-circle text-danger me-1"></i> Telecel Cash</span>
                    <span class="badge bg-dark text-light border border-secondary px-2 py-1"><i class="bi bi-check2-circle text-primary me-1"></i> AT Money</span>
                    <span class="badge bg-dark text-light border border-secondary px-2 py-1"><i class="bi bi-shield-lock-fill text-success me-1"></i> Campus Escrow</span>
                </div>
            </div>

            <!-- Regional Delivery Hubs -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="footer-col-title">Campus Hubs & Regions</h6>
                <ul class="footer-menu-links">
                    <li><a href="dashboard.php?region=University+of+Ghana">University of Ghana (UG Legon)</a></li>
                    <li><a href="dashboard.php?region=KNUST">KNUST (Kumasi Campus)</a></li>
                    <li><a href="dashboard.php?region=UCC">University of Cape Coast (UCC)</a></li>
                    <li><a href="dashboard.php?region=<?= urlencode('Cape Coast Technical University (CCTU)') ?>">Cape Coast Technical (CCTU)</a></li>
                    <li><a href="dashboard.php?region=UPSA">UPSA & Madina Hub</a></li>
                    <li><a href="dashboard.php?region=Ashesi">Ashesi University (Berekuso)</a></li>
                    <li><a href="dashboard.php">All 16 Regions & Campuses</a></li>
                </ul>
            </div>

            <!-- Mainstream Categories -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-col-title">Campus Commerce</h6>
                <ul class="footer-menu-links">
                    <li><a href="dashboard.php">Student Brands & Merch</a></li>
                    <li><a href="dashboard.php?cat=2">Tech & Gadgets</a></li>
                    <li><a href="dashboard.php?cat=1">Fashion & Streetwear</a></li>
                    <li><a href="dashboard.php?cat=3">Beauty & Care</a></li>
                    <li><a href="dashboard.php?cat=4">Food & Snacks</a></li>
                    <li><a href="dashboard.php?cat=5">Books & Essentials</a></li>
                </ul>
            </div>

            <!-- Customer Care & Security -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-col-title">Student & Founder Support</h6>
                <p class="small mb-3" style="line-height:1.6;color:#94A3B8;">
                    Our campus support team is active Monday through Saturday, assisting student merchants and buyers nationwide.
                </p>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2 small" style="color:#CBD5E1;">
                        <i class="bi bi-envelope text-success fs-5"></i>
                        <span>support@unimarket.com.gh</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small" style="color:#CBD5E1;">
                        <i class="bi bi-telephone text-success fs-5"></i>
                        <span>+233596668429</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small" style="color:#CBD5E1;">
                        <i class="bi bi-whatsapp text-success fs-5"></i>
                        <span>+233502281863</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Legal Bar -->
    <div class="footer-inner-bottom">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <span>
                &copy; <?= date('Y') ?> UniMarket Ghana. Empowering Student Entrepreneurs Across Ghanaian Campuses.
            </span>
            <div class="d-flex gap-3 small">
                <span>Verified Student Founders</span>
                <span>•</span>
                <span>Campus MoMo Escrow</span>
                <span>•</span>
                <span>Buyer Protection</span>
            </div>
        </div>
    </div>
</footer>

<!-- ── STICKY MOBILE BOTTOM NAVIGATION BAR (< md) ─────────────── -->
<nav class="um-mobile-bottom-nav d-md-none" aria-label="Mobile Navigation">
    <a href="dashboard.php" class="um-bottom-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-house-door<?= $current_page === 'dashboard.php' ? '-fill' : '' ?>"></i>
        <span>Home</span>
    </a>
    <a href="dashboard.php#liveSearch" class="um-bottom-nav-item" id="mobileBottomSearchBtn">
        <i class="bi bi-search"></i>
        <span>Search</span>
    </a>
    <a href="post.php" class="um-bottom-nav-item <?= $current_page === 'post.php' ? 'active' : '' ?>">
        <span class="um-bottom-nav-sell"><i class="bi bi-plus-lg"></i></span>
        <span>Sell</span>
    </a>
    <a href="chat.php" class="um-bottom-nav-item <?= $current_page === 'chat.php' ? 'active' : '' ?>">
        <i class="bi bi-chat-dots<?= $current_page === 'chat.php' ? '-fill' : '' ?>"></i>
        <span>Messages</span>
        <?php if (!empty($unread_messages) && $unread_messages > 0): ?>
            <span class="um-bottom-badge"><?= (int)$unread_messages ?></span>
        <?php endif; ?>
    </a>
    <a href="orders.php" class="um-bottom-nav-item <?= ($current_page === 'orders.php' || $current_page === 'profile.php') ? 'active' : '' ?>">
        <i class="bi bi-bag-check<?= ($current_page === 'orders.php' || $current_page === 'profile.php') ? '-fill' : '' ?>"></i>
        <span>Orders</span>
    </a>
</nav>
<?php endif; ?>

<!-- Bootstrap 5 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Application Core Scripts -->
<script src="js/main.js?v=<?= @filemtime(__DIR__ . '/../js/main.js') ?: '2' ?>"></script>

</body>
</html>
