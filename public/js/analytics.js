// ======================== ANALYTICS PAGE - SCRIPT COMPLET ========================

// Chart instances
let revenueChart, trafficChart, categoryChart, engagementChart, devicesChart, retentionChart;

// Données simulées
const monthlyData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
    revenue: [32500, 34200, 37800, 41200, 43800, 45800, 47900, 49500, 51200, 52800, 54500, 56200],
    engagement: [65, 68, 72, 75, 78, 82, 85, 87, 90, 92, 94, 96]
};

const weeklyData = {
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    revenue: [8200, 9100, 10500, 9800, 11200, 13500, 12800],
    engagement: [72, 75, 78, 80, 85, 88, 86]
};

const yearlyData = {
    labels: ['2020', '2021', '2022', '2023', '2024', '2025'],
    revenue: [125000, 189000, 278000, 412000, 589000, 724000],
    engagement: [45, 52, 61, 72, 84, 91]
};

// Catégories
const categories = {
    labels: ['Électronique', 'Vêtements', 'Maison', 'Sport', 'Beauté'],
    sales: [12400, 8900, 6700, 5200, 3800],
    colors: ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444']
};

// Appareils
const devices = {
    labels: ['Mobile', 'Desktop', 'Tablet'],
    data: [55, 35, 10],
    colors: ['#3b82f6', '#10b981', '#f59e0b']
};

// Rétention
const retentionData = {
    labels: ['Mois 1', 'Mois 2', 'Mois 3', 'Mois 4', 'Mois 5', 'Mois 6'],
    data: [100, 78, 65, 58, 52, 48]
};

// Initialisation des graphiques
function initCharts() {
    // Revenue Chart (Line)
    const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
    if(revenueCtx) {
        if(revenueChart) revenueChart.destroy();
        revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Revenus (€)',
                    data: monthlyData.revenue,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Traffic Chart (Doughnut)
    const trafficCtx = document.getElementById('trafficChart')?.getContext('2d');
    if(trafficCtx) {
        if(trafficChart) trafficChart.destroy();
        trafficChart = new Chart(trafficCtx, {
            type: 'doughnut',
            data: {
                labels: ['Direct', 'Réseaux sociaux', 'Email'],
                datasets: [{
                    data: [48, 32, 20],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                cutout: '65%',
                responsive: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw}%` } } }
            }
        });
    }

    // Category Chart (Bar)
    const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
    if(categoryCtx) {
        if(categoryChart) categoryChart.destroy();
        categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categories.labels,
                datasets: [{
                    label: 'Ventes (€)',
                    data: categories.sales,
                    backgroundColor: categories.colors,
                    borderRadius: 8,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.raw.toLocaleString()} €` } } },
                scales: { y: { beginAtZero: true, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } }
            }
        });
    }

    // Engagement Chart (Area)
    const engagementCtx = document.getElementById('engagementChart')?.getContext('2d');
    if(engagementCtx) {
        if(engagementChart) engagementChart.destroy();
        engagementChart = new Chart(engagementCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: "Taux d'engagement (%)",
                    data: monthlyData.engagement,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#d97706'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.raw}%` } } },
                scales: { y: { beginAtZero: true, max: 100, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } }
            }
        });
    }

    // Devices Chart (Pie)
    const devicesCtx = document.getElementById('devicesChart')?.getContext('2d');
    if(devicesCtx) {
        if(devicesChart) devicesChart.destroy();
        devicesChart = new Chart(devicesCtx, {
            type: 'pie',
            data: {
                labels: devices.labels,
                datasets: [{
                    data: devices.data,
                    backgroundColor: devices.colors,
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw}%` } } }
            }
        });
    }

    // Retention Chart (Line)
    const retentionCtx = document.getElementById('retentionChart')?.getContext('2d');
    if(retentionCtx) {
        if(retentionChart) retentionChart.destroy();
        retentionChart = new Chart(retentionCtx, {
            type: 'line',
            data: {
                labels: retentionData.labels,
                datasets: [{
                    label: 'Taux de rétention (%)',
                    data: retentionData.data,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: '#dc2626',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.raw}%` } } },
                scales: { y: { beginAtZero: true, max: 100, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } }
            }
        });
    }
}

// Changer la période du graphique revenus
function changeRevenuePeriod(period) {
    let labels, data;
    switch(period) {
        case 'week':
            labels = weeklyData.labels;
            data = weeklyData.revenue;
            break;
        case 'year':
            labels = yearlyData.labels;
            data = yearlyData.revenue;
            break;
        default:
            labels = monthlyData.labels;
            data = monthlyData.revenue;
    }
    
    if(revenueChart) {
        revenueChart.data.labels = labels;
        revenueChart.data.datasets[0].data = data;
        revenueChart.update();
    }
}

// Écouteurs pour les boutons du graphique revenus
document.querySelectorAll('.chart-btn[data-chart="revenue"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.chart-btn[data-chart="revenue"]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        changeRevenuePeriod(btn.getAttribute('data-period'));
    });
});

// Changer la plage de dates
const dateRangeSelect = document.getElementById('dateRangeSelect');
if(dateRangeSelect) {
    dateRangeSelect.addEventListener('change', (e) => {
        const days = parseInt(e.target.value);
        // Simuler mise à jour des données
        showLoader();
        setTimeout(() => {
            updateKPIs(days);
            hideLoader();
            showToast(`Données mises à jour pour les ${days} derniers jours`, 'success');
        }, 800);
    });
}

// Mise à jour des KPIs
function updateKPIs(days) {
    const multiplier = days / 30;
    const revenue = Math.round(52480 * multiplier);
    const users = Math.round(4215 * multiplier);
    const orders = Math.round(1629 * multiplier);
    
    document.getElementById('revenueValue').textContent = `$${revenue.toLocaleString()}`;
    document.getElementById('usersValue').textContent = users.toLocaleString();
    document.getElementById('ordersValue').textContent = orders.toLocaleString();
}

// Export table
const exportTableBtn = document.getElementById('exportTableBtn');
if(exportTableBtn) {
    exportTableBtn.addEventListener('click', () => {
        const table = document.querySelector('.products-table');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('th, td');
            const rowData = Array.from(cells).map(cell => cell.textContent.trim());
            csv.push(rowData.join(','));
        });
        
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'analytics_export.csv';
        a.click();
        URL.revokeObjectURL(url);
        
        showToast('Export CSV effectué avec succès', 'success');
    });
}

// Loader et toast
function showLoader() {
    const loader = document.getElementById('loaderOverlay');
    if(loader) loader.classList.add('active');
}

function hideLoader() {
    const loader = document.getElementById('loaderOverlay');
    if(loader) loader.classList.remove('active');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Dark theme support
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    .toast-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 20px;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 9999;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    }
    .toast-notification.show { transform: translateX(0); }
    .toast-notification.success { border-left: 4px solid #10b981; }
    .toast-notification.success i { color: #10b981; }
    .toast-notification.info { border-left: 4px solid #3b82f6; }
    .toast-notification.info i { color: #3b82f6; }
`;
document.head.appendChild(toastStyle);

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    setTimeout(() => {
        initCharts();
        hideLoader();
    }, 500);
});