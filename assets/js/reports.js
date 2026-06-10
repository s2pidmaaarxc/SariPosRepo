//  Reports JavaScript

const palette = ['#1a6b3c','#f5a623','#3182ce','#e53e3e','#805ad5','#38b2ac','#ed8936','#667eea'];
const bX = { grid:{display:false}, ticks:{font:{family:'Plus Jakarta Sans',size:11},color:'#718096'} };
const bY = { grid:{color:'#f0f0f0'}, ticks:{font:{family:'Plus Jakarta Sans',size:11},color:'#718096'} };
const pesoY = { ...bY, ticks:{...bY.ticks, callback: v=>'₱'+Number(v).toLocaleString()} };

let charts = {};

function destroyChart(id) {
    if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

// Date Range Helpers
function setRange(range) {
    const now = new Date();
    
    // Safely pull local YYYY-MM-DD format strings
    const offset = now.getTimezoneOffset() * 60000;
    const localISOTime = (new Date(now - offset)).toISOString().slice(0,10);
    
    let from, to = localISOTime;

    if (range === 'today') {
        from = to;
    } else if (range === 'week') {
        const d = new Date(now);
        d.setDate(d.getDate() - d.getDay());
        const dOffset = d.getTimezoneOffset() * 60000;
        from = (new Date(d - dOffset)).toISOString().slice(0,10);
    } else if (range === 'month') {
        from = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
    } else if (range === 'year') {
        from = now.getFullYear() + '-01-01';
    }

    document.getElementById('date-from').value = from;
    document.getElementById('date-to').value   = to;
    
    // Safety check for UI trigger event variations
    if (window.event && window.event.target) {
        document.querySelectorAll('.quick-btn').forEach(b => b.classList.remove('active'));
        window.event.target.classList.add('active');
    }
    
    loadAll();
}

// Tab Switcher
function switchPane(paneId) {
    document.querySelectorAll('.report-tab').forEach(t =>
        t.classList.toggle('active', t.dataset.pane === paneId));
    document.querySelectorAll('.report-pane').forEach(p =>
        p.classList.toggle('active', p.id === paneId));
}

// Loading helpers
function showLoad(...ids) { ids.forEach(id => { const el=document.getElementById(id); if(el) el.classList.add('show'); }); }
function hideLoad(...ids) { ids.forEach(id => { const el=document.getElementById(id); if(el) el.classList.remove('show'); }); }

// AJAX fetch helper
async function fetchReport(action, extra = {}) {
    const fd = new FormData();
    fd.append('action',    action);
    fd.append('date_from', document.getElementById('date-from').value);
    fd.append('date_to',   document.getElementById('date-to').value);
    Object.entries(extra).forEach(([k,v]) => fd.append(k, v));

    const res  = await fetch('/pos_system/manager/reports.php', {
        method: 'POST', credentials: 'same-origin', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return res.json();
}

// Load All Reports
async function loadAll() {
    updateStatCards();
    loadSales();
    loadBestSellers();
    loadCashierPerformance();
    loadCategoryRevenue();
}

// Update Summary Stat Cards
async function updateStatCards() {
    const data = await fetchReport('get_sales');
    if (!data.success) return;

    const fmt = n => '₱' + parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2});
    document.getElementById('stat-revenue').textContent   = fmt(data.total_revenue);
    document.getElementById('stat-orders').textContent    = data.total_orders;
    document.getElementById('stat-completed').textContent = data.total_completed + ' completed';
    document.getElementById('stat-cancelled').textContent = data.total_cancelled;
    document.getElementById('stat-avg').textContent       = data.total_orders > 0
        ? fmt(data.total_revenue / data.total_completed)
        : '₱0.00';
}

// Pane 1: Daily Sales
async function loadSales() {
    showLoad('load-sales','load-sales-table');
    const data = await fetchReport('get_sales');
    hideLoad('load-sales','load-sales-table');
    if (!data.success) return;

    const rows = data.rows;

    // Chart
    destroyChart('sales');
    charts['sales'] = new Chart(document.getElementById('chart-sales'), {
        type: 'line',
        data: {
            labels: rows.map(r => r.sale_date),
            datasets: [{
                label: 'Revenue',
                data: rows.map(r => parseFloat(r.revenue)),
                borderColor: '#1a6b3c', backgroundColor: 'rgba(26,107,60,0.08)',
                borderWidth: 2.5, fill: true, tension: 0.4, pointRadius: 4
            }]
        },
        options: { responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:false}}, scales:{x:bX, y:pesoY} }
    });

    // Table
    document.getElementById('sales-count').textContent = rows.length + ' days';
    const fmt = n => '₱' + parseFloat(n).toLocaleString('en-PH',{minimumFractionDigits:2});
    document.getElementById('tbody-sales').innerHTML = rows.length === 0
        ? '<tr><td colspan="5" class="text-center text-muted" style="padding:24px">No data for selected period.</td></tr>'
        : rows.map(r => `
            <tr>
                <td class="font-bold">${r.sale_date}</td>
                <td class="text-right text-mono">${r.total_orders}</td>
                <td class="text-right"><span class="badge badge-success">${r.completed}</span></td>
                <td class="text-right"><span class="badge badge-danger">${r.cancelled}</span></td>
                <td class="text-right font-bold text-primary">${fmt(r.revenue)}</td>
            </tr>`).join('');
}

//  Pane 2: Best Sellers
async function loadBestSellers() {
    showLoad('load-bs-chart','load-bs-rev','load-bs-table');
    const data = await fetchReport('get_best_sellers', { limit: 10 });
    hideLoad('load-bs-chart','load-bs-rev','load-bs-table');
    if (!data.success) return;

    const rows   = data.rows;
    const top5   = rows.slice(0,5);
    const fmt    = n => '₱' + parseFloat(n).toLocaleString('en-PH',{minimumFractionDigits:2});

    // Chart — Qty
    destroyChart('bs-qty');
    charts['bs-qty'] = new Chart(document.getElementById('chart-bs-qty'), {
        type: 'bar',
        data: { labels: top5.map(r=>r.name),
            datasets:[{ data:top5.map(r=>parseInt(r.total_qty)),
                backgroundColor:palette, borderRadius:6, borderSkipped:false }] },
        options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:false}},
            scales:{x:{...bX, ticks:{...bX.ticks, callback:v=>v+' pcs'}}, y:bY} }
    });

    // Chart — Revenue
    destroyChart('bs-rev');
    charts['bs-rev'] = new Chart(document.getElementById('chart-bs-rev'), {
        type: 'bar',
        data: { labels: top5.map(r=>r.name),
            datasets:[{ data:top5.map(r=>parseFloat(r.total_revenue)),
                backgroundColor:palette, borderRadius:6, borderSkipped:false }] },
        options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:false}}, scales:{x:{...bX, ticks:{...bX.ticks, callback:v=>'₱'+v.toLocaleString()}}, y:bY} }
    });

    // Table
    const rankBadge = i => i < 3
        ? `<span class="badge rank-${i+1}">${['🥇','🥈','🥉'][i]}</span>`
        : `<span class="text-muted">#${i+1}</span>`;

    document.getElementById('tbody-bs').innerHTML = rows.length === 0
        ? '<tr><td colspan="6" class="text-center text-muted" style="padding:24px">No sales data for selected period.</td></tr>'
        : rows.map((r,i) => `
            <tr>
                <td>${rankBadge(i)}</td>
                <td class="font-bold">${r.name}</td>
                <td><span class="badge badge-info">${r.category_name}</span></td>
                <td class="text-right text-mono">${parseInt(r.total_qty)} pcs</td>
                <td class="text-right font-bold text-primary">${fmt(r.total_revenue)}</td>
                <td class="text-right text-mono">${fmt(r.avg_price)}</td>
            </tr>`).join('');
}

// Pane 3: Cashier Performance
async function loadCashierPerformance() {
    showLoad('load-cash-chart','load-cash-orders','load-cash-table');
    const data = await fetchReport('get_cashier_performance');
    hideLoad('load-cash-chart','load-cash-orders','load-cash-table');
    if (!data.success) return;

    const rows = data.rows;
    const fmt  = n => '₱' + parseFloat(n).toLocaleString('en-PH',{minimumFractionDigits:2});

    // Chart — Revenue
    destroyChart('cash-rev');
    charts['cash-rev'] = new Chart(document.getElementById('chart-cash-rev'), {
        type: 'bar',
        data: { labels: rows.map(r=>r.username),
            datasets:[{ data:rows.map(r=>parseFloat(r.revenue)),
                backgroundColor:palette, borderRadius:6, borderSkipped:false }] },
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:false}}, scales:{x:bX, y:pesoY} }
    });

    // Chart — Orders
    destroyChart('cash-orders');
    charts['cash-orders'] = new Chart(document.getElementById('chart-cash-orders'), {
        type: 'doughnut',
        data: { labels: rows.map(r=>r.username),
            datasets:[{ data:rows.map(r=>parseInt(r.completed)),
                backgroundColor:palette, borderWidth:2, borderColor:'#fff', hoverOffset:6 }] },
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:true, position:'bottom',
                labels:{font:{family:'Plus Jakarta Sans',size:11},boxWidth:12}}} }
    });

    // Table
    document.getElementById('tbody-cashiers').innerHTML = rows.length === 0
        ? '<tr><td colspan="6" class="text-center text-muted" style="padding:24px">No transactions for selected period.</td></tr>'
        : rows.map(r => `
            <tr>
                <td class="font-bold">${r.username}</td>
                <td class="text-right text-mono">${r.total_orders}</td>
                <td class="text-right"><span class="badge badge-success">${r.completed}</span></td>
                <td class="text-right"><span class="badge badge-danger">${r.cancelled}</span></td>
                <td class="text-right font-bold text-primary">${fmt(r.revenue)}</td>
                <td class="text-right text-mono">${fmt(r.avg_order)}</td>
            </tr>`).join('');
}

//  Pane 4: Category Revenue
async function loadCategoryRevenue() {
    showLoad('load-cat-doughnut','load-cat-bar','load-cat-table');
    const data = await fetchReport('get_category_revenue');
    hideLoad('load-cat-doughnut','load-cat-bar','load-cat-table');
    if (!data.success) return;

    const rows      = data.rows;
    const totalRev  = rows.reduce((s,r) => s + parseFloat(r.total_revenue), 0);
    const fmt       = n => '₱' + parseFloat(n).toLocaleString('en-PH',{minimumFractionDigits:2});

    // Doughnut
    destroyChart('cat-doughnut');
    charts['cat-doughnut'] = new Chart(document.getElementById('chart-cat-doughnut'), {
        type: 'doughnut',
        data: { labels: rows.map(r=>r.category_name),
            datasets:[{ data:rows.map(r=>parseFloat(r.total_revenue)),
                backgroundColor:palette, borderWidth:2, borderColor:'#fff', hoverOffset:6 }] },
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:true, position:'bottom',
                labels:{font:{family:'Plus Jakarta Sans',size:11},boxWidth:12}}} }
    });

    // Bar — Qty
    destroyChart('cat-qty');
    charts['cat-qty'] = new Chart(document.getElementById('chart-cat-qty'), {
        type: 'bar',
        data: { labels: rows.map(r=>r.category_name),
            datasets:[{ data:rows.map(r=>parseInt(r.total_qty)),
                backgroundColor:palette, borderRadius:6, borderSkipped:false }] },
        options:{ responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:false}},
            scales:{x:bX, y:{...bY, ticks:{...bY.ticks, callback:v=>v+' pcs'}}} }
    });

    // Table
    document.getElementById('tbody-category').innerHTML = rows.length === 0
        ? '<tr><td colspan="4" class="text-center text-muted" style="padding:24px">No sales data for selected period.</td></tr>'
        : rows.map(r => {
            const share = totalRev > 0 ? ((parseFloat(r.total_revenue)/totalRev)*100).toFixed(1) : '0.0';
            return `
            <tr>
                <td class="font-bold">${r.category_name}</td>
                <td class="text-right text-mono">${parseInt(r.total_qty)} pcs</td>
                <td class="text-right font-bold text-primary">${fmt(r.total_revenue)}</td>
                <td class="text-right">
                    <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end">
                        <div style="width:60px;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                            <div style="width:${share}%;height:100%;background:var(--primary);border-radius:3px"></div>
                        </div>
                        <span class="text-mono">${share}%</span>
                    </div>
                </td>
            </tr>`;
        }).join('');
}

// Auto-load on page start
window.addEventListener('DOMContentLoaded', () => {
    loadAll();
});