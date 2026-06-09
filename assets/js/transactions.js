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