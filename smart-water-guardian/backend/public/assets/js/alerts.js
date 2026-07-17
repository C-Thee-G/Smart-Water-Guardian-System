/**
 * Smart Water Guardian - Alert Management System
 * Handles alert fetching, rendering, and actions
 */

// ============================================
// ALERT CONFIGURATION
// ============================================
const AlertConfig = {
    types: {
        leak: {
            icon: 'faucet',
            color: '#fc8181',
            label: 'Leak Detected'
        },
        critical_leak: {
            icon: 'exclamation-triangle',
            color: '#e53e3e',
            label: 'Critical Leak'
        },
        threshold: {
            icon: 'chart-line',
            color: '#f6ad55',
            label: 'Threshold Exceeded'
        },
        battery_low: {
            icon: 'battery-quarter',
            color: '#f6ad55',
            label: 'Low Battery'
        },
        offline: {
            icon: 'wifi-slash',
            color: '#a0aec0',
            label: 'Device Offline'
        },
        high_usage: {
            icon: 'fire',
            color: '#fc8181',
            label: 'High Usage'
        }
    },
    severity: {
        info: { color: '#4facfe', label: 'Info' },
        warning: { color: '#f6ad55', label: 'Warning' },
        high: { color: '#fc8181', label: 'High' },
        critical: { color: '#e53e3e', label: 'Critical' }
    },
    refreshInterval: 30000, // 30 seconds
    maxDisplay: 50
};

// ============================================
// ALERT STATE
// ============================================
const AlertState = {
    alerts: [],
    unreadCount: 0,
    loading: false,
    filters: {
        severity: 'all',
        type: 'all',
        status: 'active',
        search: ''
    },
    pagination: {
        page: 1,
        limit: 20,
        total: 0
    }
};

// ============================================
// ALERT API
// ============================================
const AlertAPI = {
    async getAlerts(params = {}) {
        const defaultParams = {
            limit: AlertState.pagination.limit,
            page: AlertState.pagination.page,
            severity: AlertState.filters.severity,
            type: AlertState.filters.type,
            status: AlertState.filters.status,
            search: AlertState.filters.search
        };
        
        const queryParams = { ...defaultParams, ...params };
        return await API.get('/alerts/get_alerts.php', queryParams);
    },
    
    async acknowledgeAlert(alertId) {
        return await API.put(`/alerts/acknowledge.php`, { alert_id: alertId });
    },
    
    async resolveAlert(alertId) {
        return await API.put(`/alerts/resolve.php`, { alert_id: alertId });
    },
    
    async getAlertCounts() {
        return await API.get('/alerts/counts.php');
    },
    
    async configureThresholds(thresholds) {
        return await API.post('/alerts/configure.php', { thresholds });
    },
    
    async getThresholds() {
        return await API.get('/alerts/thresholds.php');
    }
};

// ============================================
// ALERT RENDERER
// ============================================
class AlertRenderer {
    static renderAlertList(alerts, container) {
        if (!container) return;
        
        if (!alerts || alerts.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color: #48bb78; font-size: 48px;"></i>
                    <h3>No Alerts</h3>
                    <p>All systems are operating normally.</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = alerts.map(alert => this.renderAlertItem(alert)).join('');
    }
    
    static renderAlertItem(alert) {
        const typeConfig = AlertConfig.types[alert.alert_type] || AlertConfig.types.threshold;
        const severityConfig = AlertConfig.severity[alert.severity] || AlertConfig.severity.info;
        const isActive = alert.status === 'active';
        
        return `
            <div class="alert-item ${alert.status} severity-${alert.severity}" 
                 data-alert-id="${alert.id}"
                 data-severity="${alert.severity}"
                 data-status="${alert.status}">
                <div class="alert-icon" style="color: ${typeConfig.color}">
                    <i class="fas fa-${typeConfig.icon}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-header">
                        <span class="alert-type" style="color: ${typeConfig.color}">
                            ${typeConfig.label}
                        </span>
                        <span class="alert-severity" style="background: ${severityConfig.color}">
                            ${severityConfig.label}
                        </span>
                        <span class="alert-status ${alert.status}">
                            ${alert.status}
                        </span>
                    </div>
                    <div class="alert-message">${alert.message}</div>
                    <div class="alert-meta">
                        ${alert.property_address ? `
                            <span class="alert-property">
                                <i class="fas fa-home"></i> ${alert.property_address}
                            </span>
                        ` : ''}
                        ${alert.meter_id ? `
                            <span class="alert-meter">
                                <i class="fas fa-microchip"></i> ${alert.meter_id}
                            </span>
                        ` : ''}
                        <span class="alert-time">
                            <i class="fas fa-clock"></i> ${formatDate(alert.created_at)}
                        </span>
                    </div>
                </div>
                <div class="alert-actions">
                    ${isActive ? `
                        <button onclick="Alerts.acknowledge('${alert.id}')" 
                                class="btn-sm btn-primary">
                            <i class="fas fa-check"></i> Acknowledge
                        </button>
                        <button onclick="Alerts.resolve('${alert.id}')" 
                                class="btn-sm btn-secondary">
                            <i class="fas fa-times"></i> Resolve
                        </button>
                    ` : `
                        <span class="badge ${alert.status}">
                            ${alert.status}
                        </span>
                    `}
                    <button onclick="Alerts.viewDetails('${alert.id}')" 
                            class="btn-sm btn-outline">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    static renderAlertCounts(counts, container) {
        if (!container) return;
        
        const total = counts.active || 0;
        const critical = counts.critical || 0;
        const high = counts.high || 0;
        const warning = counts.warning || 0;
        
        container.innerHTML = `
            <div class="alert-counts">
                <div class="count-item total">
                    <span class="count">${total}</span>
                    <span class="label">Total</span>
                </div>
                <div class="count-item critical">
                    <span class="count">${critical}</span>
                    <span class="label">Critical</span>
                </div>
                <div class="count-item high">
                    <span class="count">${high}</span>
                    <span class="label">High</span>
                </div>
                <div class="count-item warning">
                    <span class="count">${warning}</span>
                    <span class="label">Warning</span>
                </div>
            </div>
        `;
    }
}

// ============================================
// ALERT FILTERS
// ============================================
class AlertFilters {
    static renderFilters(container) {
        if (!container) return;
        
        const severityOptions = ['all', 'critical', 'high', 'warning', 'info'];
        const statusOptions = ['all', 'active', 'acknowledged', 'resolved'];
        const typeOptions = ['all', ...Object.keys(AlertConfig.types)];
        
        container.innerHTML = `
            <div class="alert-filters">
                <div class="filter-group">
                    <label>Severity</label>
                    <select id="filterSeverity" onchange="Alerts.applyFilter('severity', this.value)">
                        ${severityOptions.map(s => `
                            <option value="${s}" ${AlertState.filters.severity === s ? 'selected' : ''}>
                                ${s.charAt(0).toUpperCase() + s.slice(1)}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" onchange="Alerts.applyFilter('status', this.value)">
                        ${statusOptions.map(s => `
                            <option value="${s}" ${AlertState.filters.status === s ? 'selected' : ''}>
                                ${s.charAt(0).toUpperCase() + s.slice(1)}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select id="filterType" onchange="Alerts.applyFilter('type', this.value)">
                        ${typeOptions.map(t => {
                            const label = t === 'all' ? 'All Types' : 
                                         (AlertConfig.types[t] ? AlertConfig.types[t].label : t);
                            return `
                                <option value="${t}" ${AlertState.filters.type === t ? 'selected' : ''}>
                                    ${label}
                                </option>
                            `;
                        }).join('')}
                    </select>
                </div>
                <div class="filter-group search">
                    <label>Search</label>
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="filterSearch" 
                               placeholder="Search alerts..." 
                               value="${AlertState.filters.search}"
                               oninput="Alerts.applyFilter('search', this.value)">
                    </div>
                </div>
            </div>
        `;
    }
}

// ============================================
// MAIN ALERT CONTROLLER
// ============================================
const Alerts = {
    // ============================================
    // INITIALIZATION
    // ============================================
    init() {
        this.setupEventListeners();
        this.loadAlerts();
        this.startAutoRefresh();
        this.loadAlertCounts();
    },
    
    setupEventListeners() {
        // Setup alert item actions
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-alert-action]');
            if (target) {
                const action = target.dataset.alertAction;
                const alertId = target.dataset.alertId;
                if (action && alertId) {
                    this[action](alertId);
                }
            }
        });
    },
    
    // ============================================
    // LOAD ALERTS
    // ============================================
    async loadAlerts(params = {}) {
        if (AlertState.loading) return;
        
        AlertState.loading = true;
        showLoading(true);
        
        try {
            const data = await AlertAPI.getAlerts(params);
            
            if (data.success) {
                AlertState.alerts = data.alerts || [];
                AlertState.pagination.total = data.total || 0;
                this.renderAlerts();
            } else {
                showToast('Failed to load alerts', 'danger');
            }
        } catch (error) {
            console.error('Load alerts error:', error);
            showToast('Error loading alerts', 'danger');
        } finally {
            AlertState.loading = false;
            showLoading(false);
        }
    },
    
    // ============================================
    // RENDER ALERTS
    // ============================================
    renderAlerts() {
        const container = document.getElementById('alertsList');
        if (container) {
            AlertRenderer.renderAlertList(AlertState.alerts, container);
        }
        
        const countsContainer = document.getElementById('alertCounts');
        if (countsContainer) {
            this.loadAlertCounts();
        }
    },
    
    // ============================================
    // ALERT COUNTS
    // ============================================
    async loadAlertCounts() {
        try {
            const data = await AlertAPI.getAlertCounts();
            if (data.success) {
                AlertState.unreadCount = data.counts.active || 0;
                this.updateBadge();
                
                const container = document.getElementById('alertCounts');
                if (container) {
                    AlertRenderer.renderAlertCounts(data.counts, container);
                }
            }
        } catch (error) {
            console.error('Load counts error:', error);
        }
    },
    
    updateBadge() {
        const badge = document.getElementById('alertBadge');
        if (badge) {
            const count = AlertState.unreadCount;
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    },
    
    // ============================================
    // ALERT ACTIONS
    // ============================================
    async acknowledge(alertId) {
        try {
            const data = await AlertAPI.acknowledgeAlert(alertId);
            if (data.success) {
                showToast('Alert acknowledged', 'success');
                this.loadAlerts();
                this.loadAlertCounts();
            } else {
                showToast('Failed to acknowledge alert', 'danger');
            }
        } catch (error) {
            console.error('Acknowledge error:', error);
            showToast('Error acknowledging alert', 'danger');
        }
    },
    
    async resolve(alertId) {
        if (!confirm('Are you sure you want to resolve this alert?')) return;
        
        try {
            const data = await AlertAPI.resolveAlert(alertId);
            if (data.success) {
                showToast('Alert resolved', 'success');
                this.loadAlerts();
                this.loadAlertCounts();
            } else {
                showToast('Failed to resolve alert', 'danger');
            }
        } catch (error) {
            console.error('Resolve error:', error);
            showToast('Error resolving alert', 'danger');
        }
    },
    
    viewDetails(alertId) {
        navigateTo(`alert-details?id=${alertId}`);
    },
    
    // ============================================
    // FILTERS
    // ============================================
    applyFilter(filter, value) {
        AlertState.filters[filter] = value;
        AlertState.pagination.page = 1;
        this.loadAlerts({
            page: AlertState.pagination.page,
            limit: AlertState.pagination.limit,
            ...AlertState.filters
        });
    },
    
    resetFilters() {
        AlertState.filters = {
            severity: 'all',
            type: 'all',
            status: 'active',
            search: ''
        };
        AlertState.pagination.page = 1;
        
        // Reset form controls
        document.getElementById('filterSeverity')?.value = 'all';
        document.getElementById('filterStatus')?.value = 'active';
        document.getElementById('filterType')?.value = 'all';
        document.getElementById('filterSearch')?.value = '';
        
        this.loadAlerts();
    },
    
    // ============================================
    // PAGINATION
    // ============================================
    loadPage(page) {
        AlertState.pagination.page = page;
        this.loadAlerts({
            page: page,
            limit: AlertState.pagination.limit,
            ...AlertState.filters
        });
    },
    
    // ============================================
    // AUTO REFRESH
    // ============================================
    startAutoRefresh() {
        setInterval(() => {
            this.loadAlerts({
                page: AlertState.pagination.page,
                limit: AlertState.pagination.limit,
                ...AlertState.filters
            });
            this.loadAlertCounts();
        }, AlertConfig.refreshInterval);
    },
    
    // ============================================
    // THRESHOLD CONFIGURATION
    // ============================================
    async loadThresholds() {
        try {
            const data = await AlertAPI.getThresholds();
            if (data.success) {
                this.renderThresholds(data.thresholds);
            }
        } catch (error) {
            console.error('Load thresholds error:', error);
        }
    },
    
    renderThresholds(thresholds) {
        const container = document.getElementById('thresholdSettings');
        if (!container) return;
        
        container.innerHTML = `
            <form id="thresholdForm" onsubmit="Alerts.saveThresholds(event)">
                <div class="threshold-group">
                    <h4>Flow Rate Thresholds</h4>
                    <div class="threshold-item">
                        <label>Leak Detection (L/min)</label>
                        <input type="number" id="leakThreshold" 
                               value="${thresholds.leak_flow_rate || 20}"
                               step="0.5" min="1">
                        <span class="help-text">Flow rate above this triggers a leak alert</span>
                    </div>
                    <div class="threshold-item">
                        <label>Critical Leak (L/min)</label>
                        <input type="number" id="criticalLeakThreshold" 
                               value="${thresholds.critical_leak_flow_rate || 30}"
                               step="0.5" min="1">
                        <span class="help-text">Flow rate above this triggers a critical alert</span>
                    </div>
                    <div class="threshold-item">
                        <label>High Usage (L/min)</label>
                        <input type="number" id="highUsageThreshold" 
                               value="${thresholds.high_usage_flow_rate || 25}"
                               step="0.5" min="1">
                        <span class="help-text">Flow rate above this triggers a high usage warning</span>
                    </div>
                </div>
                <div class="threshold-group">
                    <h4>System Thresholds</h4>
                    <div class="threshold-item">
                        <label>Low Battery (%)</label>
                        <input type="number" id="batteryThreshold" 
                               value="${thresholds.low_battery || 20}"
                               step="5" min="1" max="50">
                        <span class="help-text">Battery below this triggers a low battery alert</span>
                    </div>
                    <div class="threshold-item">
                        <label>Offline Timeout (minutes)</label>
                        <input type="number" id="offlineTimeout" 
                               value="${(thresholds.offline_timeout || 7200) / 60}"
                               step="5" min="5">
                        <span class="help-text">Device offline for this long triggers an offline alert</span>
                    </div>
                </div>
                <div class="threshold-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Thresholds
                    </button>
                    <button type="button" class="btn-secondary" onclick="this.closest('form').reset()">
                        Reset
                    </button>
                </div>
            </form>
        `;
    },
    
    async saveThresholds(event) {
        event.preventDefault();
        
        const thresholds = {
            leak_flow_rate: parseFloat(document.getElementById('leakThreshold').value),
            critical_leak_flow_rate: parseFloat(document.getElementById('criticalLeakThreshold').value),
            high_usage_flow_rate: parseFloat(document.getElementById('highUsageThreshold').value),
            low_battery: parseFloat(document.getElementById('batteryThreshold').value),
            offline_timeout: parseFloat(document.getElementById('offlineTimeout').value) * 60
        };
        
        try {
            const data = await AlertAPI.configureThresholds(thresholds);
            if (data.success) {
                showToast('Thresholds saved successfully', 'success');
            } else {
                showToast('Failed to save thresholds', 'danger');
            }
        } catch (error) {
            console.error('Save thresholds error:', error);
            showToast('Error saving thresholds', 'danger');
        }
    }
};

// ============================================
// EXPOSE TO GLOBAL
// ============================================
window.Alerts = Alerts;
window.AlertAPI = AlertAPI;
window.AlertRenderer = AlertRenderer;
window.AlertFilters = AlertFilters;

// ============================================
// AUTO-INITIALIZE
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Check if alerts page
    if (document.getElementById('alertsPage')) {
        Alerts.init();
    }
    
    // Check if threshold settings page
    if (document.getElementById('thresholdSettings')) {
        Alerts.loadThresholds();
    }
});
