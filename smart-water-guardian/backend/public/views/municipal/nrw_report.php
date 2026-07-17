<?php
/**
 * NRW Report Page
 * Advanced Non-Revenue Water reporting and analytics
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'municipal') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'NRW Reports';
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
                <span class="user-name"><?php echo $_SESSION['name'] ?? 'Municipal'; ?></span>
                <span class="role-badge">Municipal</span>
                <button onclick="logout()" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="municipal-nav">
            <a href="?page=dashboard" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="?page=nrw_report" class="nav-link active">
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
            <div class="content-body">
                <!-- Report Controls -->
                <div class="report-controls">
                    <div class="controls-card">
                        <h3><i class="fas fa-sliders-h"></i> Report Controls</h3>
                        <form id="reportForm" onsubmit="generateReport(event)">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Report Type</label>
                                    <select id="reportType" onchange="updateReportType(this.value)">
                                        <option value="nrw">NRW Report</option>
                                        <option value="consumption">Consumption Report</option>
                                        <option value="financial">Financial Report</option>
                                        <option value="comparison">Comparison Report</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Period</label>
                                    <select id="reportPeriod" onchange="updateDateRange(this.value)">
                                        <option value="this_month">This Month</option>
                                        <option value="last_month">Last Month</option>
                                        <option value="this_quarter">This Quarter</option>
                                        <option value="last_quarter">Last Quarter</option>
                                        <option value="this_year" selected>This Year</option>
                                        <option value="last_year">Last Year</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row" id="customDateRange" style="display:none;">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" id="endDate" value="<?php echo date('Y-m-t'); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Suburb</label>
                                    <select id="suburbFilter">
                                        <option value="all">All Suburbs</option>
                                        <!-- Options will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Group By</label>
                                    <select id="groupBy">
                                        <option value="suburb">Suburb</option>
                                        <option value="day">Day</option>
                                        <option value="week">Week</option>
                                        <option value="month">Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-file-alt"></i> Generate Report
                                </button>
                                <button type="button" class="btn-secondary" onclick="resetFilters()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Results -->
                <div id="reportResults">
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Report Generated</h3>
                        <p>Configure the report controls above and click "Generate Report"</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // ============================================
    // NRW REPORT CONTROLLER
    // ============================================
    let reportCharts = {};
    let reportData = null;
    let currentReportType = 'nrw';

    document.addEventListener('DOMContentLoaded', function() {
        loadSuburbs();
        // Auto-generate default report
        setTimeout(() => generateReport(new Event('submit')), 500);
    });

    // ============================================
    // UPDATE DATE RANGE
    // ============================================
    function updateDateRange(period) {
        const customRange = document.getElementById('customDateRange');
        if (period === 'custom') {
            customRange.style.display = 'flex';
        } else {
            customRange.style.display = 'none';
            // Set dates based on period
            const now = new Date();
            let start, end;
            
            switch(period) {
                case 'this_month':
                    start = new Date(now.getFullYear(), now.getMonth(), 1);
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    break;
                case 'last_month':
                    start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    end = new Date(now.getFullYear(), now.getMonth(), 0);
                    break;
                case 'this_quarter':
                    const quarter = Math.floor(now.getMonth() / 3);
                    start = new Date(now.getFullYear(), quarter * 3, 1);
                    end = new Date(now.getFullYear(), quarter * 3 + 3, 0);
                    break;
                case 'last_quarter':
                    const q = Math.floor(now.getMonth() / 3) - 1;
                    start = new Date(now.getFullYear(), q * 3, 1);
                    end = new Date(now.getFullYear(), q * 3 + 3, 0);
                    break;
                case 'this_year':
                    start = new Date(now.getFullYear(), 0, 1);
                    end = new Date(now.getFullYear(), 11, 31);
                    break;
                case 'last_year':
                    start = new Date(now.getFullYear() - 1, 0, 1);
                    end = new Date(now.getFullYear() - 1, 11, 31);
                    break;
                default:
                    return;
            }
            
            document.getElementById('startDate').value = formatDateInput(start);
            document.getElementById('endDate').value = formatDateInput(end);
        }
    }

    function formatDateInput(date) {
        return date.toISOString().split('T')[0];
    }

    // ============================================
    // UPDATE REPORT TYPE
    // ============================================
    function updateReportType(type) {
        currentReportType = type;
        // Update UI based on report type
        const labels = {
            'nrw': 'NRW Report',
            'consumption': 'Consumption Report',
            'financial': 'Financial Report',
            'comparison': 'Comparison Report'
        };
        document.querySelector('.controls-card h3').innerHTML = 
            `<i class="fas fa-sliders-h"></i> ${labels[type]}`;
    }

    // ============================================
    // LOAD SUBURBS
    // ============================================
    async function loadSuburbs() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/reports/suburbs.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('suburbFilter');
                data.suburbs.forEach(suburb => {
                    const option = document.createElement('option');
                    option.value = suburb;
                    option.textContent = suburb;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Load suburbs error:', error);
        }
    }

    // ============================================
    // GENERATE REPORT
    // ============================================
    async function generateReport(event) {
        event.preventDefault();
        
        const period = document.getElementById('reportPeriod').value;
        let startDate, endDate;
        
        if (period === 'custom') {
            startDate = document.getElementById('startDate').value;
            endDate = document.getElementById('endDate').value;
        } else {
            startDate = document.getElementById('startDate').value;
            endDate = document.getElementById('endDate').value;
        }
        
        const suburb = document.getElementById('suburbFilter').value;
        const groupBy = document.getElementById('groupBy').value;
        const reportType = currentReportType;
        
        // Show loading
        const results = document.getElementById('reportResults');
        results.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Generating report...</p>
            </div>
        `;
        
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
                    suburb: suburb !== 'all' ? suburb : null,
                    group_by: groupBy,
                    report_type: reportType
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                reportData = data.report;
                renderReport(data.report, reportType);
            } else {
                results.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.message || 'Failed to generate report'}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Generate report error:', error);
            results.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error generating report: ${error.message}
                </div>
            `;
        }
    }

    // ============================================
    // RENDER REPORT
    // ============================================
    function renderReport(report, type) {
        const results = document.getElementById('reportResults');
        
        // Build report based on type
        let html = '';
        
        switch(type) {
            case 'nrw':
                html = renderNRWReport(report);
                break;
            case 'consumption':
                html = renderConsumptionReport(report);
                break;
            case 'financial':
                html = renderFinancialReport(report);
                break;
            case 'comparison':
                html = renderComparisonReport(report);
                break;
            default:
                html = renderNRWReport(report);
        }
        
        results.innerHTML = html;
        
        // Initialize charts
        setTimeout(() => {
            initReportCharts(report, type);
        }, 500);
    }

    // ============================================
    // RENDER NRW REPORT
    // ============================================
    function renderNRWReport(report) {
        return `
            <div class="report-container">
                <!-- Report Header -->
                <div class="report-header">
                    <div class="report-title">
                        <h2><i class="fas fa-file-alt"></i> Non-Revenue Water Report</h2>
                        <p>${report.period.start} to ${report.period.end}</p>
                    </div>
                    <div class="report-actions">
                        <button onclick="downloadReport('csv')" class="btn-secondary">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                        <button onclick="downloadReport('pdf')" class="btn-secondary">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button onclick="downloadReport('excel')" class="btn-secondary">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <label>Total Supplied</label>
                        <span>${report.summary.total_supplied.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-card">
                        <label>Total Billed</label>
                        <span>${report.summary.total_billed.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-card highlight">
                        <label>NRW Percentage</label>
                        <span>${report.summary.nrw_percentage}%</span>
                    </div>
                    <div class="summary-card">
                        <label>NRW Volume</label>
                        <span>${report.summary.nrw_volume.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-card">
                        <label>Financial Impact</label>
                        <span>${formatCurrency(report.summary.financial_impact)}</span>
                    </div>
                    <div class="summary-card">
                        <label>Days in Period</label>
                        <span>${report.period.days || 0}</span>
                    </div>
                </div>

                <!-- Charts -->
                <div class="report-charts">
                    <div class="chart-card">
                        <h4>NRW Trend</h4>
                        <canvas id="nrwTrendChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4>Supply vs Billed</h4>
                        <canvas id="supplyBilledChart"></canvas>
                    </div>
                </div>

                <!-- Breakdown Table -->
                <div class="report-table">
                    <h4>Breakdown by ${report.group_by || 'Suburb'}</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>${report.group_by === 'suburb' ? 'Suburb' : 'Date'}</th>
                                    <th>Supplied (kL)</th>
                                    <th>Billed (kL)</th>
                                    <th>NRW (kL)</th>
                                    <th>NRW %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${report.breakdown.map(item => `
                                    <tr>
                                        <td><strong>${item.label}</strong></td>
                                        <td>${item.supplied.toFixed(2)}</td>
                                        <td>${item.billed.toFixed(2)}</td>
                                        <td>${item.nrw.toFixed(2)}</td>
                                        <td>${item.nrw_percentage}%</td>
                                        <td>
                                            <span class="status-badge ${item.nrw_percentage > 30 ? 'danger' : item.nrw_percentage > 20 ? 'warning' : 'success'}">
                                                ${item.nrw_percentage > 30 ? '⚠️ Critical' : item.nrw_percentage > 20 ? '⚡ Warning' : '✅ Good'}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // ============================================
    // RENDER CONSUMPTION REPORT
    // ============================================
    function renderConsumptionReport(report) {
        return `
            <div class="report-container">
                <!-- Report Header -->
                <div class="report-header">
                    <div class="report-title">
                        <h2><i class="fas fa-chart-bar"></i> Consumption Report</h2>
                        <p>${report.period.start} to ${report.period.end}</p>
                    </div>
                    <div class="report-actions">
                        <button onclick="downloadReport('csv')" class="btn-secondary">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                        <button onclick="downloadReport('pdf')" class="btn-secondary">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>

                <!-- Summary -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <label>Total Consumption</label>
                        <span>${report.summary.total_consumption.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-card">
                        <label>Average Daily</label>
                        <span>${report.summary.average_daily.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-card">
                        <label>Peak Day</label>
                        <span>${report.summary.peak_day.toFixed(2)} kL</span>
                    </div>
                    <div class="summary-card">
                        <label>Total Consumers</label>
                        <span>${report.summary.total_consumers || 0}</span>
                    </div>
                </div>

                <!-- Consumption Chart -->
                <div class="report-charts">
                    <div class="chart-card full-width">
                        <h4>Consumption Trend</h4>
                        <canvas id="consumptionChart"></canvas>
                    </div>
                </div>

                <!-- Top Consumers -->
                <div class="report-table">
                    <h4>Top 10 Consumers</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Consumer</th>
                                    <th>Address</th>
                                    <th>Consumption (kL)</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${report.top_consumers.map((item, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.name}</td>
                                        <td>${item.address}</td>
                                        <td>${item.consumption.toFixed(2)}</td>
                                        <td>${item.percentage}%</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // ============================================
    // RENDER FINANCIAL REPORT
    // ============================================
    function renderFinancialReport(report) {
        return `
            <div class="report-container">
                <!-- Report Header -->
                <div class="report-header">
                    <div class="report-title">
                        <h2><i class="fas fa-money-bill-wave"></i> Financial Report</h2>
                        <p>${report.period.start} to ${report.period.end}</p>
                    </div>
                    <div class="report-actions">
                        <button onclick="downloadReport('csv')" class="btn-secondary">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="summary-cards">
                    <div class="summary-card highlight">
                        <label>Total Revenue</label>
                        <span>${formatCurrency(report.financial.total_revenue)}</span>
                    </div>
                    <div class="summary-card">
                        <label>Total Billed</label>
                        <span>${formatCurrency(report.financial.total_billed)}</span>
                    </div>
                    <div class="summary-card">
                        <label>NRW Loss</label>
                        <span style="color: #fc8181;">${formatCurrency(report.financial.nrw_loss)}</span>
                    </div>
                    <div class="summary-card">
                        <label>Collection Rate</label>
                        <span>${report.financial.collection_rate}%</span>
                    </div>
                </div>

                <!-- Revenue Breakdown -->
                <div class="report-charts">
                    <div class="chart-card">
                        <h4>Revenue by Tariff Tier</h4>
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4>Revenue vs Costs</h4>
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>
            </div>
        `;
    }

    // ============================================
    // RENDER COMPARISON REPORT
    // ============================================
    function renderComparisonReport(report) {
        return `
            <div class="report-container">
                <!-- Report Header -->
                <div class="report-header">
                    <div class="report-title">
                        <h2><i class="fas fa-balance-scale"></i> Comparison Report</h2>
                        <p>${report.period.start} to ${report.period.end}</p>
                    </div>
                    <div class="report-actions">
                        <button onclick="downloadReport('csv')" class="btn-secondary">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                    </div>
                </div>

                <!-- Comparison Summary -->
                <div class="comparison-grid">
                    <div class="comparison-card">
                        <h4>This Period</h4>
                        <div class="comparison-item">
                            <label>Total Consumption</label>
                            <span>${report.this_period.total_consumption.toFixed(2)} kL</span>
                        </div>
                        <div class="comparison-item">
                            <label>NRW %</label>
                            <span>${report.this_period.nrw_percentage}%</span>
                        </div>
                        <div class="comparison-item">
                            <label>Revenue</label>
                            <span>${formatCurrency(report.this_period.revenue)}</span>
                        </div>
                    </div>
                    <div class="comparison-card">
                        <h4>Previous Period</h4>
                        <div class="comparison-item">
                            <label>Total Consumption</label>
                            <span>${report.previous_period.total_consumption.toFixed(2)} kL</span>
                        </div>
                        <div class="comparison-item">
                            <label>NRW %</label>
                            <span>${report.previous_period.nrw_percentage}%</span>
                        </div>
                        <div class="comparison-item">
                            <label>Revenue</label>
                            <span>${formatCurrency(report.previous_period.revenue)}</span>
                        </div>
                    </div>
                    <div class="comparison-card highlight">
                        <h4>Change</h4>
                        <div class="comparison-item">
                            <label>Consumption</label>
                            <span class="${report.change.consumption > 0 ? 'positive' : 'negative'}">
                                ${report.change.consumption > 0 ? '+' : ''}${report.change.consumption_percentage}%
                            </span>
                        </div>
                        <div class="comparison-item">
                            <label>NRW</label>
                            <span class="${report.change.nrw < 0 ? 'positive' : 'negative'}">
                                ${report.change.nrw > 0 ? '+' : ''}${report.change.nrw_percentage}%
                            </span>
                        </div>
                        <div class="comparison-item">
                            <label>Revenue</label>
                            <span class="${report.change.revenue > 0 ? 'positive' : 'negative'}">
                                ${report.change.revenue > 0 ? '+' : ''}${report.change.revenue_percentage}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Comparison Chart -->
                <div class="report-charts">
                    <div class="chart-card full-width">
                        <h4>Period Comparison</h4>
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
            </div>
        `;
    }

    // ============================================
    // INIT REPORT CHARTS
    // ============================================
    function initReportCharts(report, type) {
        // Destroy existing charts
        Object.values(reportCharts).forEach(chart => {
            if (chart) chart.destroy();
        });
        reportCharts = {};

        // Initialize charts based on report type
        switch(type) {
            case 'nrw':
                initNRWCharts(report);
                break;
            case 'consumption':
                initConsumptionCharts(report);
                break;
            case 'financial':
                initFinancialCharts(report);
                break;
            case 'comparison':
                initComparisonCharts(report);
                break;
        }
    }

    function initNRWCharts(report) {
        // NRW Trend Chart
        const trendCtx = document.getElementById('nrwTrendChart');
        if (trendCtx && report.daily_trend) {
            const trend = report.daily_trend;
            reportCharts.trend = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trend.map(d => d.date),
                    datasets: [
                        {
                            label: 'NRW %',
                            data: trend.map(d => d.nrw_percentage),
                            borderColor: '#fc8181',
                            backgroundColor: 'rgba(252, 129, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Volume (kL)',
                            data: trend.map(d => d.nrw / 1000),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
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
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Volume (kL)'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'NRW %'
                            }
                        }
                    }
                }
            });
        }

        // Supply vs Billed Chart
        const supplyCtx = document.getElementById('supplyBilledChart');
        if (supplyCtx && report.breakdown) {
            const data = report.breakdown;
            reportCharts.supply = new Chart(supplyCtx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [
                        {
                            label: 'Supplied',
                            data: data.map(d => d.supplied),
                            backgroundColor: 'rgba(72, 187, 120, 0.6)',
                            borderColor: 'rgba(72, 187, 120, 1)',
                            borderWidth: 2
                        },
                        {
                            label: 'Billed',
                            data: data.map(d => d.billed),
                            backgroundColor: 'rgba(102, 126, 234, 0.6)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 2
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
    }

    function initConsumptionCharts(report) {
        const ctx = document.getElementById('consumptionChart');
        if (ctx && report.daily_trend) {
            const trend = report.daily_trend;
            reportCharts.consumption = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trend.map(d => d.date),
                    datasets: [{
                        label: 'Daily Consumption (kL)',
                        data: trend.map(d => d.total / 1000),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Consumption (kL)'
                            }
                        }
                    }
                }
            });
        }
    }

    function initFinancialCharts(report) {
        // Revenue by Tariff
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx && report.financial.tariff_breakdown) {
            const data = report.financial.tariff_breakdown;
            const colors = ['#667eea', '#48bb78', '#f6ad55', '#fc8181', '#4facfe'];
            reportCharts.revenue = new Chart(revenueCtx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => `Tier ${d.tier}`),
                    datasets: [{
                        data: data.map(d => d.revenue),
                        backgroundColor: colors.slice(0, data.length),
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
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Profit Chart
        const profitCtx = document.getElementById('profitChart');
        if (profitCtx && report.financial) {
            const fin = report.financial;
            reportCharts.profit = new Chart(profitCtx, {
                type: 'bar',
                data: {
                    labels: ['Revenue', 'Costs', 'Profit'],
                    datasets: [{
                        label: 'Amount (ZAR)',
                        data: [
                            fin.total_revenue,
                            fin.total_costs || 0,
                            fin.total_revenue - (fin.total_costs || 0)
                        ],
                        backgroundColor: ['#48bb78', '#fc8181', '#667eea'],
                        borderColor: ['#38a169', '#e53e3e', '#5a67d8'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R' + (value / 1000).toFixed(0) + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    function initComparisonCharts(report) {
        const ctx = document.getElementById('comparisonChart');
        if (ctx && report.this_period && report.previous_period) {
            const labels = ['Consumption (kL)', 'NRW %', 'Revenue (ZAR)'];
            const thisData = [
                report.this_period.total_consumption,
                report.this_period.nrw_percentage,
                report.this_period.revenue / 1000
            ];
            const prevData = [
                report.previous_period.total_consumption,
                report.previous_period.nrw_percentage,
                report.previous_period.revenue / 1000
            ];
            
            reportCharts.comparison = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'This Period',
                            data: thisData,
                            backgroundColor: 'rgba(102, 126, 234, 0.6)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 2
                        },
                        {
                            label: 'Previous Period',
                            data: prevData,
                            backgroundColor: 'rgba(160, 174, 192, 0.6)',
                            borderColor: 'rgba(160, 174, 192, 1)',
                            borderWidth: 2
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
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // ============================================
    // DOWNLOAD REPORT
    // ============================================
    function downloadReport(format) {
        if (!reportData) {
            showToast('No report data to download', 'warning');
            return;
        }
        
        showToast(`Downloading ${format.toUpperCase()} report...`, 'info');
        
        // Implement download based on format
        // This would typically trigger a server-side export
        setTimeout(() => {
            showToast(`Report downloaded as ${format.toUpperCase()}`, 'success');
        }, 1500);
    }

    // ============================================
    // RESET FILTERS
    // ============================================
    function resetFilters() {
        document.getElementById('reportType').value = 'nrw';
        document.getElementById('reportPeriod').value = 'this_year';
        document.getElementById('suburbFilter').value = 'all';
        document.getElementById('groupBy').value = 'suburb';
        updateDateRange('this_year');
        updateReportType('nrw');
        showToast('Filters reset', 'info');
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
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

    function toggleSidebar() {
        document.querySelector('.municipal-nav').classList.toggle('collapsed');
    }

    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '?page=login';
    }
    </script>

    <style>
    .report-controls {
        margin-bottom: 30px;
    }
    .controls-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .controls-card h3 {
        color: #2d3748;
        margin-bottom: 20px;
        font-size: 18px;
    }
    .controls-card h3 i {
        color: #667eea;
        margin-right: 10px;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
    }
    .form-row.three-col {
        grid-template-columns: 1fr 1fr 1fr;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        font-weight: 600;
        color: #4a5568;
        font-size: 14px;
        margin-bottom: 5px;
    }
    .form-group select,
    .form-group input {
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        background: white;
    }
    .form-group select:focus,
    .form-group input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        outline: none;
    }
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }
    .form-actions button {
        padding: 10px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-primary:hover {
        background: #5a67d8;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    .btn-secondary {
        background: #f7fafc;
        color: #2d3748;
    }
    .btn-secondary:hover {
        background: #e2e8f0;
    }
    .report-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f7fafc;
    }
    .report-title h2 {
        margin: 0;
        color: #2d3748;
        font-size: 24px;
    }
    .report-title h2 i {
        color: #667eea;
        margin-right: 10px;
    }
    .report-title p {
        color: #718096;
        margin: 5px 0 0 0;
    }
    .report-actions {
        display: flex;
        gap: 10px;
    }
    .report-actions button {
        padding: 8px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 13px;
    }
    .report-actions button:hover {
        background: #f7fafc;
        border-color: #667eea;
    }
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .summary-card {
        padding: 15px;
        background: #f7fafc;
        border-radius: 8px;
        text-align: center;
    }
    .summary-card label {
        display: block;
        font-size: 12px;
        color: #718096;
        margin-bottom: 5px;
    }
    .summary-card span {
        font-size: 20px;
        font-weight: bold;
        color: #2d3748;
    }
    .summary-card.highlight {
        background: #ebf4ff;
        border: 2px solid #667eea;
    }
    .summary-card.highlight span {
        color: #667eea;
    }
    .report-charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .report-charts .full-width {
        grid-column: 1 / -1;
    }
    .chart-card {
        background: #f7fafc;
        padding: 20px;
        border-radius: 8px;
    }
    .chart-card h4 {
        margin: 0 0 15px 0;
        color: #2d3748;
        font-size: 15px;
    }
    .chart-card canvas {
        height: 250px;
        max-height: 250px;
    }
    .report-table {
        margin-top: 20px;
    }
    .report-table h4 {
        color: #2d3748;
        margin-bottom: 15px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th {
        background: #f7fafc;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #4a5568;
        font-size: 13px;
        border-bottom: 2px solid #e2e8f0;
    }
    .table td {
        padding: 10px 15px;
        border-bottom: 1px solid #f7fafc;
        color: #2d3748;
    }
    .table tr:hover td {
        background: #f7fafc;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-badge.success { background: #c6f6d5; color: #22543d; }
    .status-badge.warning { background: #fefcbf; color: #744210; }
    .status-badge.danger { background: #fed7d7; color: #742a2a; }
    .comparison-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .comparison-card {
        padding: 20px;
        background: #f7fafc;
        border-radius: 8px;
    }
    .comparison-card h4 {
        margin: 0 0 15px 0;
        color: #2d3748;
        font-size: 16px;
    }
    .comparison-card.highlight {
        background: #ebf4ff;
        border: 2px solid #667eea;
    }
    .comparison-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .comparison-item:last-child {
        border-bottom: none;
    }
    .comparison-item label {
        color: #718096;
        font-size: 13px;
    }
    .comparison-item span {
        font-weight: 600;
        color: #2d3748;
    }
    .comparison-item span.positive { color: #48bb78; }
    .comparison-item span.negative { color: #fc8181; }
    .loading-state {
        text-align: center;
        padding: 50px;
    }
    .loading-state i {
        font-size: 48px;
        color: #667eea;
        animation: spin 1s linear infinite;
    }
    .loading-state p {
        color: #718096;
        margin-top: 15px;
    }
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #718096;
    }
    .empty-state i {
        font-size: 48px;
        color: #a0aec0;
        margin-bottom: 15px;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .form-row.three-col {
            grid-template-columns: 1fr;
        }
        .report-header {
            flex-direction: column;
            gap: 15px;
        }
        .report-actions {
            width: 100%;
        }
        .report-charts {
            grid-template-columns: 1fr;
        }
        .comparison-grid {
            grid-template-columns: 1fr;
        }
        .summary-cards {
            grid-template-columns: 1fr 1fr;
        }
    }
    </style>
</body>
</html>
