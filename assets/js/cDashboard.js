// assets/js/cDashboard.js
// Reads chart data injected by dashboard.php via window.CHART_DATA
 
(function () {
    const {
        weeklyLabels,
        weeklyData,
        hourlyLabels,
        hourlyData,
        topLabels,
        topData
    } = window.CHART_DATA;
 
    const baseScale = {
        x: {
            grid: { display: false },
            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#718096' }
        },
        y: {
            grid: { color: '#f0f0f0' },
            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#718096' }
        }
    };
 
    const pesoTick = { callback: v => '₱' + v.toLocaleString() };
 
    // Weekly line chart
    new Chart(document.getElementById('weeklyChart'), {
        type: 'line',
        data: {
            labels: weeklyLabels.length ? weeklyLabels : ['No data'],
            datasets: [{
                data: weeklyData.length ? weeklyData : [0],
                borderColor: '#1a6b3c',
                backgroundColor: 'rgba(26,107,60,0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1a6b3c',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: baseScale.x,
                y: { ...baseScale.y, ticks: { ...baseScale.y.ticks, ...pesoTick } }
            }
        }
    });
 
    // Hourly bar chart
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hourlyLabels.length ? hourlyLabels : ['No data'],
            datasets: [{
                data: hourlyData.length ? hourlyData : [0],
                backgroundColor: 'rgba(26,107,60,0.75)',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: baseScale.x,
                y: { ...baseScale.y, ticks: { ...baseScale.y.ticks, ...pesoTick } }
            }
        }
    });
 
    // Top products horizontal bar
    new Chart(document.getElementById('topChart'), {
        type: 'bar',
        data: {
            labels: topLabels.length ? topLabels : ['No data'],
            datasets: [{
                data: topData.length ? topData : [0],
                backgroundColor: [
                    'rgba(26,107,60,0.8)',
                    'rgba(245,166,35,0.8)',
                    'rgba(49,130,206,0.8)',
                    'rgba(229,62,62,0.8)',
                    'rgba(113,128,150,0.8)'
                ],
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ...baseScale.x, ticks: { ...baseScale.x.ticks, callback: v => v + ' pcs' } },
                y: baseScale.y
            }
        }
    });
})();