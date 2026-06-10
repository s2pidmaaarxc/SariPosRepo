document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('dashboard-data');
    if(!container) return;

    const getData = (attr) => {
        try{
            const data = container.getAttribute(attr);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            console.error("Error parsing attribute: " + attr, e);
            return [];
        }
    };

    const sevenLabels = getData('data-seven-labels');
    const cashierDatasets = getData('data-cashier-datasets');
    const catLabels = getData('data-cat-labels');
    const catData = getData('data-cat-data');
    const weekLabels = getData('data-week-labels');
    const weekData = getData('data-week-data');
    const cashierNames = getData('data-cashier-names');
    const cashierSales = getData('data-cashier-sales');
    const topNames = getData('data-top-names');
    const topQty = getData('data-top-qty');

    // Chart layouts
    const palette = ['#1a6b3c', '#f5a623', '#3182ce', '#e53e3e', '#805ad5', '#38b2ac', '#ed8936'];
    const bX = { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#718096' } };
    const bY = { grid: { color: '#f0f0f0' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#718096' } };
    const pY = { ...bY, ticks: { ...bY.ticks, callback: v => '₱' + v.toLocaleString() } };
    const base = (extra = {}) => ({ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, ...extra });

    // 1. Weekly Sales Per Cashier
    new Chart(document.getElementById('cashierWeekChart'), {
        type: 'line',
        data: {
            labels: sevenLabels.length ? sevenLabels : ['No data'],
            datasets: cashierDatasets.length ? cashierDatasets : [{ data: [0], label: 'No Active Cashiers', borderColor: '#ccc', fill: false }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 11 }, boxWidth: 12 } } },
            scales: { x: bX, y: pY }
        }
    });

    // 2. Revenue By Category
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: catLabels.length ? catLabels : ['No Transactions'],
            datasets: [{ data: catData.length ? catData : [1], backgroundColor: catData.length ? palette : ['#e2e8f0'], borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 11 }, boxWidth: 12 } } }
        }
    });

    // 3. Store Sales 7 Days
    new Chart(document.getElementById('weeklyChart'), {
        type: 'line',
        data: {
            labels: weekLabels.length ? weekLabels : ['No data'],
            datasets: [{ data: weekData.length ? weekData : [0], borderColor: '#1a6b3c', backgroundColor: 'rgba(26,107,60,0.08)', borderWidth: 2.5, fill: true, tension: 0.4, pointRadius: 3 }]
        },
        options: { ...base(), scales: { x: bX, y: pY } }
    });

    // 4. Cashier Sales Today
    new Chart(document.getElementById('cashierTodayChart'), {
        type: 'bar',
        data: {
            labels: cashierNames.length ? cashierNames : ['No data'],
            datasets: [{ data: cashierSales.length ? cashierSales : [0], backgroundColor: palette, borderRadius: 6, borderSkipped: false }]
        },
        options: { ...base(), scales: { x: bX, y: pY } }
    });

    // 5. Top Products
    new Chart(document.getElementById('topChart'), {
        type: 'bar',
        data: {
            labels: topNames.length ? topNames : ['No data'],
            datasets: [{ data: topQty.length ? topQty : [0], backgroundColor: palette, borderRadius: 6, borderSkipped: false }]
        },
        options: { indexAxis: 'y', ...base(), scales: { x: { ...bX, ticks: { ...bX.ticks, callback: v => v + ' pcs' } }, y: bY } }
    });
});