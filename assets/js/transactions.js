// Auto-submit form when status or date selections change
document.querySelectorAll('.filter-bar select, .filter-bar input[type="date"]').forEach(element => {
    element.addEventListener('change', () => {
        element.closest('form').submit();
    });
});

// Auto-submit form when typing in the search box (with a tiny delay so it doesn't stutter)
let searchTimeout;
document.querySelector('.filter-bar input[type="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        this.closest('form').submit();
    }, 400); // Waits 400ms after you stop typing to process the search
});

// Cancel Order
async function cancelOrder(orderId) {
    if (!confirm('Cancel this order? Stock will be restored.')) return;

    const fd = new FormData();
    fd.append('action',   'cancel_order');
    fd.append('order_id', orderId);

    try {
        const res  = await fetch('/pos_system/cashier/transactions.php', {
            method: 'POST', credentials: 'same-origin', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
            showToast('Order cancelled and stock restored.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Cancel failed.', 'danger');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'danger');
    }
}