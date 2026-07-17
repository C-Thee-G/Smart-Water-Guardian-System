<?php
/**
 * Municipal Dashboard
 * Main dashboard for municipal officials showing city-wide water statistics
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'municipal') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'Municipal Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Smart Water Guardian</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="municipal-layout">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <h1>🏙️ Smart Water Guardian</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="alert-badge-container">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="alertBadge">0</span>
                </div>
                <span class="user-name"><?php echo $_SESSION['name'] ?? 'Municipal'; ?></span>
                <span class="role-badge">Municipal</span>
                <button onclick="logout()" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="municipal-nav">
            <a href="?page=dashboard" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="?page=nrw_report" class="nav-link">
                <i class="fas fa-file-alt"></i> NRW Reports
            </a>
            <a href="?page=consumer_management" class="nav-link">
                <i class="fas fa-users"></i> Consumer Management
            </a>
            <a href="?page=tariff_management" class="nav-link">
                <i class="fas fa-coins"></i> Tariff Management
            </a>
            <a href="?page=demand_forecast" class="nav-link">
                <i class="fas fa-chart-line"></i> Demand Forecast
            </a>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-body" id="dashboardContent">
                <!-- Dashboard will be loaded by JavaScript -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading municipal dashboard...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Alert Details Modal -->
    <div class="modal" id="alertDetailsModal" style="display:none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-bell"></i> Alert Details</h3>
                <button class="modal-close" onclick="closeModal('alertDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="alertDetailsBody">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <!-- NRW Report Modal -->
    <div class="modal" id="nrwReportModal" style="display:none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> NRW Report</h3>
                <button class="modal-close" onclick="closeModal('nrwReportModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="nrwReportBody">
                <div class="loading">Generating report...</div>
            </div>
            <div class="modal-footer">
                <button onclick="downloadNRWReport()" class="btn-primary">
                    <i class="fas fa-download"></i> Download CSV
                </button>
                <button onclick="closeModal('nrwReportModal')" class="btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
    // ============================================
    // MUNICIPAL DASHBOARD
    // ============================================
    let municipalCharts = {};
    let dashboardData = null;
    let currentReportData = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadDashboard();
        
        // Auto-refresh every 60 seconds
        setInterval(loadDashboard, 60000);
        
        // Setup real-time updates
        if (typeof initRealtime === 'function') {
            initRealtime();
        }
    });

    // ============================================
    // LOAD DASHBOARD
    // ============================================
    async function loadDashboard() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/dashboard/municipal.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                dashboardData = data.data;
                renderDashboard(data.data);
                updateAlertsBadge();
            } else {
                showToast('Failed to load dashboard', 'danger');
            }
        } catch (error) {
            console.error('Load dashboard error:', error);
            showToast('Error loading dashboard', 'danger');
        }
    }

    // ============================================
    // RENDER DASHBOARD
    // ============================================
    function renderDashboard(data) {
        const container = document.getElementById('dashboardContent');
        
        // Summary Stats
        const summary = data.summary || {};
        
        let html = `
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-value">${summary.total_meters || 0}</div>
                        <div class="stat-label">Total Meters</div>
                    </div>
                    <div class="stat-sub">
                        Online: ${summary.online_meters || 0} (${summary.online_percentage || 0}%)
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <div class="stat-value">${summary.total_consumers || 0}</div>
                        <div class="stat-label">Total Consumers</div>
                    </div>
                    <div class="stat-sub">
                        Active: ${summary.total_consumers || 0}
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💧</div>
                    <div class="stat-info">
                        <div class="stat-value">${(summary.today_usage || 0).toLocaleString()}</div>
                        <div class="stat-label">Today's Usage (L)</div>
                    </div>
                    <div class="stat-sub">
                        Month: ${(summary.month_usage || 0).toLocaleString()} L
                    </div>
                </div>
                <div class="stat-card stat-nrw">
                    <div class="stat-icon">📉</div>
                    <div class="stat-info">
                        <div class="stat-value">${summary.nrw_percentage || 0}%</div>
                        <div class="stat-label">NRW Percentage</div>
                    </div>
                    <div class="stat-sub">
                        Volume: ${(summary.nrw_volume || 0).toLocaleString()} L
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-value">${formatCurrency(summary.nrw_volume * 25 / 1000 || 0)}</div>
                        <div class="stat-label">NRW Financial Impact</div>
                    </div>
                    <div class="stat-sub">
                        Avg. Tariff: R25.00/kL
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-info">
                        <div class="stat-value">${summary.total_supplied ? (summary.total_supplied / 1000).toFixed(1) : 0}</div>
                        <div class="stat-label">Total Supplied (kL)</div>
                    </div>
                    <div class="stat-sub">
                        Billed: ${summary.total_billed ? (summary.total_billed / 1000).toFixed(1) : 0} kL
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Daily Usage Trend</h3>
                        <div class="chart-controls">
                            <select id="trendPeriod" onchange="updateTrendPeriod(this.value)">
                                <option value="7">7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30" selected>30 Days</option>
                                <option value="90">90 Days</option>
                            </select>
                        </div>
                    </div>
                    <canvas id="dailyTrendChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Top NRW Areas</h3>
                    <canvas id="nrwAreasChart"></canvas>
                </div>
            </div>
            
            <!-- Hourly Usage -->
            <div class="chart-card full-width">
                <h3>Hourly Usage Pattern (Last 24 Hours)</h3>
                <canvas id="hourlyUsageChart"></canvas>
            </div>
            
            <!-- Bottom Section -->
            <div class="bottom-grid">
                <div class="recent-alerts-section">
                    <div class="section-header">
                        <h3><i class="fas fa-bell"></i> Recent Alerts</h3>
                        <a href="?page=alerts" class="view-all">View All</a>
                    </div>
                    <div id="recentAlertsList">
                        ${renderRecentAlerts(data.recent_alerts || [])}
                    </div>
                </div>
                <div class="quick-actions-section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <button onclick="showNRWReport()" class="btn-primary">
                            <i class="fas fa-file-alt"></i> Generate NRW Report
                        </button>
                        <button onclick="window.location.href='?page=consumer_management'" class="btn-secondary">
                            <i class="fas fa-users"></i> Manage Consumers
                        </button>
                        <button onclick="window.location.href='?page=tariff_management'" class="btn-secondary">
                            <i class="fas fa-coins"></i> Manage Tariffs
                        </button>
                        <button onclick="window.location.href='?page=demand_forecast'" class="btn-secondary">
                            <i class="fas fa-chart-line"></i> Demand Forecast
                        </button>
                        <button onclick="exportData()" class="btn-secondary">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Consumption by Type -->
            <div class="chart-card full-width">
                <h3>Consumption by Property Type</h3>
                <div class="consumption-grid">
                    <div class="consumption-chart">
                        <canvas id="consumptionTypeChart"></canvas>
                    </div>
                    <div class="consumption-stats" id="consumptionStats">
                        ${renderConsumptionStats(data.consumption_by_type || [])}
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Initialize charts after DOM update
        setTimeout(() => {
            initCharts(data);
        }, 500);
    }

    // ============================================
    // RENDER RECENT ALERTS
    // ============================================
    function renderRecentAlerts(alerts) {
        if (!alerts || alerts.length === 0) {
            return `
                <div class="no-alerts">
                    <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                    <p>No recent alerts</p>
                </div>
            `;
        }
        
        return alerts.slice(0, 10).map(alert => `
            <div class="alert-item alert-${alert.severity}" onclick="viewAlert('${alert.id}')">
                <div class="alert-icon">
                    <i class="fas fa-${getAlertIcon(alert.alert_type)}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-message">${alert.message}</div>
                    <div class="alert-meta">
                        <span class="alert-user">${alert.name} ${alert.surname}</span>
                        <span class="alert-time">${formatDate(alert.created_at)}</span>
                        <span class="alert-status ${alert.status}">${alert.status}</span>
                    </div>
                </div>
                <div class="alert-actions">
                    <span class="badge severity-${alert.severity}">${alert.severity}</span>
                </div>
            </div>
        `).join('');
    }

    // ============================================
    // RENDER CONSUMPTION STATS
    // ============================================
    function renderConsumptionStats(data) {
        if (!data || data.length === 0) {
            return '<p>No data available</p>';
        }
        
        const total = data.reduce((sum, item) => sum + parseFloat(item.total_usage), 0);
        
        return data.map(item => {
            const percentage = total > 0 ? ((item.total_usage / total) * 100).toFixed(1) : 0;
            return `
                <div class="consumption-stat">
                    <div class="stat-label">
                        <span class="type-dot ${item.property_type}"></span>
                        ${item.property_type}
                    </div>
                    <div class="stat-bar">
                        <div class="bar-fill" style="width: ${percentage}%; background: ${getPropertyTypeColor(item.property_type)}"></div>
                    </div>
                    <div class="stat-value">
                        ${(item.total_usage / 1000).toFixed(1)} kL (${percentage}%)
                    </div>
                </div>
            `;
        }).join('');
    }

    function getPropertyTypeColor(type) {
        const colors = {
            'residential': '#667eea',
            'commercial': '#48bb78',
            'industrial': '#f6ad55',
            'agricultural': '#4facfe'
        };
        return colors[type] || '#a0aec0';
    }

    // ============================================
    // INIT CHARTS
    // ============================================
    function initCharts(data) {
        // Destroy existing charts
        Object.values(municipalCharts).forEach(chart => {
            if (chart) chart.destroy();
        });
        municipalCharts = {};
        
        // Daily Trend Chart
        const dailyCtx = document.getElementById('dailyTrendChart');
        if (dailyCtx && data.daily_trend && data.daily_trend.length > 0) {
            const trend = data.daily_trend;
            municipalCharts.daily = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: trend.map(d => d.date),
                    datasets: [{
                        label: 'Daily Usage (L)',
                        data: trend.map(d => d.total),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Usage: ${context.parsed.y.toLocaleString()} L`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return (value / 1000).toFixed(0) + 'k';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // NRW Areas Chart
        const nrwCtx = document.getElementById('nrwAreasChart');
        if (nrwCtx && data.top_nrw_areas && data.top_nrw_areas.length > 0) {
            const areas = data.top_nrw_areas;
            municipalCharts.nrw = new Chart(nrwCtx, {
                type: 'bar',
                data: {
                    labels: areas.map(d => d.suburb),
                    datasets: [
                        {
                            label: 'Supplied (kL)',
                            data: areas.map(d => d.supplied / 1000),
                            backgroundColor: 'rgba(46, 204, 113, 0.6)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 2,
                            borderRadius: 4
                        },
                        {
                            label: 'Billed (kL)',
                            data: areas.map(d => d.billed / 1000),
                            backgroundColor: 'rgba(102, 126, 234, 0.6)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 2,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)} kL`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // Hourly Usage Chart
        const hourlyCtx = document.getElementById('hourlyUsageChart');
        if (hourlyCtx && data.hourly_usage && data.hourly_usage.length > 0) {
            const hourly = data.hourly_usage;
            municipalCharts.hourly = new Chart(hourlyCtx, {
                type: 'bar',
                data: {
                    labels: hourly.map(d => `${d.hour}:00`),
                    datasets: [{
                        label: 'Hourly Usage (L)',
                        data: hourly.map(d => d.total),
                        backgroundColor: 'rgba(118, 75, 162, 0.6)',
                        borderColor: 'rgba(118, 75, 162, 1)',
                        borderWidth: 2,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Usage: ${context.parsed.y.toLocaleString()} L`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return (value / 1000).toFixed(0) + 'k';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // Consumption by Type Chart
        const typeCtx = document.getElementById('consumptionTypeChart');
        if (typeCtx && data.consumption_by_type && data.consumption_by_type.length > 0) {
            const types = data.consumption_by_type;
            const colors = ['#667eea', '#48bb78', '#f6ad55', '#4facfe', '#fc8181'];
            
            municipalCharts.type = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: types.map(d => d.property_type),
                    datasets: [{
                        data: types.map(d => d.total_usage / 1000),
                        backgroundColor: colors.slice(0, types.length),
                        borderColor: '#ffffff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed.toFixed(1)} kL (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // ============================================
    // UPDATE TREND PERIOD
    // ============================================
    function updateTrendPeriod(days) {
        showToast(`Loading ${days} days data...`, 'info');
        // Re-fetch data with new period
        loadDashboard();
    }

    // ============================================
    // NRW REPORT
    // ============================================
    function showNRWReport() {
        const modal = document.getElementById('nrwReportModal');
        modal.style.display = 'flex';
        document.getElementById('nrwReportBody').innerHTML = `
            <div class="report-generator">
                <form id="nrwReportForm" onsubmit="generateNRWReport(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" id="reportStartDate" 
                                   value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" id="reportEndDate" 
                                   value="<?php echo date('Y-m-t'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Suburb (Optional)</label>
                        <input type="text" id="reportSuburb" placeholder="Filter by suburb">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </button>
                        <button type="button" class="btn-secondary" onclick="closeModal('nrwReportModal')">
                            Cancel
                        </button>
                    </div>
                </form>
                <div id="reportResults" style="margin-top: 20px;"></div>
            </div>
        `;
    }

    async function generateNRWReport(event) {
        event.preventDefault();
        
        const startDate = document.getElementById('reportStartDate').value;
        const endDate = document.getElementById('reportEndDate').value;
        const suburb = document.getElementById('reportSuburb').value;
        
        const resultsDiv = document.getElementById('reportResults');
        resultsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Generating report...</div>';
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/reports/generate_nrw.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate,
                    suburb: suburb || null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentReportData = data.report;
                renderNRWReportResults(data.report);
            } else {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.message || 'Failed to generate report'}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Generate report error:', error);
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error generating report
                </div>
            `;
        }
    }

    function renderNRWReportResults(report) {
        const resultsDiv = document.getElementById('reportResults');
        
        resultsDiv.innerHTML = `
            <div class="report-summary">
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Period</label>
                        <span>${report.period.start} to ${report.period.end}</span>
                    </div>
                    <div class="summary-item">
                        <label>Total Supplied</label>
                        <span>${report.summary.total_supplied.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-item">
                        <label>Total Billed</label>
                        <span>${report.summary.total_billed.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-item highlight">
                        <label>NRW Percentage</label>
                        <span>${report.summary.nrw_percentage}%</span>
                    </div>
                    <div class="summary-item">
                        <label>NRW Volume</label>
                        <span>${report.summary.nrw_volume.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-item">
                        <label>Financial Impact</label>
                        <span>${formatCurrency(report.summary.financial_impact)}</span>
                    </div>
                </div>
                
                <div class="report-breakdown">
                    <h4>Breakdown by Area</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Suburb</th>
                                    <th>Supplied (kL)</th>
                                    <th>Billed (kL)</th>
                                    <th>NRW (kL)</th>
                                    <th>NRW %</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${report.breakdown.map(item => `
                                    <tr>
                                        <td><strong>${item.suburb}</strong></td>
                                        <td>${item.supplied.toFixed(2)}</td>
                                        <td>${item.billed.toFixed(2)}</td>
                                        <td>${item.nrw.toFixed(2)}</td>
                                        <td>
                                            <span class="nrw-badge ${item.nrw_percentage > 30 ? 'high' : item.nrw_percentage > 20 ? 'medium' : 'low'}">
                                                ${item.nrw_percentage}%
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                ${report.daily_trend && report.daily_trend.length > 0 ? `
                    <div class="report-trend">
                        <h4>Daily Trend</h4>
                        <canvas id="reportTrendChart"></canvas>
                    </div>
                ` : ''}
            </div>
        `;
        
        // Initialize trend chart if data exists
        if (report.daily_trend && report.daily_trend.length > 0) {
            setTimeout(() => {
                const ctx = document.getElementById('reportTrendChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: report.daily_trend.map(d => d.supply_date),
                            datasets: [
                                {
                                    label: 'Supplied',
                                    data: report.daily_trend.map(d => d.supplied / 1000),
                                    borderColor: '#48bb78',
                                    backgroundColor: 'rgba(72, 187, 120, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: 'Billed',
                                    data: report.daily_trend.map(d => d.billed / 1000),
                                    borderColor: '#667eea',
                                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Volume (kL)'
                                    }
                                }
                            }
                        }
                    });
                }
            }, 100);
        }
    }

    function downloadNRWReport() {
        if (!currentReportData) {
            showToast('No report data to download', 'warning');
            return;
        }
        
        // Create CSV
        let csv = 'Suburb,Supplied,Billed,NRW,NRW%\n';
        currentReportData.breakdown.forEach(item => {
            csv += `${item.suburb},${item.supplied.toFixed(2)},${item.billed.toFixed(2)},${item.nrw.toFixed(2)},${item.nrw_percentage}%\n`;
        });
        
        // Add summary
        csv += '\nSummary,,,,,\n';
        csv += `Total Supplied,${currentReportData.summary.total_supplied.toFixed(2)},,,,\n`;
        csv += `Total Billed,${currentReportData.summary.total_billed.toFixed(2)},,,,\n`;
        csv += `NRW Volume,${currentReportData.summary.nrw_volume.toFixed(2)},,,,\n`;
        csv += `NRW Percentage,${currentReportData.summary.nrw_percentage}%,,,,\n`;
        csv += `Financial Impact,${formatCurrency(currentReportData.summary.financial_impact)},,,,\n`;
        csv += `Period,${currentReportData.period.start} to ${currentReportData.period.end},,,,\n`;
        
        // Download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `NRW_Report_${currentReportData.period.start}_to_${currentReportData.period.end}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Report downloaded successfully!', 'success');
    }

    // ============================================
    // VIEW ALERT
    // ============================================
    async function viewAlert(alertId) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/alerts/get_alert.php?id=${alertId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const alert = data.alert;
                document.getElementById('alertDetailsBody').innerHTML = `
                    <div class="alert-details">
                        <div class="detail-row">
                            <label>Type</label>
                            <span class="alert-type">${alert.alert_type}</span>
                        </div>
                        <div class="detail-row">
                            <label>Severity</label>
                            <span class="badge severity-${alert.severity}">${alert.severity}</span>
                        </div>
                        <div class="detail-row">
                            <label>Status</label>
                            <span class="badge ${alert.status}">${alert.status}</span>
                        </div>
                        <div class="detail-row">
                            <label>Message</label>
                            <p>${alert.message}</p>
                        </div>
                        <div class="detail-row">
                            <label>User</label>
                            <span>${alert.name} ${alert.surname}</span>
                        </div>
                        <div class="detail-row">
                            <label>Created</label>
                            <span>${formatDate(alert.created_at)}</span>
                        </div>
                        ${alert.resolved_at ? `
                            <div class="detail-row">
                                <label>Resolved</label>
                                <span>${formatDate(alert.resolved_at)}</span>
                            </div>
                        ` : ''}
                    </div>
                    ${alert.status === 'active' ? `
                        <div class="alert-actions">
                            <button onclick="resolveAlert('${alert.id}')" class="btn-primary">
                                <i class="fas fa-check"></i> Resolve Alert
                            </button>
                        </div>
                    ` : ''}
                `;
                showModal('alertDetailsModal');
            }
        } catch (error) {
            console.error('View alert error:', error);
            showToast('Error loading alert details', 'danger');
        }
    }

    async function resolveAlert(alertId) {
        if (!confirm('Are you sure you want to resolve this alert?')) return;
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/alerts/resolve.php', {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ alert_id: alertId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Alert resolved successfully!', 'success');
                closeModal('alertDetailsModal');
                loadDashboard();
            } else {
                showToast(data.message || 'Failed to resolve alert', 'danger');
            }
        } catch (error) {
            console.error('Resolve alert error:', error);
            showToast('Error resolving alert', 'danger');
        }
    }

    // ============================================
    // EXPORT DATA
    // ============================================
    function exportData() {
        showToast('Preparing data export...', 'info');
        // Implement data export
        setTimeout(() => {
            showToast('Data export feature coming soon', 'info');
        }, 2000);
    }

    // ============================================
    // ALERT BADGE
    // ============================================
    function updateAlertsBadge() {
        if (dashboardData && dashboardData.recent_alerts) {
            const activeAlerts = dashboardData.recent_alerts.filter(a => a.status === 'active').length;
            const badge = document.getElementById('alertBadge');
            if (badge) {
                badge.textContent = activeAlerts > 99 ? '99+' : activeAlerts;
                badge.style.display = activeAlerts > 0 ? 'inline-flex' : 'none';
            }
        }
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function getAlertIcon(type) {
        const icons = {
            'leak': 'faucet',
            'critical_leak': 'exclamation-triangle',
            'threshold': 'chart-line',
            'battery_low': 'battery-quarter',
            'offline': 'wifi-slash',
            'high_usage': 'fire'
        };
        return icons[type] || 'bell';
    }

    function formatDate(date) {
        if (!date) return 'Never';
        return new Date(date).toLocaleString('en-ZA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-ZA', {
            style: 'currency',
            currency: 'ZAR'
        }).format(amount);
    }

    function showToast(message, type) {
        if (typeof showToast === 'function') {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }

    function showModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function toggleSidebar() {
        document.querySelector('.municipal-nav').classList.toggle('collapsed');
    }

    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '?page=login';
    }

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
    </script>

    <style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-card .stat-icon {
        font-size: 32px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f7fafc;
        border-radius: 10px;
    }
    .stat-card .stat-info {
        flex: 1;
    }
    .stat-card .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #2d3748;
    }
    .stat-card .stat-label {
        font-size: 14px;
        color: #718096;
    }
    .stat-card .stat-sub {
        font-size: 12px;
        color: #a0aec0;
        margin-top: 3px;
    }
    .stat-card.stat-nrw .stat-value {
        color: #fc8181;
    }
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .chart-card.full-width {
        grid-column: 1 / -1;
        margin-bottom: 30px;
    }
    .chart-card h3 {
        color: #2d3748;
        margin-bottom: 20px;
        font-size: 16px;
    }
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .chart-controls select {
        padding: 5px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        background: white;
    }
    .bottom-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .recent-alerts-section,
    .quick-actions-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .section-header h3 {
        margin: 0;
        font-size: 16px;
        color: #2d3748;
    }
    .view-all {
        font-size: 13px;
        color: #667eea;
        text-decoration: none;
    }
    .view-all:hover {
        text-decoration: underline;
    }
    .alert-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: background 0.3s;
        border-left: 4px solid transparent;
    }
    .alert-item:hover {
        background: #f7fafc;
    }
    .alert-item.alert-critical { border-left-color: #fc8181; }
    .alert-item.alert-high { border-left-color: #f6ad55; }
    .alert-item.alert-warning { border-left-color: #fefcbf; }
    .alert-item .alert-icon {
        font-size: 20px;
        color: #667eea;
    }
    .alert-item .alert-content {
        flex: 1;
    }
    .alert-item .alert-message {
        font-weight: 500;
        color: #2d3748;
    }
    .alert-item .alert-meta {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #718096;
        margin-top: 3px;
    }
    .alert-item .alert-status {
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
    }
    .alert-item .alert-status.active { background: #fefcbf; color: #744210; }
    .alert-item .alert-status.acknowledged { background: #bee3f8; color: #2b6cb0; }
    .alert-item .alert-status.resolved { background: #c6f6d5; color: #22543d; }
    .badge.severity-critical { background: #fc8181; color: white; }
    .badge.severity-high { background: #f6ad55; color: white; }
    .badge.severity-warning { background: #fefcbf; color: #744210; }
    .badge.severity-info { background: #bee3f8; color: #2b6cb0; }
    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .quick-actions button {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .quick-actions button:hover {
        transform: translateX(5px);
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-primary:hover { background: #5a67d8; }
    .btn-secondary {
        background: #f7fafc;
        color: #2d3748;
    }
    .btn-secondary:hover { background: #e2e8f0; }
    .consumption-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    .consumption-stats {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .consumption-stat {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .consumption-stat .stat-label {
        min-width: 120px;
        font-weight: 500;
        color: #2d3748;
    }
    .consumption-stat .stat-bar {
        flex: 1;
        height: 8px;
        background: #f7fafc;
        border-radius: 4px;
        overflow: hidden;
    }
    .consumption-stat .stat-bar .bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s;
    }
    .consumption-stat .stat-value {
        min-width: 80px;
        text-align: right;
        font-weight: 500;
        color: #2d3748;
    }
    .type-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 8px;
    }
    .type-dot.residential { background: #667eea; }
    .type-dot.commercial { background: #48bb78; }
    .type-dot.industrial { background: #f6ad55; }
    .type-dot.agricultural { background: #4facfe; }
    .nrw-badge {
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
    }
    .nrw-badge.high { background: #fc8181; color: white; }
    .nrw-badge.medium { background: #f6ad55; color: white; }
    .nrw-badge.low { background: #48bb78; color: white; }
    .report-summary {
        margin-top: 20px;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .summary-item {
        padding: 15px;
        background: #f7fafc;
        border-radius: 8px;
        text-align: center;
    }
    .summary-item label {
        display: block;
        font-size: 12px;
        color: #718096;
        margin-bottom: 5px;
    }
    .summary-item span {
        font-size: 18px;
        font-weight: bold;
        color: #2d3748;
    }
    .summary-item.highlight {
        background: #ebf4ff;
        border: 2px solid #667eea;
    }
    .summary-item.highlight span {
        color: #667eea;
    }
    .report-breakdown {
        margin-top: 20px;
    }
    .report-breakdown h4 {
        color: #2d3748;
        margin-bottom: 15px;
    }
    .report-trend {
        margin-top: 30px;
    }
    .report-trend h4 {
        color: #2d3748;
        margin-bottom: 15px;
    }
    #reportTrendChart {
        height: 300px;
    }
    .alert-details .detail-row {
        padding: 10px 0;
        border-bottom: 1px solid #f7fafc;
    }
    .alert-details .detail-row label {
        font-weight: 600;
        color: #4a5568;
        display: block;
        margin-bottom: 3px;
        font-size: 13px;
    }
    .alert-details .detail-row p {
        margin: 0;
        color: #2d3748;
    }
    .loading-spinner {
        text-align: center;
        padding: 50px;
    }
    .loading-spinner i {
        font-size: 48px;
        color: #667eea;
    }
    .loading-spinner p {
        color: #718096;
        margin-top: 10px;
    }
    .empty-state {
        text-align: center;
        padding: 50px;
    }
    .empty-state i {
        font-size: 48px;
        color: #a0aec0;
        margin-bottom: 15px;
    }
    .no-alerts {
        text-align: center;
        padding: 20px;
        color: #718096;
    }
    .no-alerts i {
        font-size: 32px;
        margin-bottom: 10px;
    }
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        .charts-grid {
            grid-template-columns: 1fr;
        }
        .bottom-grid {
            grid-template-columns: 1fr;
        }
        .consumption-grid {
            grid-template-columns: 1fr;
        }
        .summary-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    </style>
</body>
</html>
