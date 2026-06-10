// Tab Switcher
function switchTab(tabId) {
    document.querySelectorAll('.tab-switch-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tab === tabId);
    });
    document.querySelectorAll('.tab-pane').forEach(p => {
        p.classList.toggle('active', p.id === tabId);
    });
}

// Generic AJAX send (POST to same file — products.php)
async function send(fd, errElId) {
    const errEl = document.getElementById(errElId);
    errEl.style.display = 'none';
    try {
        const res  = await fetch('/pos_system/manager/products.php', {
            method: 'POST', credentials: 'same-origin', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/pos_system/manager/products.php?msg=' + (fd.get('action') === 'add_product' ? 'added'
                : fd.get('action') === 'edit_product' ? 'updated'
                : fd.get('action') === 'delete_product' ? 'deleted'
                : 'cat_added');
        } else {
            errEl.textContent   = data.message || 'Something went wrong.';
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent   = 'Network error. Please try again.';
        errEl.style.display = 'block';
    }
}

// Add 
function saveAdd() {
    const isProduct = document.getElementById('tab-product').classList.contains('active');
    const fd = new FormData();

    if (isProduct) {
        fd.append('action',      'add_product');
        fd.append('name',        document.getElementById('add-name').value.trim());
        fd.append('category_id', document.getElementById('add-cat').value);
        fd.append('price',       document.getElementById('add-price').value);
        fd.append('stock',       document.getElementById('add-stock').value);
        send(fd, 'err-add-product');
    } else {
        fd.append('action', 'add_category');
        fd.append('name',   document.getElementById('add-cat-name').value.trim());
        send(fd, 'err-add-cat');
    }
}

//  Open Edit Modal
function openEdit(id, name, catId, price, stock) {
    document.getElementById('edit-id').value    = id;
    document.getElementById('edit-name').value  = name;
    document.getElementById('edit-cat').value   = catId;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-stock').value = stock;
    document.getElementById('err-edit').style.display = 'none';
    document.getElementById('modal-edit').classList.add('open');
}

// Save Edit 
function saveEdit() {
    const fd = new FormData();
    fd.append('action',      'edit_product');
    fd.append('product_id',  document.getElementById('edit-id').value);
    fd.append('name',        document.getElementById('edit-name').value.trim());
    fd.append('category_id', document.getElementById('edit-cat').value);
    fd.append('price',       document.getElementById('edit-price').value);
    fd.append('stock',       document.getElementById('edit-stock').value);
    send(fd, 'err-edit');
}

//  Delete 
async function deleteProduct(id, name) {
    if (!confirm(`Delete '${name}'? This cannot be undone.`)) return;
    const fd = new FormData();
    fd.append('action',     'delete_product');
    fd.append('product_id', id);
    send(fd, 'err-edit'); // reuse errEl — invisible since modal is closed
}