<?php
/**
 * Consumer Dashboard
 * Main dashboard for consumers showing water usage, alerts, and statistics
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'consumer') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'My Dashboard';
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
    <div class="consumer-layout">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <h1>💧 Smart Water Guardian</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="alert-badge-container">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="alertBadge">0</span>
                </div>
                <span class="user-name"><?php echo $_SESSION['name'] ?? 'User'; ?></span>
                <span class="role-badge">Consumer</span>
                <button onclick="logout()" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="consumer-nav">
            <a href="?page=dashboard" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="?page=usage_history" class="nav-link">
                <i class="fas fa-chart-line"></i> Usage History
            </a>
            <a href="?page=bill_estimator" class="nav-link">
                <i class="fas fa-file-invoice-dollar"></i> Bill Estimator
            </a>
            <a href="?page=alerts" class="nav-link">
                <i class="fas fa-bell"></i> Alerts
            </a>
            <a href="?page=settings" class="nav-link">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-body" id="dashboardContent">
                <!-- Dashboard will be loaded by JavaScript -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading your dashboard...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Quick Action Modals -->
    <!-- Add Property Modal -->
    <div class="modal" id="addPropertyModal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-home"></i> Add Property</h3>
                <button class="modal-close" onclick="closeModal('addPropertyModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addPropertyForm" onsubmit="addProperty(event)">
                    <div class="form-group">
                        <label>Address <span class="required">*</span></label>
                        <input type="text" id="propertyAddress" placeholder="Enter property address" required>
                    </div>
                    <div class="form-group">
                        <label>Property Type</label>
                        <select id="propertyType">
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Meter ID (Optional)</label>
                        <input type="text" id="propertyMeter" placeholder="Enter meter ID if you have one">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('addPropertyModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <span id="addPropertyText">Add Property</span>
                            <span id="addPropertySpinner" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Alert Settings Modal -->
    <div class="modal" id="alertSettingsModal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-bell"></i> Alert Settings</h3>
                <button class="modal-close" onclick="closeModal('alertSettingsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="alertSettingsBody">
                <div class="loading">Loading settings...</div>
            </div>
        </div>
    </div>

    <script>
    // ============================================
    // CONSUMER DASHBOARD
    // ============================================
    let dashboardCharts = {};
    let properties = [];
    let activeProperty = null;

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
            const response = await fetch('/api/modules/dashboard/consumer.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                properties = data.data.properties || [];
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
        
        if (!data.properties || data.properties.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>Welcome to Smart Water Guardian!</h3>
                    <p>You don't have any properties registered yet. Add your first property to start monitoring.</p>
                    <button onclick="showAddPropertyModal()" class="btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Add Property
                    </button>
                </div>
            `;
            return;
        }

        // Build dashboard
        let html = `
            <!-- Quick Stats -->
            <div class="stats-grid" id="statsGrid">
                ${data.properties.map(p => renderPropertyStats(p)).join('')}
            </div>
            
            <!-- Property Cards -->
            <div class="property-cards">
                ${data.properties.map(p => renderPropertyCard(p)).join('')}
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <h3>Daily Usage Trend</h3>
                    <canvas id="dailyChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Monthly Comparison</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Alerts -->
            <div class="alerts-section">
                <div class="section-header">
                    <h3><i class="fas fa-bell"></i> Recent Alerts</h3>
                    <a href="?page=alerts" class="view-all">View All</a>
                </div>
                <div id="recentAlerts">
                    ${renderRecentAlerts(data.properties)}
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Initialize charts after DOM update
        setTimeout(() => {
            if (data.properties.length > 0) {
                initCharts(data.properties[0]);
            }
        }, 500);
    }

    // ============================================
    // RENDER PROPERTY STATS
    // ============================================
    function renderPropertyStats(property) {
        const usage = property.current_usage || {};
        const meter = property.meter || {};
        
        return `
            <div class="stat-card" data-property="${property.id}">
                <div class="stat-icon">💧</div>
                <div class="stat-info">
                    <div class="stat-value">${(usage.flow_rate || 0).toFixed(1)}</div>
                    <div class="stat-label">Current Flow (L/min)</div>
                </div>
                <div class="stat-sub">
                    Today: ${(usage.today_total || 0).toFixed(1)} L
                </div>
            </div>
            <div class="stat-card" data-property="${property.id}">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-value">${(usage.week_total || 0).toFixed(1)}</div>
                    <div class="stat-label">This Week (L)</div>
                </div>
                <div class="stat-sub">
                    Month: ${(usage.month_total || 0).toFixed(1)} L
                </div>
            </div>
            <div class="stat-card" data-property="${property.id}">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <div class="stat-value">R${calculateBill(usage.month_total || 0).toFixed(2)}</div>
                    <div class="stat-label">Est. Monthly Bill</div>
                </div>
                <div class="stat-sub">
                    ${meter.status || 'No meter'}
                </div>
            </div>
            <div class="stat-card" data-property="${property.id}">
                <div class="stat-icon">📡</div>
                <div class="stat-info">
                    <div class="stat-value">${meter.status || 'No Meter'}</div>
                    <div class="stat-label">Meter Status</div>
                </div>
                <div class="stat-sub">
                    Battery: ${meter.battery || 0}%
                </div>
            </div>
        `;
    }

    // ============================================
    // RENDER PROPERTY CARD
    // ============================================
    function renderPropertyCard(property) {
        const alerts = property.alerts || [];
        const hasAlerts = alerts.some(a => a.status === 'active');
        
        return `
            <div class="property-card ${hasAlerts ? 'has-alert' : ''}" data-property="${property.id}">
                <div class="property-header">
                    <div class="property-info">
                        <h4>${property.address}</h4>
                        <span class="property-type ${property.property_type}">
                            ${property.property_type}
                        </span>
                    </div>
                    <div class="property-status">
                        <span class="status-badge ${property.meter.status || 'offline'}">
                            <i class="fas fa-${property.meter.status === 'online' ? 'circle' : 'circle'}"></i>
                            ${property.meter.status || 'No Meter'}
                        </span>
                        <span class="meter-id">${property.meter.id || 'No Meter'}</span>
                    </div>
                </div>
                <div class="property-usage">
                    <div class="usage-item">
                        <label>Flow Rate</label>
                        <span class="value">${(property.current_usage.flow_rate || 0).toFixed(1)} <small>L/min</small></span>
                    </div>
                    <div class="usage-item">
                        <label>Today</label>
                        <span class="value">${(property.current_usage.today_total || 0).toFixed(1)} <small>L</small></span>
                    </div>
                    <div class="usage-item">
                        <label>This Week</label>
                        <span class="value">${(property.current_usage.week_total || 0).toFixed(1)} <small>L</small></span>
                    </div>
                    <div class="usage-item">
                        <label>This Month</label>
                        <span class="value">${(property.current_usage.month_total || 0).toFixed(1)} <small>L</small></span>
                    </div>
                </div>
                ${hasAlerts ? `
                    <div class="property-alerts">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>${alerts.filter(a => a.status === 'active').length} active alert(s)</span>
                    </div>
                ` : ''}
                <div class="property-actions">
                    <button onclick="viewPropertyDetails('${property.id}')" class="btn-sm btn-primary">
                        <i class="fas fa-chart-line"></i> Details
                    </button>
                    <button onclick="configureAlerts('${property.id}')" class="btn-sm btn-secondary">
                        <i class="fas fa-bell"></i> Alerts
                    </button>
                </div>
            </div>
        `;
    }

    // ============================================
    // RENDER RECENT ALERTS
    // ============================================
    function renderRecentAlerts(properties) {
        let allAlerts = [];
        properties.forEach(p => {
            if (p.alerts) {
                allAlerts = allAlerts.concat(p.alerts.map(a => ({
                    ...a,
                    property_address: p.address
                })));
            }
        });
        
        if (allAlerts.length === 0) {
            return `
                <div class="no-alerts">
                    <i class="fas fa-check-circle" style="color: #48bb78; font-size: 32px;"></i>
                    <p>No alerts. Everything is working properly!</p>
                </div>
            `;
        }
        
        // Sort by newest first
        allAlerts.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        
        return allAlerts.slice(0, 5).map(alert => `
            <div class="alert-item alert-${alert.severity}">
                <div class="alert-icon">
                    <i class="fas fa-${getAlertIcon(alert.alert_type)}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-message">${alert.message}</div>
                    <div class="alert-meta">
                        <span class="alert-property">${alert.property_address}</span>
                        <span class="alert-time">${formatDate(alert.created_at)}</span>
                    </div>
                </div>
                ${alert.status === 'active' ? `
                    <button onclick="acknowledgeAlert('${alert.id}')" class="btn-sm btn-outline">
                        Acknowledge
                    </button>
                ` : `
                    <span class="badge ${alert.status}">${alert.status}</span>
                `}
            </div>
        `).join('');
    }

    // ============================================
    // INIT CHARTS
    // ============================================
    function initCharts(property) {
        const history = property.history || {};
        
        // Destroy existing charts
        Object.values(dashboardCharts).forEach(chart => {
            if (chart) chart.destroy();
        });
        dashboardCharts = {};
        
        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart');
        if (dailyCtx && history.weekly && history.weekly.length > 0) {
            const labels = history.weekly.map(d => d.date);
            const data = history.weekly.map(d => d.total);
            
           
