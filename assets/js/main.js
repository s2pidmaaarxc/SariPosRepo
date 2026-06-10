//  assets/js/main.js — Global JavaScript (except: login)

document.addEventListener('DOMContentLoaded', () => {

    // Live Clock sa Topbar
    const clockEl = document.getElementById('topbar-clock');
    if (clockEl) {
        function updateClock() {
            const now = new Date();
            clockEl.textContent = now.toLocaleString('en-PH', {
                month:  'short',
                day:    'numeric',
                year:   'numeric',
                hour:   'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // Sidebar Active Link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('active');
        }
    });

    // Modal Handling
    // Open modal
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-modal-open');
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('open');
        });
    });

    // Close modal — X button
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-modal-close');
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('open');
        });
    });

    // Close modal — click outside
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // Close modal — ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => {
                m.classList.remove('open');
            });
        }
    });

    // Auto-dismiss Alerts
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        const delay = parseInt(alert.getAttribute('data-auto-dismiss')) || 3000;
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.4s ease';
            setTimeout(() => alert.remove(), 400);
        }, delay);
    });

    // Confirm Delete 
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.getAttribute('data-confirm') || 'Sigurado ka ba?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // Sidebar mobile toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

});

//  Utility Functions — available globally

// Format peso
function formatPeso(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format date
function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year:  'numeric',
        month: 'short',
        day:   'numeric'
    });
}

// Show toast notification
function showToast(message, type = 'success') {
    // Remove existing toasts
    document.querySelectorAll('.toast').forEach(t => t.remove());

    const colors = {
        success: { bg: '#f0fff4', border: '#9ae6b4', text: '#276749' },
        danger:  { bg: '#fff5f5', border: '#feb2b2', text: '#9b2c2c' },
        warning: { bg: '#fffff0', border: '#fbd38d', text: '#744210' },
        info:    { bg: '#ebf8ff', border: '#90cdf4', text: '#2c5282' }
    };

    const c = colors[type] || colors.success;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: ${c.bg};
        border: 1px solid ${c.border};
        color: ${c.text};
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        z-index: 9999;
        animation: slideInToast 0.3s ease;
        max-width: 320px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    `;

    toast.textContent = message;
    document.body.appendChild(toast);

    // Auto remove
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add toast animation to head once
if (!document.getElementById('toast-style')) {
    const style = document.createElement('style');
    style.id = 'toast-style';
    style.textContent = `
        @keyframes slideInToast {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
}
