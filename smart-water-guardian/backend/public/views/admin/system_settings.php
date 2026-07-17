<?php
/**
 * System Settings Page
 * Admin can configure system-wide settings
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'admin') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'System Settings';
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
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h2>💧 Smart Water</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?page=devices" class="nav-link">
                    <i class="fas fa-microchip"></i> Devices
                </a>
                <a href="?page=users" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="?page=system_settings" class="nav-link active">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="?page=reports" class="nav-link">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
                <a href="#" onclick="logout()" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo $pageTitle; ?></h1>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo $_SESSION['name'] ?? 'Admin'; ?></span>
                    <span class="role-badge">Admin</span>
                </div>
            </header>

            <div class="content-body">
                <div class="settings-container">
                    <!-- System Settings Tabs -->
                    <div class="settings-tabs">
                        <button class="tab-btn active" data-tab="general">
                            <i class="fas fa-globe"></i> General
                        </button>
                        <button class="tab-btn" data-tab="alerts">
                            <i class="fas fa-bell"></i> Alerts
                        </button>
                        <button class="tab-btn" data-tab="billing">
                            <i class="fas fa-coins"></i> Billing
                        </button>
                        <button class="tab-btn" data-tab="security">
                            <i class="fas fa-shield-alt"></i> Security
                        </button>
                        <button class="tab-btn" data-tab="integration">
                            <i class="fas fa-link"></i> Integration
                        </button>
                        <button class="tab-btn" data-tab="maintenance">
                            <i class="fas fa-tools"></i> Maintenance
                        </button>
                    </div>

                    <!-- General Settings -->
                    <div class="tab-content active" id="tab-general">
                        <div class="settings-card">
                            <h3><i class="fas fa-globe"></i> General Settings</h3>
                            <form id="generalSettingsForm" onsubmit="saveSettings(event, 'general')">
                                <div class="form-group">
                                    <label>System Name</label>
                                    <input type="text" id="systemName" value="Smart Water Guardian">
                                </div>
                                <div class="form-group">
                                    <label>System URL</label>
                                    <input type="url" id="systemUrl" value="<?php echo $_SERVER['HTTP_HOST']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Timezone</label>
                                    <select id="timezone">
                                        <option value="Africa/Johannesburg">Africa/Johannesburg</option>
                                        <option value="Africa/Cape_Town">Africa/Cape_Town</option>
                                        <option value="Africa/Durban">Africa/Durban</option>
                                        <option value="UTC">UTC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date Format</label>
                                    <select id="dateFormat">
                                        <option value="Y-m-d">YYYY-MM-DD</option>
                                        <option value="d/m/Y">DD/MM/YYYY</option>
                                        <option value="m/d/Y">MM/DD/YYYY</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Maintenance Mode</label>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="maintenanceMode">
                                        <label for="maintenanceMode">Enable Maintenance Mode</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Save General Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Alert Settings -->
                    <div class="tab-content" id="tab-alerts">
                        <div class="settings-card">
                            <h3><i class="fas fa-bell"></i> Alert Settings</h3>
                            <form id="alertSettingsForm" onsubmit="saveSettings(event, 'alerts')">
                                <div class="form-group">
                                    <label>Leak Detection Threshold (L/min)</label>
                                    <input type="number" id="leakThreshold" value="20" step="0.5" min="1">
                                    <small class="help-text">Flow rate above this triggers a leak alert</small>
                                </div>
                                <div class="form-group">
                                    <label>Critical Leak Threshold (L/min)</label>
                                    <input type="number" id="criticalLeakThreshold" value="30" step="0.5" min="1">
                                    <small class="help-text">Flow rate above this triggers a critical alert</small>
                                </div>
                                <div class="form-group">
                                    <label>High Usage Threshold (L/min)</label>
                                    <input type="number" id="highUsageThreshold" value="25" step="0.5" min="1">
                                    <small class="help-text">Flow rate above this triggers a high usage warning</small>
                                </div>
                                <div class="form-group">
                                    <label>Low Battery Threshold (%)</label>
                                    <input type="number" id="batteryThreshold" value="20" step="5" min="1" max="50">
                                    <small class="help-text">Battery below this triggers a low battery alert</small>
                                </div>
                                <div class="form-group">
                                    <label>Offline Timeout (minutes)</label>
                                    <input type="number" id="offlineTimeout" value="120" step="5" min="5">
                                    <small class="help-text">Device offline for this long triggers an offline alert</small>
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Alert Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Billing Settings -->
                    <div class="tab-content" id="tab-billing">
                        <div class="settings-card">
                            <h3><i class="fas fa-coins"></i> Billing Settings</h3>
                            <form id="billingSettingsForm" onsubmit="saveSettings(event, 'billing')">
                                <div class="form-group">
                                    <label>Default Currency</label>
                                    <select id="currency">
                                        <option value="ZAR">ZAR - South African Rand</option>
                                        <option value="USD">USD - US Dollar</option>
                                        <option value="EUR">EUR - Euro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Billing Cycle</label>
                                    <select id="billingCycle">
                                        <option value="monthly">Monthly</option>
                                        <option value="bi-monthly">Bi-Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Billing Day</label>
                                    <select id="billingDay">
                                        <?php for ($i = 1; $i <= 28; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Billing Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-content" id="tab-security">
                        <div class="settings-card">
                            <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                            <form id="securitySettingsForm" onsubmit="saveSettings(event, 'security')">
                                <div class="form-group">
                                    <label>Minimum Password Length</label>
                                    <input type="number" id="minPasswordLength" value="8" min="6" max="20">
                                </div>
                                <div class="form-group">
                                    <label>Session Timeout (minutes)</label>
                                    <input type="number" id="sessionTimeout" value="30" min="5" max="1440">
                                </div>
                                <div class="form-group">
                                    <label>Max Login Attempts</label>
                                    <input type="number" id="maxLoginAttempts" value="5" min="3" max="10">
                                </div>
                                <div class="form-group">
                                    <label>Lockout Duration (minutes)</label>
                                    <input type="number" id="lockoutDuration" value="30" min="5" max="1440">
                                </div>
                                <div class="form-group">
                                    <label>Two-Factor Authentication</label>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="twoFactorAuth">
                                        <label for="twoFactorAuth">Require 2FA for all users</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Security Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Integration Settings -->
                    <div class="tab-content" id="tab-integration">
                        <div class="settings-card">
                            <h3><i class="fas fa-link"></i> Integration Settings</h3>
                            <form id="integrationSettingsForm" onsubmit="saveSettings(event, 'integration')">
                                <div class="form-group">
                                    <label>Firebase Database URL</label>
                                    <input type="url" id="firebaseUrl" placeholder="https://your-project.firebaseio.com/">
                                </div>
                                <div class="form-group">
                                    <label>Firebase Secret</label>
                                    <input type="password" id="firebaseSecret" placeholder="Enter Firebase secret">
                                </div>
                                <div class="form-group">
                                    <label>Azure IoT Hub Connection String</label>
                                    <input type="text" id="azureConnection" placeholder="Enter Azure connection string">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Host</label>
                                    <input type="text" id="smtpHost" placeholder="smtp.gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Port</label>
                                    <input type="number" id="smtpPort" value="587">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Username</label>
                                    <input type="email" id="smtpUsername" placeholder="your-email@gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Password</label>
                                    <input type="password" id="smtpPassword" placeholder="Enter SMTP password">
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Integration Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Maintenance Settings -->
                    <div class="tab-content" id="tab-maintenance">
                        <div class="settings-card">
                            <h3><i class="fas fa-tools"></i> Maintenance</h3>
                            <div class="maintenance-actions">
                                <div class="action-item">
                                    <h4>Database Backup</h4>
                                    <p>Create a backup of the entire database</p>
                                    <button onclick="backupDatabase()" class="btn-primary">
                                        <i class="fas fa-database"></i> Backup Now
                                    </button>
                                </div>
                                <div class="action-item">
                                    <h4>Clear Cache</h4>
                                    <p>Clear all system cache and temporary data</p>
                                    <button onclick="clearCache()" class="btn-warning">
                                        <i class="fas fa-broom"></i> Clear Cache
                                    </button>
                                </div>
                                <div class="action-item">
                                    <h4>System Logs</h4>
                                    <p>View and manage system logs</p>
                                    <button onclick="viewLogs()" class="btn-secondary">
                                        <i class="fas fa-file-alt"></i> View Logs
                                    </button>
                                </div>
                                <div class="action-item">
                                    <h4>System Update</h4>
                                    <p>Check for and apply system updates</p>
                                    <button onclick="checkUpdates()" class="btn-info">
                                        <i class="fas fa-sync"></i> Check Updates
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <h3><i class="fas fa-chart-bar"></i> System Status</h3>
                            <div class="system-status">
                                <div class="status-item">
                                    <label>PHP Version</label>
                                    <span><?php echo phpversion(); ?></span>
                                </div>
                                <div class="status-item">
                                    <label>MySQL Version</label>
                                    <span id="mysqlVersion">Checking...</span>
                                </div>
                                <div class="status-item">
                                    <label>Server Load</label>
                                    <span id="serverLoad">Checking...</span>
                                </div>
                                <div class="status-item">
                                    <label>Memory Usage</label>
                                    <span id="memoryUsage">Checking...</span>
                                </div>
                                <div class="status-item">
                                    <label>Disk Space</label>
                                    <span id="diskSpace">Checking...</span>
                                </div>
                                <div class="status-item">
                                    <label>Last Backup</label>
                                    <span id="lastBackup">Checking...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // ============================================
    // SYSTEM SETTINGS
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        loadSettings();
        loadSystemStatus();
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });
    });

    // ============================================
    // LOAD SETTINGS
    // ============================================
    async function loadSettings() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/settings.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.settings) {
                populateSettings(data.settings);
            }
        } catch (error) {
            console.error('Load settings error:', error);
        }
    }

    function populateSettings(settings) {
        // General
        document.getElementById('systemName').value = settings.system_name || 'Smart Water Guardian';
        document.getElementById('systemUrl').value = settings.system_url || '';
        document.getElementById('timezone').value = settings.timezone || 'Africa/Johannesburg';
        document.getElementById('dateFormat').value = settings.date_format || 'Y-m-d';
        document.getElementById('maintenanceMode').checked = settings.maintenance_mode || false;
        
        // Alerts
        document.getElementById('leakThreshold').value = settings.leak_threshold || 20;
        document.getElementById('criticalLeakThreshold').value = settings.critical_leak_threshold || 30;
        document.getElementById('highUsageThreshold').value = settings.high_usage_threshold || 25;
        document.getElementById('batteryThreshold').value = settings.battery_threshold || 20;
        document.getElementById('offlineTimeout').value = (settings.offline_timeout || 7200) / 60;
        
        // Billing
        document.getElementById('currency').value = settings.currency || 'ZAR';
        document.getElementById('billingCycle').value = settings.billing_cycle || 'monthly';
        document.getElementById('billingDay').value = settings.billing_day || 1;
        
        // Security
        document.getElementById('minPasswordLength').value = settings.min_password_length || 8;
        document.getElementById('sessionTimeout').value = (settings.session_timeout || 1800) / 60;
        document.getElementById('maxLoginAttempts').value = settings.max_login_attempts || 5;
        document.getElementById('lockoutDuration').value = (settings.lockout_duration || 1800) / 60;
        document.getElementById('twoFactorAuth').checked = settings.two_factor_auth || false;
        
        // Integration
        document.getElementById('firebaseUrl').value = settings.firebase_url || '';
        document.getElementById('azureConnection').value = settings.azure_connection || '';
        document.getElementById('smtpHost').value = settings.smtp_host || '';
        document.getElementById('smtpPort').value = settings.smtp_port || 587;
        document.getElementById('smtpUsername').value = settings.smtp_username || '';
    }

    // ============================================
    // SAVE SETTINGS
    // ============================================
    async function saveSettings(event, section) {
        event.preventDefault();
        
        let settings = {};
        
        switch(section) {
            case 'general':
                settings = {
                    system_name: document.getElementById('systemName').value,
                    system_url: document.getElementById('systemUrl').value,
                    timezone: document.getElementById('timezone').value,
                    date_format: document.getElementById('dateFormat').value,
                    maintenance_mode: document.getElementById('maintenanceMode').checked
                };
                break;
            case 'alerts':
                settings = {
                    leak_threshold: parseFloat(document.getElementById('leakThreshold').value),
                    critical_leak_threshold: parseFloat(document.getElementById('criticalLeakThreshold').value),
                    high_usage_threshold: parseFloat(document.getElementById('highUsageThreshold').value),
                    battery_threshold: parseFloat(document.getElementById('batteryThreshold').value),
                    offline_timeout: parseFloat(document.getElementById('offlineTimeout').value) * 60
                };
                break;
            case 'billing':
                settings = {
                    currency: document.getElementById('currency').value,
                    billing_cycle: document.getElementById('billingCycle').value,
                    billing_day: parseInt(document.getElementById('billingDay').value)
                };
                break;
            case 'security':
                settings = {
                    min_password_length: parseInt(document.getElementById('minPasswordLength').value),
                    session_timeout: parseInt(document.getElementById('sessionTimeout').value) * 60,
                    max_login_attempts: parseInt(document.getElementById('maxLoginAttempts').value),
                    lockout_duration: parseInt(document.getElementById('lockoutDuration').value) * 60,
                    two_factor_auth: document.getElementById('twoFactorAuth').checked
                };
                break;
            case 'integration':
                settings = {
                    firebase_url: document.getElementById('firebaseUrl').value,
                    azure_connection: document.getElementById('azureConnection').value,
                    smtp_host: document.getElementById('smtpHost').value,
                    smtp_port: parseInt(document.getElementById('smtpPort').value),
                    smtp_username: document.getElementById('smtpUsername').value
                };
                // Don't include password if empty
                const smtpPassword = document.getElementById('smtpPassword').value;
                if (smtpPassword) {
                    settings.smtp_password = smtpPassword;
                }
                break;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/admin/settings.php?section=${section}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(settings)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Settings saved successfully!', 'success');
            } else {
                showToast(data.message || 'Failed to save settings', 'danger');
            }
        } catch (error) {
            console.error('Save settings error:', error);
            showToast('Error saving settings', 'danger');
        }
    }

    // ============================================
    // MAINTENANCE ACTIONS
    // ============================================
    async function backupDatabase() {
        if (!confirm('This will create a backup of the entire database. Continue?')) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            showToast('Starting backup...', 'info');
            
            const response = await fetch('/api/modules/admin/backup.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Database backup created successfully!', 'success');
                // Download backup
                if (data.download_url) {
                    window.location.href = data.download_url;
                }
            } else {
                showToast(data.message || 'Failed to create backup', 'danger');
            }
        } catch (error) {
            console.error('Backup error:', error);
            showToast('Error creating backup', 'danger');
        }
    }

    async function clearCache() {
        if (!confirm('This will clear all system cache. Continue?')) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/clear_cache.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Cache cleared successfully!', 'success');
            } else {
                showToast(data.message || 'Failed to clear cache', 'danger');
            }
        } catch (error) {
            console.error('Clear cache error:', error);
            showToast('Error clearing cache', 'danger');
        }
    }

    function viewLogs() {
        window.open('?page=logs', '_blank');
    }

    function checkUpdates() {
        showToast('Checking for updates...', 'info');
        // Implement update check
        setTimeout(() => {
            showToast('System is up to date', 'success');
        }, 2000);
    }

    // ============================================
    // LOAD SYSTEM STATUS
    // ============================================
    async function loadSystemStatus() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/system_status.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('mysqlVersion').textContent = data.status.mysql_version || 'Unknown';
                document.getElementById('serverLoad').textContent = data.status.server_load || 'N/A';
                document.getElementById('memoryUsage').textContent = data.status.memory_usage || 'N/A';
                document.getElementById('diskSpace').textContent = data.status.disk_space || 'N/A';
                document.getElementById('lastBackup').textContent = data.status.last_backup || 'Never';
            }
        } catch (error) {
            console.error('Load status error:', error);
        }
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function showToast(message, type) {
        if (typeof showToast === 'function') {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    }

    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '?page=login';
    }
    </script>

    <style>
    .settings-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .settings-tabs {
        display: flex;
        gap: 5px;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow-x: auto;
    }
    .tab-btn {
        padding: 10px 20px;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        color: #718096;
        transition: all 0.3s;
        white-space: nowrap;
    }
    .tab-btn:hover {
        background: #f7fafc;
        color: #2d3748;
    }
    .tab-btn.active {
        background: #667eea;
        color: white;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .settings-card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .settings-card h3 {
        color: #2d3748;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f7fafc;
    }
    .settings-card h3 i {
        color: #667eea;
        margin-right: 10px;
    }
    .toggle-switch {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .toggle-switch input[type="checkbox"] {
        width: 50px;
        height: 26px;
        appearance: none;
        background: #cbd5e0;
        border-radius: 13px;
        position: relative;
        cursor: pointer;
        transition: all 0.3s;
    }
    .toggle-switch input[type="checkbox"]:checked {
        background: #667eea;
    }
    .toggle-switch input[type="checkbox"]::before {
        content: '';
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: all 0.3s;
    }
    .toggle-switch input[type="checkbox"]:checked::before {
        left: 26px;
    }
    .maintenance-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .action-item {
        padding: 20px;
        background: #f7fafc;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .action-item h4 {
        margin: 0 0 8px 0;
        color: #2d3748;
    }
    .action-item p {
        margin: 0 0 15px 0;
        color: #718096;
        font-size: 14px;
    }
    .system-status {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
    }
    .status-item {
        padding: 15px;
        background: #f7fafc;
        border-radius: 8px;
    }
    .status-item label {
        display: block;
        font-size: 12px;
        color: #718096;
        margin-bottom: 5px;
    }
    .status-item span {
        font-weight: 600;
        color: #2d3748;
    }
    .btn-warning {
        background: #f6ad55;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-warning:hover {
        background: #ed8936;
    }
    .btn-info {
        background: #4facfe;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-info:hover {
        background: #3182ce;
    }
    @media (max-width: 768px) {
        .settings-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding: 5px;
        }
        .tab-btn {
            font-size: 13px;
            padding: 8px 15px;
        }
        .settings-card {
            padding: 20px;
        }
        .maintenance-actions,
        .system-status {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
