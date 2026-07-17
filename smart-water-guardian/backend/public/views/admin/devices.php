<?php
/**
 * Device Management Page
 * Admin can view, register, and manage IoT devices
 */

// Check authentication and role
if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'admin') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'Device Management';
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
                <a href="?page=devices" class="nav-link active">
                    <i class="fas fa-microchip"></i> Devices
                </a>
                <a href="?page=users" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="?page=system_settings" class="nav-link">
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
                <!-- Device Stats -->
                <div class="stats-grid" id="deviceStats">
                    <div class="stat-card">
                        <div class="stat-icon">📡</div>
                        <div class="stat-info">
                            <div class="stat-value" id="totalDevices">0</div>
                            <div class="stat-label">Total Devices</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🟢</div>
                        <div class="stat-info">
                            <div class="stat-value" id="onlineDevices">0</div>
                            <div class="stat-label">Online</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔴</div>
                        <div class="stat-info">
                            <div class="stat-value" id="offlineDevices">0</div>
                            <div class="stat-label">Offline</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-info">
                            <div class="stat-value" id="errorDevices">0</div>
                            <div class="stat-label">Error</div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions-bar">
                    <button class="btn-primary" onclick="showRegisterDeviceModal()">
                        <i class="fas fa-plus"></i> Register New Device
                    </button>
                    <button class="btn-secondary" onclick="refreshDevices()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="deviceSearch" placeholder="Search devices..." oninput="filterDevices(this.value)">
                    </div>
                </div>

                <!-- Device Table -->
                <div class="table-container">
                    <table class="table" id="devicesTable">
                        <thead>
                            <tr>
                                <th>Device ID</th>
                                <th>Property</th>
                                <th>Model</th>
                                <th>Status</th>
                                <th>Battery</th>
                                <th>Signal</th>
                                <th>Last Reading</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="devicesTableBody">
                            <tr>
                                <td colspan="8" class="text-center">Loading devices...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-container">
                    <div class="pagination-info" id="paginationInfo">Showing 1-10 of 0</div>
                    <div class="pagination-controls">
                        <button onclick="changePage('prev')" id="prevPage" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="currentPage">1</span>
                        <button onclick="changePage('next')" id="nextPage">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Register Device Modal -->
    <div class="modal" id="registerDeviceModal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-microchip"></i> Register New Device</h3>
                <button class="modal-close" onclick="closeModal('registerDeviceModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="registerDeviceForm" onsubmit="registerDevice(event)">
                    <div class="form-group">
                        <label>Device ID <span class="required">*</span></label>
                        <input type="text" id="deviceId" placeholder="e.g., METER_001" required>
                        <small class="help-text">Unique identifier for the device</small>
                    </div>
                    <div class="form-group">
                        <label>Property <span class="required">*</span></label>
                        <select id="propertyId" required>
                            <option value="">Select Property</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <select id="deviceModel">
                            <option value="YF-S201">YF-S201</option>
                            <option value="YF-S401">YF-S401</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Firmware Version</label>
                        <input type="text" id="firmwareVersion" placeholder="1.0.0" value="1.0.0">
                    </div>
                    <div class="form-group">
                        <label>Install Date</label>
                        <input type="date" id="installDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('registerDeviceModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary" id="registerBtn">
                            <span id="registerText">Register Device</span>
                            <span id="registerSpinner" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Device Details Modal -->
    <div class="modal" id="deviceDetailsModal" style="display:none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Device Details</h3>
                <button class="modal-close" onclick="closeModal('deviceDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="deviceDetailsBody">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <script>
    // ============================================
    // DEVICE MANAGEMENT
    // ============================================
    let currentPage = 1;
    let pageSize = 10;
    let totalDevices = 0;
    let devices = [];
    let searchTerm = '';

    // Load devices on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadDevices();
        loadProperties();
        loadDeviceStats();
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            loadDevices(false);
            loadDeviceStats();
        }, 30000);
    });

    // ============================================
    // LOAD DEVICES
    // ============================================
    async function loadDevices(showLoading = true) {
        if (showLoading) {
            document.getElementById('devicesTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center">Loading devices...</td></tr>';
        }

        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/admin/devices.php?page=${currentPage}&limit=${pageSize}&search=${encodeURIComponent(searchTerm)}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                devices = data.devices || [];
                totalDevices = data.total || 0;
                renderDevices(devices);
                updatePagination();
            } else {
                showToast('Failed to load devices', 'danger');
            }
        } catch (error) {
            console.error('Load devices error:', error);
            showToast('Error loading devices', 'danger');
        }
    }

    // ============================================
    // RENDER DEVICES
    // ============================================
    function renderDevices(devices) {
        const tbody = document.getElementById('devicesTableBody');
        
        if (!devices || devices.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-microchip"></i>
                            <p>No devices found</p>
                            <button class="btn-primary btn-sm" onclick="showRegisterDeviceModal()">
                                <i class="fas fa-plus"></i> Register Device
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = devices.map(device => `
            <tr data-device-id="${device.id}">
                <td>
                    <strong>${device.meter_id}</strong>
                    <br>
                    <small class="text-muted">${device.id}</small>
                </td>
                <td>
                    ${device.property_address || 'Not Assigned'}
                    <br>
                    <small class="text-muted">${device.property_id || 'No Property'}</small>
                </td>
                <td>
                    ${device.model || 'Unknown'}
                    <br>
                    <small class="text-muted">v${device.firmware_version || '1.0.0'}</small>
                </td>
                <td>
                    <span class="status-badge ${device.status}">
                        <i class="fas fa-${getStatusIcon(device.status)}"></i>
                        ${device.status || 'unknown'}
                    </span>
                </td>
                <td>
                    <div class="battery-indicator">
                        <div class="battery-bar" style="width: ${device.battery_level || 0}%; 
                             background: ${getBatteryColor(device.battery_level)};">
                        </div>
                        <span class="battery-text">${device.battery_level || 0}%</span>
                    </div>
                </td>
                <td>
                    <div class="signal-indicator">
                        <i class="fas fa-signal" style="color: ${getSignalColor(device.signal_strength)}"></i>
                        ${device.signal_strength || 0}%
                    </div>
                </td>
                <td>
                    ${device.last_reading_time ? formatDate(device.last_reading_time) : 'Never'}
                    <br>
                    <small class="text-muted">${device.last_reading || 0} L</small>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick="viewDevice('${device.id}')" class="btn-sm btn-info" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editDevice('${device.id}')" class="btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteDevice('${device.id}')" class="btn-sm btn-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // ============================================
    // LOAD DEVICE STATS
    // ============================================
    async function loadDeviceStats() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/device_stats.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('totalDevices').textContent = data.stats.total || 0;
                document.getElementById('onlineDevices').textContent = data.stats.online || 0;
                document.getElementById('offlineDevices').textContent = data.stats.offline || 0;
                document.getElementById('errorDevices').textContent = data.stats.error || 0;
            }
        } catch (error) {
            console.error('Load stats error:', error);
        }
    }

    // ============================================
    // LOAD PROPERTIES FOR DROPDOWN
    // ============================================
    async function loadProperties() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/properties.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('propertyId');
                select.innerHTML = `
                    <option value="">Select Property</option>
                    ${data.properties.map(p => `
                        <option value="${p.id}">${p.address} (${p.property_type})</option>
                    `).join('')}
                `;
            }
        } catch (error) {
            console.error('Load properties error:', error);
        }
    }

    // ============================================
    // REGISTER DEVICE
    // ============================================
    async function registerDevice(event) {
        event.preventDefault();
        
        const deviceId = document.getElementById('deviceId').value.trim();
        const propertyId = document.getElementById('propertyId').value;
        const model = document.getElementById('deviceModel').value;
        const firmware = document.getElementById('firmwareVersion').value.trim();
        const installDate = document.getElementById('installDate').value;
        
        if (!deviceId || !propertyId) {
            showToast('Please fill in all required fields', 'warning');
            return;
        }
        
        // Show loading
        const btn = document.getElementById('registerBtn');
        const text = document.getElementById('registerText');
        const spinner = document.getElementById('registerSpinner');
        btn.disabled = true;
        text.textContent = 'Registering...';
        spinner.style.display = 'inline';
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/register_device.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    meter_id: deviceId,
                    property_id: propertyId,
                    model: model,
                    firmware_version: firmware,
                    install_date: installDate
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Device registered successfully!', 'success');
                closeModal('registerDeviceModal');
                loadDevices();
                loadDeviceStats();
                document.getElementById('registerDeviceForm').reset();
            } else {
                showToast(data.message || 'Failed to register device', 'danger');
            }
        } catch (error) {
            console.error('Register device error:', error);
            showToast('Error registering device', 'danger');
        } finally {
            btn.disabled = false;
            text.textContent = 'Register Device';
            spinner.style.display = 'none';
        }
    }

    // ============================================
    // VIEW DEVICE DETAILS
    // ============================================
    async function viewDevice(deviceId) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/admin/device_details.php?id=${deviceId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const device = data.device;
                const body = document.getElementById('deviceDetailsBody');
                
                body.innerHTML = `
                    <div class="device-details-grid">
                        <div class="detail-item">
                            <label>Device ID</label>
                            <span>${device.meter_id}</span>
                        </div>
                        <div class="detail-item">
                            <label>Model</label>
                            <span>${device.model || 'Unknown'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Firmware</label>
                            <span>v${device.firmware_version || '1.0.0'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span class="status-badge ${device.status}">${device.status}</span>
                        </div>
                        <div class="detail-item">
                            <label>Battery</label>
                            <span>${device.battery_level || 0}%</span>
                        </div>
                        <div class="detail-item">
                            <label>Signal</label>
                            <span>${device.signal_strength || 0}%</span>
                        </div>
                        <div class="detail-item">
                            <label>Property</label>
                            <span>${device.property_address || 'Not Assigned'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Install Date</label>
                            <span>${device.install_date || 'Not Set'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Last Reading</label>
                            <span>${device.last_reading || 0} L</span>
                        </div>
                        <div class="detail-item">
                            <label>Last Reading Time</label>
                            <span>${device.last_reading_time ? formatDate(device.last_reading_time) : 'Never'}</span>
                        </div>
                    </div>
                    <div class="device-usage-history">
                        <h4>Recent Readings</h4>
                        <div id="deviceReadings"></div>
                    </div>
                `;
                
                // Load readings history
                loadDeviceReadings(device.id);
                
                showModal('deviceDetailsModal');
            } else {
                showToast('Failed to load device details', 'danger');
            }
        } catch (error) {
            console.error('View device error:', error);
            showToast('Error loading device details', 'danger');
        }
    }

    // ============================================
    // LOAD DEVICE READINGS
    // ============================================
    async function loadDeviceReadings(meterId) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/admin/device_readings.php?meter_id=${meterId}&limit=10`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            const container = document.getElementById('deviceReadings');
            if (data.success && data.readings.length > 0) {
                container.innerHTML = `
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Flow Rate</th>
                                <th>Volume</th>
                                <th>Battery</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.readings.map(r => `
                                <tr>
                                    <td>${formatDate(r.reading_time)}</td>
                                    <td>${r.flow_rate} L/min</td>
                                    <td>${r.volume} L</td>
                                    <td>${r.battery_level}%</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                container.innerHTML = '<p class="text-muted">No readings available</p>';
            }
        } catch (error) {
            console.error('Load readings error:', error);
        }
    }

    // ============================================
    // EDIT DEVICE
    // ============================================
    function editDevice(deviceId) {
        // Find device in list
        const device = devices.find(d => d.id === deviceId);
        if (!device) {
            showToast('Device not found', 'danger');
            return;
        }
        
        // Populate and show edit modal
        // Similar to register modal but with pre-filled values
        showToast('Edit functionality coming soon', 'info');
    }

    // ============================================
    // DELETE DEVICE
    // ============================================
    async function deleteDevice(deviceId) {
        if (!confirm('Are you sure you want to delete this device? This action cannot be undone.')) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/admin/delete_device.php`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ device_id: deviceId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Device deleted successfully', 'success');
                loadDevices();
                loadDeviceStats();
            } else {
                showToast(data.message || 'Failed to delete device', 'danger');
            }
        } catch (error) {
            console.error('Delete device error:', error);
            showToast('Error deleting device', 'danger');
        }
    }

    // ============================================
    // FILTER DEVICES
    // ============================================
    function filterDevices(search) {
        searchTerm = search;
        currentPage = 1;
        loadDevices();
    }

    // ============================================
    // PAGINATION
    // ============================================
    function changePage(direction) {
        const totalPages = Math.ceil(totalDevices / pageSize);
        
        if (direction === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (direction === 'next' && currentPage < totalPages) {
            currentPage++;
        } else {
            return;
        }
        
        loadDevices();
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalDevices / pageSize);
        document.getElementById('paginationInfo').textContent = 
            `Showing ${((currentPage - 1) * pageSize) + 1}-${Math.min(currentPage * pageSize, totalDevices)} of ${totalDevices}`;
        document.getElementById('currentPage').textContent = currentPage;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }

    // ============================================
    // REFRESH
    // ============================================
    function refreshDevices() {
        loadDevices();
        loadDeviceStats();
        showToast('Refreshed', 'success');
    }

    // ============================================
    // MODAL HELPERS
    // ============================================
    function showRegisterDeviceModal() {
        showModal('registerDeviceModal');
    }

    function showModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function getStatusIcon(status) {
        const icons = {
            'online': 'circle',
            'offline': 'circle',
            'error': 'exclamation-circle',
            'maintenance': 'tools'
        };
        return icons[status] || 'circle';
    }

    function getBatteryColor(level) {
        if (level > 50) return '#48bb78';
        if (level > 25) return '#f6ad55';
        return '#fc8181';
    }

    function getSignalColor(level) {
        if (level > 60) return '#48bb78';
        if (level > 30) return '#f6ad55';
        return '#fc8181';
    }

    function formatDate(date) {
        return new Date(date).toLocaleString('en-ZA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showToast(message, type) {
        // Use global toast function from app.js
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
    .battery-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .battery-bar {
        height: 6px;
        border-radius: 3px;
        min-width: 30px;
        max-width: 50px;
        background: #48bb78;
        transition: width 0.3s;
    }
    .signal-indicator {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-info { background: #4facfe; color: white; }
    .btn-warning { background: #f6ad55; color: white; }
    .btn-danger { background: #fc8181; color: white; }
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-badge.online { background: #c6f6d5; color: #22543d; }
    .status-badge.offline { background: #fed7d7; color: #742a2a; }
    .status-badge.error { background: #feb2b2; color: #742a2a; }
    .status-badge.maintenance { background: #fefcbf; color: #744210; }
    .device-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    .detail-item {
        padding: 10px;
        background: #f7fafc;
        border-radius: 8px;
    }
    .detail-item label {
        display: block;
        font-size: 12px;
        color: #718096;
        margin-bottom: 3px;
    }
    .detail-item span {
        font-weight: 600;
        color: #2d3748;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
    }
    .empty-state i {
        font-size: 48px;
        color: #a0aec0;
        margin-bottom: 15px;
    }
    </style>
</body>
</html>
