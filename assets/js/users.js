// Toggle password visibility
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type  = input.type === 'password' ? 'text' : 'password';
}

// Generic send to same file
async function send(fd, errElId, successMsg) {
    const errEl = document.getElementById(errElId);
    errEl.style.display = 'none';
    try {
        const res  = await fetch('/pos_system/manager/users.php', {
            method: 'POST', credentials: 'same-origin', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = `/pos_system/manager/users.php?msg=${successMsg}`;
        } else {
            errEl.textContent   = data.message || 'Something went wrong.';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent   = 'Network error. Please try again.';
        errEl.style.display = 'block';
    }
}

// Add User
function saveAdd() {
    const fd = new FormData();
    fd.append('action',    'add_user');
    fd.append('username',  document.getElementById('add-username').value.trim());
    fd.append('role',      document.getElementById('add-role').value);
    fd.append('password',  document.getElementById('add-pw').value);
    fd.append('password2', document.getElementById('add-pw2').value);
    send(fd, 'err-add', 'added');
}

// Open Edit Modal
function openEdit(id, username, role) {
    document.getElementById('edit-id').value       = id;
    document.getElementById('edit-username').value = username;
    document.getElementById('edit-role').value     = role;
    document.getElementById('err-edit').style.display = 'none';
    document.getElementById('modal-edit').classList.add('open');
}

// Save Edit 
function saveEdit() {
    const fd = new FormData();
    fd.append('action',   'edit_user');
    fd.append('user_id',  document.getElementById('edit-id').value);
    fd.append('username', document.getElementById('edit-username').value.trim());
    fd.append('role',     document.getElementById('edit-role').value);
    send(fd, 'err-edit', 'updated');
}

// Open Reset Password Modal
function openResetPw(id, username) {
    document.getElementById('reset-id').value     = id;
    document.getElementById('reset-pw').value     = '';
    document.getElementById('reset-pw2').value    = '';
    document.getElementById('reset-for').textContent = `Resetting password for: ${username}`;
    document.getElementById('err-reset').style.display = 'none';
    document.getElementById('modal-reset-pw').classList.add('open');
}

// Save Reset Password 
function saveResetPw() {
    const fd = new FormData();
    fd.append('action',    'reset_password');
    fd.append('user_id',   document.getElementById('reset-id').value);
    fd.append('password',  document.getElementById('reset-pw').value);
    fd.append('password2', document.getElementById('reset-pw2').value);
    send(fd, 'err-reset', 'pw_reset');
}

//  Delete User
async function deleteUser(id, username) {
    if (!confirm(`Delete user '${username}'? This cannot be undone.`)) return;
    const fd = new FormData();
    fd.append('action',  'delete_user');
    fd.append('user_id', id);
    const res  = await fetch('/pos_system/manager/users.php', {
        method: 'POST', credentials: 'same-origin', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (data.success) {
        window.location.href = '/pos_system/manager/users.php?msg=deleted';
    } else {
        showToast(data.message || 'Delete failed.', 'danger');
    }
}