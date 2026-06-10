//  POS JavaScript

// State
let cart       = {};   // { product_id: { name, price, qty, stock } }
let lastOrderId = null;
let lastReceiptNum = null;

// Add to Cart
function addToCart(card) {
    const id    = card.dataset.id;
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const stock = parseInt(card.dataset.stock);

    if (stock === 0) return;

    if (cart[id]) {
        if (cart[id].qty >= stock) {
            showToast('Maximum stock reached for this product.', 'warning');
            return;
        }
        cart[id].qty++;
    } else {
        cart[id] = { name, price, qty: 1, stock };
    }

    renderCart();
    showToast(`${name} added to cart.`, 'success');
}

// Render Cart
function renderCart() {
    const container = document.getElementById('cart-items');
    const keys      = Object.keys(cart);

    // Update count badge
    const totalQty = keys.reduce((s, id) => s + cart[id].qty, 0);
    document.getElementById('cart-count').textContent = totalQty;

    if (keys.length === 0) {
        container.innerHTML = `
            <div class="cart-empty" id="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <p>Cart is empty.<br>Tap a product to add.</p>
            </div>`;
        document.getElementById('total-items').textContent = '0';
        document.getElementById('total-amount').textContent = '₱0.00';
        setCartButtons(false);
        return;
    }

    // Hide empty state
    const empty = document.getElementById('cart-empty');
    if (empty) empty.style.display = 'none';

    // Build items HTML
    let html = '';
    let grandTotal = 0;
    let itemCount  = 0;

    keys.forEach(id => {
        const item = cart[id];
        const sub  = item.price * item.qty;
        grandTotal += sub;
        itemCount  += item.qty;

        html += `
        <div class="cart-item" id="cart-item-${id}">
            <div>
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
            </div>
            <div>
                <div class="cart-item-subtotal">₱${sub.toFixed(2)}</div>
                <div class="qty-controls">
                    <button class="qty-btn remove" onclick="changeQty('${id}', -1)" title="Decrease">−</button>
                    <span class="qty-value">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty('${id}', 1)" title="Increase">+</button>
                </div>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    document.getElementById('total-items').textContent = itemCount + (itemCount === 1 ? ' item' : ' items');
    document.getElementById('total-amount').textContent = '₱' + grandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    setCartButtons(true);
}

function setCartButtons(enabled) {
    document.getElementById('btn-checkout').disabled     = !enabled;
    document.getElementById('btn-clear-bottom').disabled = !enabled;
}

// Change Quantity
function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;

    if (cart[id].qty <= 0) {
        delete cart[id];
    } else if (cart[id].qty > cart[id].stock) {
        cart[id].qty = card[id].stock;
        showToast('Maximum stock reached.', 'warning');
    }

    renderCart();
}

// Clear Cart 
function clearCart() {
    if (!confirm('Clear all items from cart?')) return;
    cart = {};
    renderCart();
}

// Open Checkout Modal
function openCheckout() {
    const keys = Object.keys(cart);
    if (keys.length === 0) return;

    let html = '';
    let total = 0;

    keys.forEach(id => {
        const item = cart[id];
        const sub  = item.price * item.qty;
        total += sub;
        html += `
        <div class="order-summary-item">
            <div>
                <div class="name">${item.name}</div>
                <div class="qty">x${item.qty} @ ₱${item.price.toFixed(2)}</div>
            </div>
            <div class="sub">₱${sub.toFixed(2)}</div>
        </div>`;
    });

    document.getElementById('order-summary-list').innerHTML = html;
    document.getElementById('modal-total-amount').textContent = '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('modal-checkout').classList.add('open');
}

// Process Checkout (AJAX - PHP)
async function processCheckout() {
    const btn     = document.getElementById('btn-confirm');
    const text    = document.getElementById('confirm-text');
    const spinner = document.getElementById('confirm-spinner');

    if (btn.disabled) return;
    btn.disabled      = true;
    text.textContent  = 'Processing...';
    spinner.style.display = 'inline-block';

    const fd = new FormData();
    fd.append('action', 'checkout');
    fd.append('cart', JSON.stringify(cart));

    try {
        const res  = await fetch('../cashier/pos.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await res.json();

        if (data.success) {
            lastOrderId   = data.order_id;
            lastReceiptNum = data.receipt_number;

            // Close checkout step
            closeModal('modal-checkout');

            // Populate the pending modal with information
            document.getElementById('pending-order-id').textContent = data.order_id;
    
            // Extract the total amount from your UI total string (e.g., "₱149.00")
            const totalAmountText = document.getElementById('total-amount').textContent;
            document.getElementById('pending-total').textContent = totalAmountText;

            // Open the Awaiting Payment modal
             document.getElementById('modal-pending').classList.add('open');

        } else {
            showToast(data.message || 'Checkout failed.', 'danger');
        }

    } catch (e) {
        showToast('Network error. Please try again.', 'danger');
    } finally {
        btn.disabled      = false;
        text.textContent  = 'Confirm & Process';
        spinner.style.display = 'none';
    }
}

// Confirm - pending to completed
async function confirmOrder() {
    const btn     = document.getElementById('btn-confirm-order');
    const text    = document.getElementById('confirm-order-text');
    const spinner = document.getElementById('confirm-order-spinner');

    if (btn.disabled) return;
    btn.disabled          = true;
    text.textContent      = 'Confirming...';
    spinner.style.display = 'inline-block';

    const fd = new FormData();
    fd.append('action',   'confirm_order');
    fd.append('order_id', lastOrderId);

    try {
        const res  = await fetch('../cashier/pos.php', {
            method: 'POST', credentials: 'same-origin', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
            lastReceiptNum = data.receipt_number;
            closeModal('modal-pending');
            document.getElementById('success-receipt-num').textContent = data.receipt_number;
            document.getElementById('modal-success').classList.add('open');
        } else {
            showToast(data.message || 'Failed to confirm.', 'danger');
        }

    } catch (e) {
        showToast('Network error. Please try again.', 'danger');
    } finally {
        btn.disabled          = false;
        text.textContent      = 'Confirm Payment';
        spinner.style.display = 'none';
    }
}

//  Cancel - pending to cancelled
async function cancelOrder() {
    if (!confirm('Cancel this order?')) return;

    const fd = new FormData();
    fd.append('action',   'cancel_order');
    fd.append('order_id', lastOrderId);

    try {
        const res  = await fetch('../cashier/pos.php', {
            method: 'POST', credentials: 'same-origin', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
            showToast('Order cancelled.', 'warning');
            closeModal('modal-pending');
            lastOrderId = null;
            cart = {};
            renderCart();
        } else {
            showToast(data.message || 'Failed to cancel.', 'danger');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'danger');
    }
}

// Post-checkout Actions
function resetPOS() {
    cart = {};
    lastOrderId = null;
    lastReceiptNum = null;
    renderCart();
    closeModal('modal-success');
}

function viewReceipt() {
    if (lastOrderId) {
        window.open(`/pos_system/cashier/receipt.php?order_id=${lastOrderId}`, '_blank');
    }
    resetPOS();
}

// Modal Helpers
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => {
        if (e.target === m && m.id !== 'modal-success') m.classList.remove('open');
    });
});

// Search
document.getElementById('search-input').addEventListener('input', function () {
    filterProducts();
});

// Category Filter
document.querySelectorAll('.cat-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        filterProducts();
    });
});

function filterProducts() {
    const query  = document.getElementById('search-input').value.toLowerCase().trim();
    const catId  = document.querySelector('.cat-tab.active')?.dataset.cat || 'all';
    const cards  = document.querySelectorAll('.product-card');
    let   visible = 0;

    cards.forEach(card => {
        const name     = card.dataset.name.toLowerCase();
        const cardCat  = card.dataset.cat;
        const matchSearch = !query || name.includes(query);
        const matchCat    = catId === 'all' || cardCat === catId;

        if (matchSearch && matchCat) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show no results message
    let noResult = document.getElementById('no-results');
    if (visible === 0) {
        if (!noResult) {
            noResult = document.createElement('div');
            noResult.id = 'no-results';
            noResult.className = 'no-products';
            noResult.innerHTML = '<div class="no-products-icon">🔍</div><p>No products found.</p>';
            document.getElementById('products-grid').appendChild(noResult);
        }
        noResult.style.display = '';
    } else if (noResult) {
        noResult.style.display = 'none';
    }
}

// Resume pending order 
document.addEventListener('DOMContentLoaded', function(){
    
    // Check if the window context has global resumed order parameters set by PHP
    if(window.RESUME_ORDER_ID && window.RESUME_CART){
       
        // Link our active tracking variables to the parameters sent by the database
        lastOrderId = window.RESUME_ORDER_ID;
        cart        = window.RESUME_CART;

        // Loop through all items and update available stock settings based on current HTML data definitions
        Object.keys(cart).forEach(id =>{
            const itemCard = document.querySelector(`.product-card[data-id="${id}"]`);
            if (itemCard) {
                cart[id].stock = parseInt(itemCard.dataset.stock);
            } else {
                // Safe absolute backup logic if an item goes completely out of stock or isn't rendered
                cart[id].stock = cart[id].qty;
            }
        });

        renderCart();

        // Force map text entries inside the Awaiting Payment modal wrapper
        document.getElementById('pending-order-id').textContent = window.RESUME_ORDER_ID;
        document.getElementById('pending-total').textContent = '₱' + 
        parseFloat(window.RESUME_TOTAL).toLocaleString('en-PH', { minimumFractionDigits: 2 });

        // Instantly flash open the payment confirmation modal right on page initialization
        document.getElementById('modal-pending').classList.add('open');

    }
});

// ESC closes modals
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModal('modal-checkout');
    }
});
