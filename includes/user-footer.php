    </div><!-- /.user-portal-container -->

    <!-- SLIDE-IN CART & ORDER DRAWER -->
    <div id="cartOverlay" class="cart-overlay" onclick="closeCartDrawer()"></div>
    <div id="cartDrawer" class="cart-drawer">
        <div class="cart-drawer-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:8px; background:var(--user-primary-light); color:var(--user-primary); display:flex; align-items:center; justify-content:center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.125rem; font-weight:700; color:var(--user-dark);">Hospital Procurement Cart</h3>
                    <p style="margin:0; font-size:0.75rem; color:#64748b;">Direct Pro-Forma &amp; Purchase Order Checkout</p>
                </div>
            </div>
            <button type="button" class="btn-close-cart" onclick="closeCartDrawer()">&times;</button>
        </div>

        <div class="cart-drawer-body" id="cartItemList">
            <!-- Dynamic Cart Items Rendered via JavaScript -->
        </div>

        <div class="cart-drawer-footer">
            <div class="cart-summary-row">
                <span>Subtotal</span>
                <span id="cartSubtotal">$0.00</span>
            </div>
            <div class="cart-summary-row">
                <span>Estimated Medical Tax (9%)</span>
                <span id="cartTax">$0.00</span>
            </div>
            <div class="cart-summary-row">
                <span>Hospital Logistics &amp; Sterilization Handling</span>
                <span id="cartShipping">$120.00</span>
            </div>
            <div class="cart-summary-row total">
                <span>Total Estimated Cost</span>
                <span id="cartTotal">$0.00</span>
            </div>

            <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
                <button type="button" class="btn btn-primary btn-block" id="btnProceedCheckout" onclick="proceedToCheckoutModal()" style="padding:14px; font-weight:700; font-size:0.9375rem;">
                    <span>Proceed to Pro-Forma / PO Checkout &rarr;</span>
                </button>
                <button type="button" class="btn btn-secondary btn-block" onclick="clearCart()" style="font-size:0.8125rem; padding:8px;">
                    Clear Cart
                </button>
            </div>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div id="checkoutModal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center; padding:20px; overflow-y:auto;">
        <div class="modal-card" style="background:#ffffff; border-radius:16px; max-width:650px; width:100%; max-height:90vh; overflow-y:auto; padding:30px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;">
                <div>
                    <h3 style="margin:0; font-size:1.375rem; font-weight:700; color:var(--user-dark);">Confirm Hospital Purchase Order</h3>
                    <p style="margin:4px 0 0; font-size:0.8125rem; color:#64748b;">Verify delivery clinic address &amp; procurement terms</p>
                </div>
                <button type="button" onclick="closeCheckoutModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>

            <form id="orderForm" method="post" action="/surgical_equipment/user/process-order.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="cart_data" id="cartDataJson">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
                    <div style="grid-column:1/-1;">
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Consignee / Doctor Name *</label>
                        <input type="text" name="shipping_name" class="form-control" value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Contact Phone / WhatsApp *</label>
                        <input type="text" name="shipping_phone" class="form-control" placeholder="+1-555-0199" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Official Email *</label>
                        <input type="email" name="shipping_email" class="form-control" placeholder="doctor@hospital.org" required>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Hospital Department &amp; Shipping Address *</label>
                        <textarea name="shipping_address" class="form-control" rows="2" placeholder="e.g. Surgical Suite Wing B, 500 Medical Center Parkway" required></textarea>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">City / State</label>
                        <input type="text" name="city" class="form-control" placeholder="e.g. Boston, MA">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Postal / Zip Code</label>
                        <input type="text" name="pincode" class="form-control" placeholder="e.g. 02115">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Procurement Terms / Payment Method *</label>
                        <select name="payment_method" class="form-control">
                            <option value="Pro-forma Medical Invoice / PO">Pro-forma Medical Invoice (Net 30)</option>
                            <option value="Hospital Purchase Order Reference">Hospital Purchase Order (Institutional Credit)</option>
                            <option value="Wire Transfer / Bank RTGS">Bank Wire Transfer (Advance RTGS)</option>
                            <option value="Cash on Delivery / Direct Inspection">Payment on Delivery &amp; Inspection</option>
                        </select>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Clinical Notes / Special Autoclave Instructions</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Pre-sterilized double packaging required for immediate theater readiness."></textarea>
                    </div>
                </div>

                <div style="background:#f8fafc; padding:14px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1rem; color:var(--user-dark);">
                        <span>Total Payable Amount:</span>
                        <span id="modalGrandTotal">$0.00</span>
                    </div>
                    <p style="margin:4px 0 0; font-size:0.75rem; color:#64748b;">Upon placement, a formal order reference with tracking and an official Pro-Forma Invoice will be generated.</p>
                </div>

                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeCheckoutModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="padding:12px 24px; font-weight:700;">
                        Confirm &amp; Place Medical Order &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Styles for Cart Drawer & Modals -->
    <style>
        .cart-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1500;
            opacity: 0; pointer-events: none; transition: opacity 0.25s ease;
        }
        .cart-overlay.open { opacity: 1; pointer-events: auto; }

        .cart-drawer {
            position: fixed; top: 0; right: 0; bottom: 0; width: 420px; max-width: 100vw;
            background: #ffffff; z-index: 1600; box-shadow: -10px 0 30px rgba(0,0,0,0.15);
            display: flex; flex-direction: column;
            transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .cart-drawer.open { transform: translateX(0); }

        .cart-drawer-header {
            padding: 20px 24px; border-bottom: 1px solid var(--user-border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .btn-close-cart {
            background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; line-height: 1;
        }

        .cart-drawer-body {
            flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 14px;
        }

        .cart-item-row {
            display: flex; gap: 12px; padding: 12px; background: #f8faf5;
            border: 1px solid var(--user-border); border-radius: 10px; align-items: center;
        }
        .cart-item-img {
            width: 50px; height: 50px; border-radius: 6px; object-fit: cover; background: #e2e8f0; flex-shrink: 0;
        }
        .cart-item-details { flex: 1; min-width: 0; }
        .cart-item-title {
            font-size: 0.875rem; font-weight: 700; color: var(--user-dark);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .cart-item-sku { font-size: 0.6875rem; color: #64748b; font-family: monospace; }
        .cart-item-price { font-size: 0.875rem; font-weight: 700; color: var(--user-primary); }

        .cart-qty-controls {
            display: flex; align-items: center; gap: 6px;
        }
        .cart-qty-btn {
            width: 24px; height: 24px; border-radius: 4px; border: 1px solid #cbd5e1;
            background: #ffffff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem; cursor: pointer;
        }
        .cart-qty-num { font-size: 0.8125rem; font-weight: 700; min-width: 18px; text-align: center; }

        .cart-drawer-footer {
            padding: 20px 24px; border-top: 1px solid var(--user-border); background: #fafbfa;
        }
        .cart-summary-row {
            display: flex; justify-content: space-between; font-size: 0.8125rem; color: #64748b; margin-bottom: 6px;
        }
        .cart-summary-row.total {
            font-size: 1.0625rem; font-weight: 800; color: var(--user-dark);
            border-top: 1px dashed #cbd5e1; padding-top: 8px; margin-top: 8px; margin-bottom: 0;
        }
    </style>

    <!-- Cart Management Script -->
    <script>
        const CART_KEY = 'apex_hospital_cart_v1';

        function getCart() {
            try {
                return JSON.parse(localStorage.getItem(CART_KEY)) || [];
            } catch(e) {
                return [];
            }
        }

        function saveCart(cart) {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            updateCartUI();
        }

        function addToCart(product) {
            let cart = getCart();
            let existing = cart.find(item => item.id === product.id);
            if (existing) {
                existing.qty = (existing.qty || 1) + (product.qty || 1);
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    price: parseFloat(product.price) || 0,
                    image: product.image || '/surgical_equipment/assets/images/placeholder.jpg',
                    qty: product.qty || 1
                });
            }
            saveCart(cart);
            openCartDrawer();
        }

        function updateItemQty(id, delta) {
            let cart = getCart();
            let item = cart.find(i => i.id === id);
            if (item) {
                item.qty += delta;
                if (item.qty <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
            }
            saveCart(cart);
        }

        function clearCart() {
            if (confirm('Clear all items from your procurement cart?')) {
                localStorage.removeItem(CART_KEY);
                updateCartUI();
            }
        }

        function updateCartUI() {
            const cart = getCart();
            const countEl = document.getElementById('navCartCount');
            const listEl = document.getElementById('cartItemList');
            const subtotalEl = document.getElementById('cartSubtotal');
            const taxEl = document.getElementById('cartTax');
            const totalEl = document.getElementById('cartTotal');
            const btnProceed = document.getElementById('btnProceedCheckout');

            const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);
            if (countEl) countEl.textContent = totalItems;

            if (!listEl) return;

            if (cart.length === 0) {
                listEl.innerHTML = `
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px; display:block;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <p style="font-weight:600; font-size:0.9375rem; color:#475569; margin-bottom:4px;">Your Cart is Empty</p>
                        <p style="font-size:0.8125rem;">Explore our surgical catalogue and add equipment to request an order.</p>
                        <a href="/surgical_equipment/user/shop.php" class="btn btn-primary btn-sm" style="margin-top:14px;">Browse Catalogue</a>
                    </div>
                `;
                subtotalEl.textContent = '$0.00';
                taxEl.textContent = '$0.00';
                totalEl.textContent = '$0.00';
                if (btnProceed) btnProceed.disabled = true;
                return;
            }

            if (btnProceed) btnProceed.disabled = false;

            let subtotal = 0;
            let html = '';

            cart.forEach(item => {
                const itemTotal = item.price * item.qty;
                subtotal += itemTotal;
                html += `
                    <div class="cart-item-row">
                        <img src="${item.image}" alt="${item.name}" class="cart-item-img" onerror="this.src='/surgical_equipment/assets/images/placeholder.jpg'">
                        <div class="cart-item-details">
                            <div class="cart-item-title" title="${item.name}">${item.name}</div>
                            <div class="cart-item-sku">${item.sku || 'SKU-GEN'}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                        </div>
                        <div class="cart-qty-controls">
                            <button type="button" class="cart-qty-btn" onclick="updateItemQty(${item.id}, -1)">-</button>
                            <span class="cart-qty-num">${item.qty}</span>
                            <button type="button" class="cart-qty-btn" onclick="updateItemQty(${item.id}, 1)">+</button>
                        </div>
                    </div>
                `;
            });

            const tax = subtotal * 0.09;
            const shipping = subtotal > 0 ? 120.00 : 0.00;
            const grandTotal = subtotal + tax + shipping;

            listEl.innerHTML = html;
            subtotalEl.textContent = '$' + subtotal.toFixed(2);
            taxEl.textContent = '$' + tax.toFixed(2);
            totalEl.textContent = '$' + grandTotal.toFixed(2);
        }

        function openCartDrawer() {
            updateCartUI();
            document.getElementById('cartOverlay').classList.add('open');
            document.getElementById('cartDrawer').classList.add('open');
        }

        function closeCartDrawer() {
            document.getElementById('cartOverlay').classList.remove('open');
            document.getElementById('cartDrawer').classList.remove('open');
        }

        function proceedToCheckoutModal() {
            const cart = getCart();
            if (cart.length === 0) return;

            document.getElementById('cartDataJson').value = JSON.stringify(cart);
            let subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
            let grandTotal = subtotal + (subtotal * 0.09) + 120.00;
            document.getElementById('modalGrandTotal').textContent = '$' + grandTotal.toFixed(2);

            closeCartDrawer();
            document.getElementById('checkoutModal').style.display = 'flex';
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updateCartUI);
    </script>

</body>
</html>
