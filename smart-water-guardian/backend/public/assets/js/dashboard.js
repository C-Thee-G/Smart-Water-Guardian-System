/**
 * Smart Water Guardian - Dashboard Controller
 * Handles dashboard data loading, rendering, and interactions
 */

// ============================================
// CONSUMER DASHBOARD
// ============================================
let consumerCharts = {};

async function loadConsumerData() {
    showLoading(true);
    
    try {
        const data = await API.get('/dashboard/consumer.php');
        
        if (data.success) {
            renderConsumerDashboard(data.data);
            AppState.properties = data.data.properties;
        } else {
            showToast('Failed to load dashboard data', 'danger');
        }
    } catch (error) {
        console.error('Consumer dashboard error:', error);
        showToast('Error loading dashboard', 'danger');
    } finally {
        showLoading(false);
    }
}

function renderConsumerDashboard(data) {
    const container = document.getElementById('dashboardContent');
    if (!container) return;
    
    const properties = data.properties;
    if (!properties || properties.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-tachometer-alt"></i>
                <h3>No Properties Found</h3>
                <p>You don't have any properties registered yet.</p>
                <button onclick="addProperty()" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Property
                </button>
            </div>
        `;
        return;
    }
    
    // Render property cards
    let html = `
        <div class="consumer-dashboard-grid">
            ${properties.map(property => renderPropertyCard(property)).join('')}
        </div>
        <div class="dashboard-charts">
            <div class="chart-container">
                <h3>Daily Usage Trend</h3>
                <canvas id="dailyUsageChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Monthly Comparison</h3>
                <canvas id="monthlyUsageChart"></canvas>
            </div>
        </div>
        <div class="dashboard-alerts">
            <h3>Recent Alerts</h3>
            <div id="alertsList"></div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Initialize charts
    setTimeout(() => {
        if (properties[0]) {
            initConsumerCharts(properties[0]);
        }
        renderAlerts(properties);
    }, 100);
}

function renderPropertyCard(property) {
    const usage = property.current_usage || {};
    const meter = property.meter || {};
    const alerts = property.alerts || [];
    const hasActiveAlerts = alerts.some(a => a.status === 'active');
    
    return `
        <div class="property-card ${hasActiveAlerts ? 'has-alert' : ''}">
            <div class="property-header">
                <h4>${property.address}</h4>
                <span class="property-type ${property.property_type}">
                    ${property.property_type}
                </span>
            </div>
            <div class="meter-status">
                <span class="status-indicator ${meter.status || 'offline'}">
                    ${meter.status || 'No Meter'}
                </span>
                ${meter.battery ? `
                    <span class="battery-level">
                        <i class="fas fa-battery-${getBatteryIcon(meter.battery)}"></i>
                        ${meter.battery}%
                    </span>
                ` : ''}
            </div>
            <div class="usage-stats">
                <div class="stat-item">
                    <label>Flow Rate</label>
                    <span class="value">${usage.flow_rate || 0} <small>L/min</small></span>
                </div>
                <div class="stat-item">
                    <label>Today</label>
                    <span class="value">${(usage.today_total || 0).toFixed(1)} <small>L</small></span>
                </div>
                <div class="stat-item">
                    <label>This Week</label>
                    <span class="value">${(usage.week_total || 0).toFixed(1)} <small>L</small></span>
                </div>
                <div class="stat-item">
                    <label>This Month</label>
                    <span class="value">${(usage.month_total || 0).toFixed(1)} <small>L</small></span>
                </div>
            </div>
            ${alerts.length > 0 ? `
                <div class="property-alerts">
                    <span class="alert-badge">${alerts.length} alerts</span>
                </div>
            ` : ''}
            <div class="property-actions">
                <button onclick="viewProperty('${property.id}')" class="btn-sm btn-primary">
                    <i class="fas fa-chart-line"></i> View Details
                </button>
                <button onclick="configureAlerts('${property.id}')" class="btn-sm btn-secondary">
                    <i class="fas fa-bell"></i> Alerts
                </button>
            </div>
        </div>
    `;
}

function getBatteryIcon(level) {
    if (level > 75) return 'full';
    if (level > 50) return 'three-quarters';
    if (level > 25) return 'half';
    if (level > 10) return 'quarter';
    return 'empty';
}

// ============================================
// CONSUMER CHARTS
// ============================================
function initConsumerCharts(property) {
    const history = property.history || {};
    
    // Daily Usage Chart
    const dailyCtx = document.getElementById('dailyUsageChart');
    if (dailyCtx && history.weekly) {
        if (consumerCharts.daily) {
            consumerCharts.daily.destroy();
        }
        
        const labels = history.weekly.map(d => d.date);
        const data = history.weekly.map(d => d.total);
        
        consumerCharts.daily = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Usage (L)',
                    data: data,
                    backgroundColor: 'rgba(102, 126, 234, 0.6)',
                    borderColor: 'rgba(102, 126, 234, 1)',
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
                                return `Usage: ${context.parsed.y.toFixed(1)} L`;
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
    
    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyUsageChart');
    if (monthlyCtx && history.monthly) {
        if (consumerCharts.monthly) {
            consumerCharts.monthly.destroy();
        }
        
        const monthlyData = history.monthly;
        const labels = monthlyData.map(d => d.date);
        const data = monthlyData.map(d => d.total);
        
        consumerCharts.monthly = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Usage (L)',
                    data: data,
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#764ba2',
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
}

// ============================================
// ALERTS RENDERING
// ============================================
function renderAlerts(properties) {
    const container = document.getElementById('alertsList');
    if (!container) return;
    
    let allAlerts = [];
    properties.forEach(property => {
        if (property.alerts) {
            allAlerts = allAlerts.concat(property.alerts.map(alert => ({
                ...alert,
                property_address: property.address
            })));
        }
    });
    
    if (allAlerts.length === 0) {
        container.innerHTML = `
            <div class="no-alerts">
                <i class="fas fa-check-circle" style="color: #48bb78; font-size: 24px;"></i>
                <p>No active alerts. Everything is working properly!</p>
            </div>
        `;
        return;
    }
    
    // Sort by newest first
    allAlerts.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    
    container.innerHTML = allAlerts.slice(0, 10).map(alert => `
        <div class="alert-item alert-${alert.severity}">
            <div class="alert-icon">
                <i class="fas fa-${getAlertIcon(alert.alert_type)}"></i>
            </div>
            <div class="alert-content">
                <div class="alert-message">${alert.message}</div>
                <div class="alert-meta">
                    <span class="alert-property">${alert.property_address || 'Unknown Property'}</span>
                    <span class="alert-time">${formatDate(alert.created_at)}</span>
                </div>
            </div>
            <div class="alert-actions">
                <button onclick="acknowledgeAlert('${alert.id}')" class="btn-sm btn-outline">
                    Acknowledge
                </button>
            </div>
        </div>
    `).join('');
}

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

// ============================================
// PROPERTY ACTIONS
// ============================================
function viewProperty(propertyId) {
    navigateTo(`property-details?id=${propertyId}`);
}

function configureAlerts(propertyId) {
    navigateTo(`alert-settings?property=${propertyId}`);
}

function addProperty() {
    // Show modal to add property
    showModal('addProperty');
}

// ============================================
// ALERT ACTIONS
// ============================================
async function acknowledgeAlert(alertId) {
    try {
        const response = await API.put(`/alerts/acknowledge.php`, { alert_id: alertId });
        
        if (response.success) {
            showToast('Alert acknowledged', 'success');
            // Refresh dashboard
            loadConsumerData();
        } else {
            showToast('Failed to acknowledge alert', 'danger');
        }
    } catch (error) {
        console.error('Acknowledge alert error:', error);
        showToast('Error acknowledging alert', 'danger');
    }
}

// ============================================
// MUNICIPAL DASHBOARD
// ============================================
let municipalCharts = {};

async function loadMunicipalData() {
    showLoading(true);
    
    try {
        const data = await API.get('/dashboard/municipal.php');
        
        if (data.success) {
            renderMunicipalDashboard(data.data);
        } else {
            showToast('Failed to load municipal data', 'danger');
        }
    } catch (error) {
        console.error('Municipal dashboard error:', error);
        showToast('Error loading municipal dashboard', 'danger');
    } finally {
        showLoading(false);
    }
}

function renderMunicipalDashboard(data) {
    const container = document.getElementById('dashboardContent');
    if (!container) return;
    
    const summary = data.summary || {};
    const topAreas = data.top_nrw_areas || [];
    const dailyTrend = data.daily_trend || [];
    const recentAlerts = data.recent_alerts || [];
    
    container.innerHTML = `
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-value">${summary.total_meters || 0}</div>
                    <div class="stat-label">Total Meters</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🟢</div>
                <div class="stat-info">
                    <div class="stat-value">${summary.online_meters || 0}</div>
                    <div class="stat-label">Online Meters</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <div class="stat-value">${summary.total_consumers || 0}</div>
                    <div class="stat-label">Total Consumers</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💧</div>
                <div class="stat-info">
                    <div class="stat-value">${(summary.today_usage || 0).toLocaleString()}</div>
                    <div class="stat-label">Today's Usage (L)</div>
                </div>
            </div>
            <div class="stat-card stat-nrw">
                <div class="stat-icon">📉</div>
                <div class="stat-info">
                    <div class="stat-value">${summary.nrw_percentage || 0}%</div>
                    <div class="stat-label">NRW Percentage</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <div class="stat-value">${formatCurrency(summary.nrw_volume * 25 / 1000 || 0)}</div>
                    <div class="stat-label">NRW Financial Impact</div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Daily Usage Trend</h3>
                <canvas id="dailyTrendChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Top NRW Areas</h3>
                <canvas id="nrwAreasChart"></canvas>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div class="bottom-grid">
            <div class="recent-alerts-section">
                <h3>Recent Alerts</h3>
                <div id="recentAlertsList"></div>
            </div>
            <div class="quick-actions-section">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <button onclick="generateNRWReport()" class="btn-primary">
                        <i class="fas fa-file-alt"></i> Generate NRW Report
                    </button>
                    <button onclick="navigateTo('consumer-management')" class="btn-secondary">
                        <i class="fas fa-users"></i> Manage Consumers
                    </button>
                    <button onclick="navigateTo('tariff-management')" class="btn-secondary">
                        <i class="fas fa-coins"></i> Manage Tariffs
                    </button>
                    <button onclick="navigateTo('device-management')" class="btn-secondary">
                        <i class="fas fa-microchip"></i> Device Management
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Initialize charts after DOM update
    setTimeout(() => {
        initMunicipalCharts(data);
        renderRecentAlerts(recentAlerts);
    }, 100);
}

function initMunicipalCharts(data) {
    // Daily Trend Chart
    const dailyCtx = document.getElementById('dailyTrendChart');
    if (dailyCtx && data.daily_trend) {
        if (municipalCharts.daily) {
            municipalCharts.daily.destroy();
        }
        
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
                    pointRadius: 4
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
    
    // NRW Areas Chart
    const nrwCtx = document.getElementById('nrwAreasChart');
    if (nrwCtx && data.top_nrw_areas) {
        if (municipalCharts.nrw) {
            municipalCharts.nrw.destroy();
        }
        
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
                        borderWidth: 2
                    },
                    {
                        label: 'Billed (kL)',
                        data: areas.map(d => d.billed / 1000),
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
                            usePointStyle: true,
                            padding: 20
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
}

function renderRecentAlerts(alerts) {
    const container = document.getElementById('recentAlertsList');
    if (!container) return;
    
    if (!alerts || alerts.length === 0) {
        container.innerHTML = `
            <div class="no-alerts">
                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                <p>No recent alerts</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = alerts.slice(0, 10).map(alert => `
        <div class="alert-item alert-${alert.severity}">
            <div class="alert-icon">
                <i class="fas fa-${getAlertIcon(alert.alert_type)}"></i>
            </div>
            <div class="alert-content">
                <div class="alert-message">${alert.message}</div>
                <div class="alert-meta">
                    <span class="alert-user">${alert.name} ${alert.surname}</span>
                    <span class="alert-time">${formatDate(alert.created_at)}</span>
                </div>
            </div>
            <div class="alert-actions">
                <span class="badge ${alert.status}">${alert.status}</span>
            </div>
        </div>
    `).join('');
}

// ============================================
// NRW REPORT GENERATION
// ============================================
async function generateNRWReport() {
    const startDate = prompt('Enter start date (YYYY-MM-DD):', 
        new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
    if (!startDate) return;
    
    const endDate = prompt('Enter end date (YYYY-MM-DD):', 
        new Date().toISOString().split('T')[0]);
    if (!endDate) return;
    
    showLoading(true);
    
    try {
        const data = await API.post('/reports/generate_nrw.php', {
            start_date: startDate,
            end_date: endDate
        });
        
        if (data.success) {
            // Show report in a modal or download as CSV
            showNRWReport(data.report);
        } else {
            showToast('Failed to generate report', 'danger');
        }
    } catch (error) {
        console.error('NRW report error:', error);
        showToast('Error generating report', 'danger');
    } finally {
        showLoading(false);
    }
}

function showNRWReport(report) {
    // Create modal with report data
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> NRW Report</h3>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="report-summary">
                    <div class="report-stat">
                        <label>Period</label>
                        <span>${report.period.start} to ${report.period.end}</span>
                    </div>
                    <div class="report-stat">
                        <label>Total Supplied</label>
                        <span>${report.summary.total_supplied.toFixed(2)} kL</span>
                    </div>
                    <div class="report-stat">
                        <label>Total Billed</label>
                        <span>${report.summary.total_billed.toFixed(2)} kL</span>
                    </div>
                    <div class="report-stat">
                        <label>NRW Volume</label>
                        <span>${report.summary.nrw_volume.toFixed(2)} kL</span>
                    </div>
                    <div class="report-stat highlight">
                        <label>NRW Percentage</label>
                        <span>${report.summary.nrw_percentage}%</span>
                    </div>
                    <div class="report-stat">
                        <label>Financial Impact</label>
                        <span>${formatCurrency(report.summary.financial_impact)}</span>
                    </div>
                </div>
                <div class="report-breakdown">
                    <h4>Breakdown by Area</h4>
                    <table class="table">
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
                                    <td>${item.suburb}</td>
                                    <td>${item.supplied.toFixed(2)}</td>
                                    <td>${item.billed.toFixed(2)}</td>
                                    <td>${item.nrw.toFixed(2)}</td>
                                    <td>${item.nrw_percentage}%</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="downloadNRWReport(${JSON.stringify(report).replace(/"/g, '&quot;')})" 
                        class="btn-primary">
                    <i class="fas fa-download"></i> Download CSV
                </button>
                <button onclick="this.closest('.modal-overlay').remove()" class="btn-secondary">
                    Close
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function downloadNRWReport(report) {
    // Create CSV
    let csv = 'Suburb,Supplied,Billed,NRW,NRW%\n';
    report.breakdown.forEach(item => {
        csv += `${item.suburb},${item.supplied.toFixed(2)},${item.billed.toFixed(2)},${item.nrw.toFixed(2)},${item.nrw_percentage}%\n`;
    });
    
    // Add summary
    csv += '\nSummary,,,,,\n';
    csv += `Total Supplied,${report.summary.total_supplied.toFixed(2)},,,,\n`;
    csv += `Total Billed,${report.summary.total_billed.toFixed(2)},,,,\n`;
    csv += `NRW Volume,${report.summary.nrw_volume.toFixed(2)},,,,\n`;
    csv += `NRW Percentage,${report.summary.nrw_percentage}%,,,,\n`;
    csv += `Financial Impact,${formatCurrency(report.summary.financial_impact)},,,,\n`;
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `NRW_Report_${report.period.start}_to_${report.period.end}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// ============================================
// CHART DESTROY ON NAVIGATION
// ============================================
function destroyCharts() {
    Object.keys(consumerCharts).forEach(key => {
        if (consumerCharts[key]) {
            consumerCharts[key].destroy();
            delete consumerCharts[key];
        }
    });
    Object.keys(municipalCharts).forEach(key => {
        if (municipalCharts[key]) {
            municipalCharts[key].destroy();
            delete municipalCharts[key];
        }
    });
}

// ============================================
// EXPOSE FUNCTIONS TO GLOBAL SCOPE
// ============================================
window.loadConsumerData = loadConsumerData;
window.loadMunicipalData = loadMunicipalData;
window.viewProperty = viewProperty;
window.configureAlerts = configureAlerts;
window.addProperty = addProperty;
window.acknowledgeAlert = acknowledgeAlert;
window.generateNRWReport = generateNRWReport;
window.downloadNRWReport = downloadNRWReport;
window.destroyCharts = destroyCharts;
