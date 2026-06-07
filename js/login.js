// Toggle password visibility
    document.getElementById('togglePass').addEventListener('click', function () {
        const pwd = document.getElementById('password');
        pwd.type = pwd.type === 'password' ? 'text' : 'password';
    
        this.textContent = pwd.type === 'password' ? '👁️' : '🙈'; 
    });

    // Disable submit button on submit to prevent double-click
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.textContent = 'Signing in…';
    });