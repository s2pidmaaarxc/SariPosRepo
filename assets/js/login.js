//  assets/js/login.js — Login Page JavaScript
//  Sari-Sari Store POS System

// Element References
const usernameEl = document.getElementById('username');
const passwordEl = document.getElementById('password');
const btnLogin   = document.getElementById('btn-login');
const btnText    = document.getElementById('btn-text');
const btnSpinner = document.getElementById('btn-spinner');
const toastEl    = document.getElementById('toast');
const togglePw   = document.getElementById('toggle-pw');
const eyeOpen    = document.getElementById('eye-open');
const eyeClosed  = document.getElementById('eye-closed');

// Toast Helper
function showToast(msg, type = 'error') {
    toastEl.textContent = msg;
    toastEl.className   = 'toast ' + type;
}

function hideToast() {
    toastEl.className = 'toast';
    toastEl.textContent = '';
}

// Toggle Password Visibility
if (togglePw) {
    togglePw.addEventListener('click', () => {
        const isHidden = passwordEl.type === 'password';
        passwordEl.type = isHidden ? 'text' : 'password';
        eyeOpen.style.display   = isHidden ? 'none'  : 'block';
        eyeClosed.style.display = isHidden ? 'block' : 'none';
    });
}

// Clear error on input
[usernameEl, passwordEl].forEach(el => {
    if (!el) return;
    el.addEventListener('input', () => {
        el.classList.remove('error');
        hideToast();
    });
});

// Login Handler
async function doLogin() {
    const username = usernameEl.value.trim();
    const password = passwordEl.value;

    // Client-side validation
    if (!username || !password) {
        if (!username) usernameEl.classList.add('error');
        if (!password) passwordEl.classList.add('error');
        showToast('Pakifill ang username at password.', 'error');
        return;
    }

    // Prevent double-submit
    if (btnLogin.disabled) return;

    // Loading state
    btnLogin.disabled      = true;
    btnText.textContent    = 'Signing in...';
    btnSpinner.style.display = 'inline-block';

    // Build FormData
    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('username', username);
    fd.append('password', password);

    try {
        const res = await fetch('auth/login.php', {
            method:      'POST',
            credentials: 'same-origin',
            body:        fd,
            headers:     { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) {
            showToast('Server error: ' + res.status, 'error');
            return;
        }

        const text = await res.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            showToast('Invalid server response.', 'error');
            return;
        }

        if (data.success) {
            showToast('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 600);
        } else {
            showToast(data.message, 'error');

            // Reset button
            btnLogin.disabled      = false;
            btnText.textContent    = 'Sign In';
            btnSpinner.style.display = 'none';
        }

    } catch (e) {
        showToast('Network error. Please try again.', 'error');
        btnLogin.disabled      = false;
        btnText.textContent    = 'Sign In';
        btnSpinner.style.display = 'none';
    }
}

// Event Listeners
btnLogin.addEventListener('click', doLogin);

// Enter key support
document.addEventListener('keydown', e => {
    if (e.key === 'Enter') doLogin();
});
