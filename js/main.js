// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Commercial E-Commerce Client Script
// Design: Emerald (#059669) CTA & Deep Navy (#0F172A)
// ════════════════════════════════════════════════════════════

// ── Centralized Ghana Cedi Currency Formatter ────────────────
window.formatGHS = function (amount) {
    const num = Number(amount) || 0;
    return 'GH₵ ' + num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

// ── Toast Notification System ────────────────────────────────
window.showToast = function (message, type = 'success') {
    let container = document.getElementById('umToastStack');
    if (!container) {
        container = document.createElement('div');
        container.id = 'umToastStack';
        container.className = 'um-toast-stack';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'um-toast-alert';

    let icon = 'bi-check-circle-fill text-success';
    if (type === 'danger' || type === 'error') icon = 'bi-exclamation-triangle-fill text-danger';
    if (type === 'warning') icon = 'bi-exclamation-circle-fill text-warning';

    toast.innerHTML = `
        <i class="bi ${icon} fs-5"></i>
        <div class="flex-grow-1" style="font-size:13.5px;font-weight:600;">${message}</div>
        <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>
    `;

    toast.querySelector('.btn-close').addEventListener('click', () => toast.remove());
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        toast.style.transition = 'all 0.25s ease';
        setTimeout(() => toast.remove(), 250);
    }, 3200);
};

// ── Commercial Shopping Cart Engine ──────────────────────────
window.UniCart = {
    STORAGE_KEY: 'unimarket_ghana_cart',

    getItems() {
        try {
            return JSON.parse(localStorage.getItem(this.STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    },

    saveItems(items) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(items));
        this.updateNavBadge();
        this.renderDrawer();
        fetch('cart.php?action=sync', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart: items })
        }).catch(() => {});
    },

    addItem(product) {
        let items = this.getItems();
        const existingIndex = items.findIndex(item => Number(item.id) === Number(product.id));

        if (existingIndex > -1) {
            items[existingIndex].quantity += (product.quantity || 1);
        } else {
            items.push({
                id: Number(product.id),
                title: product.title || 'Marketplace Item',
                price: Number(product.price) || 0,
                image: product.image || '',
                cat_icon: product.cat_icon || 'bi-bag',
                seller: product.seller || 'Verified Merchant',
                region: product.region || 'Greater Accra',
                quantity: product.quantity || 1
            });
        }

        this.saveItems(items);
        window.showToast('Item added to your cart!');

        const drawerEl = document.getElementById('cartDrawer');
        if (drawerEl && window.bootstrap && window.bootstrap.Offcanvas) {
            const offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
            offcanvas.show();
        }
    },

    updateQuantity(productId, delta) {
        let items = this.getItems();
        const target = items.find(item => Number(item.id) === Number(productId));
        if (target) {
            target.quantity += delta;
            if (target.quantity <= 0) {
                items = items.filter(item => Number(item.id) !== Number(productId));
            }
            this.saveItems(items);
        }
    },

    removeItem(productId) {
        let items = this.getItems();
        items = items.filter(item => Number(item.id) !== Number(productId));
        this.saveItems(items);
        window.showToast('Item removed from cart', 'warning');
    },

    clearCart() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.updateNavBadge();
        this.renderDrawer();
    },

    getCount() {
        return this.getItems().reduce((total, item) => total + (item.quantity || 1), 0);
    },

    getSubtotal() {
        return this.getItems().reduce((total, item) => total + (item.price * (item.quantity || 1)), 0);
    },

    updateNavBadge() {
        const badges = document.querySelectorAll('.cart-count-badge');
        const count = this.getCount();
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        });
    },

    renderDrawer() {
        const container = document.getElementById('cartDrawerItems');
        const subtotalEl = document.getElementById('cartDrawerSubtotal');
        const totalEl = document.getElementById('cartDrawerTotal');
        const checkoutBtn = document.getElementById('cartDrawerCheckoutBtn');
        const emptyState = document.getElementById('cartDrawerEmpty');

        if (!container) return;

        const items = this.getItems();
        const subtotal = this.getSubtotal();

        if (items.length === 0) {
            container.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            if (subtotalEl) subtotalEl.textContent = window.formatGHS(0);
            if (totalEl) totalEl.textContent = window.formatGHS(0);
            if (checkoutBtn) {
                checkoutBtn.classList.add('disabled');
                checkoutBtn.setAttribute('disabled', 'disabled');
            }
            return;
        }

        if (emptyState) emptyState.style.display = 'none';
        if (checkoutBtn) {
            checkoutBtn.classList.remove('disabled');
            checkoutBtn.removeAttribute('disabled');
        }

        let html = '';
        items.forEach(item => {
            const imgTag = item.image
                ? `<img src="uploads/${item.image}" alt="${item.title}" class="cart-item-thumb">`
                : `<div class="cart-item-thumb d-flex align-items-center justify-content-center fs-3">${item.cat_icon}</div>`;

            html += `
                <div class="cart-item-row">
                    ${imgTag}
                    <div class="flex-grow-1 min-w-0" style="min-width:0;">
                        <div class="cart-item-name" title="${item.title}">${item.title}</div>
                        <div class="small text-muted mb-1">${item.region}</div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="cart-item-price">${window.formatGHS(item.price)}</span>
                            <div class="qty-stepper">
                                <button type="button" class="qty-step-btn" onclick="UniCart.updateQuantity(${item.id}, -1)">−</button>
                                <span class="qty-val">${item.quantity}</span>
                                <button type="button" class="qty-step-btn" onclick="UniCart.updateQuantity(${item.id}, 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2" onclick="UniCart.removeItem(${item.id})" title="Remove">
                        <i class="bi bi-trash3 fs-6 text-danger"></i>
                    </button>
                </div>
            `;
        });

        container.innerHTML = html;
        if (subtotalEl) subtotalEl.textContent = window.formatGHS(subtotal);
        if (totalEl) totalEl.textContent = window.formatGHS(subtotal);
    }
};

// ── DOM Initialization ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    UniCart.updateNavBadge();
    UniCart.renderDrawer();

    // Attach click listeners to "Add to Cart" buttons
    document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const product = {
                id: this.dataset.id,
                title: this.dataset.title,
                price: parseFloat(this.dataset.price) || 0,
                image: this.dataset.image || '',
                cat_icon: this.dataset.catIcon || 'bi-bag',
                seller: this.dataset.seller || 'Merchant',
                region: this.dataset.region || 'Greater Accra',
                quantity: 1
            };
            UniCart.addItem(product);
        });
    });

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(el => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 4500);

    // Live search filter on marketplace
    const searchInput = document.getElementById('liveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.product-grid-item').forEach(card => {
                const title  = card.dataset.title?.toLowerCase() || '';
                const seller = card.dataset.seller?.toLowerCase() || '';
                const cat    = card.dataset.cat?.toLowerCase() || '';
                const region = card.dataset.region?.toLowerCase() || '';
                const match  = title.includes(q) || seller.includes(q) || cat.includes(q) || region.includes(q);
                card.style.display = match ? '' : 'none';
            });
        });
    }

    // Dynamic price input preview in Ghana Cedis
    const priceInput = document.getElementById('price');
    const pricePreview = document.getElementById('pricePreview');
    if (priceInput && pricePreview) {
        priceInput.addEventListener('input', function () {
            const val = parseFloat(this.value.replace(/[^0-9.]/g, '')) || 0;
            if (val > 0) {
                pricePreview.textContent = 'Formatted: ' + window.formatGHS(val);
            } else {
                pricePreview.textContent = '';
            }
        });
    }

    // Ghana phone formatting helper (+233)
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('blur', function () {
            let val = this.value.replace(/[^0-9+]/g, '');
            if (val.startsWith('0') && val.length === 10) {
                this.value = '+233 ' + val.substring(1, 3) + ' ' + val.substring(3, 6) + ' ' + val.substring(6);
            }
        });
    });

    // Wishlist Toggle
    document.querySelectorAll('.wishlist-btn, .card-wish-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const pid = this.dataset.id;
            fetch(`wishlist-toggle.php?product_id=${pid}`)
                .then(r => r.json())
                .then(data => {
                    const icon = this.querySelector('i');
                    if (data.status === 'added') {
                        if (icon) {
                            icon.classList.replace('bi-heart', 'bi-heart-fill');
                            icon.style.color = '#DC2626';
                        }
                        this.classList.add('active');
                        window.showToast('Item saved to wishlist');
                    } else {
                        if (icon) {
                            icon.classList.replace('bi-heart-fill', 'bi-heart');
                            icon.style.color = '';
                        }
                        this.classList.remove('active');
                        window.showToast('Item removed from wishlist');
                    }
                })
                .catch(() => window.showToast('Could not update saved items', 'error'));
        });
    });

    // Image Upload Previews
    const imgInput = document.getElementById('product_image');
    const imgPreview = document.getElementById('imagePreview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                    document.querySelector('.upload-placeholder')?.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = document.querySelector(this.dataset.target);
            if (target) {
                const isText = target.type === 'text';
                target.type = isText ? 'password' : 'text';
                const icon = this.querySelector('i');
                if (icon) icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
            }
        });
    });
});
