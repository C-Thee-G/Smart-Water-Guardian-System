<?php
/**
 * Consumer Management Page
 * Municipal can view, manage, and search consumers
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'municipal') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'Consumer Management';
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
            <a href="?page=nrw_report" class="nav-link">
                <i class="fas fa-file-alt"></i> NRW Reports
            </a>
            <a href="?page=consumer_management" class="nav-link active">
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
                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="consumerSearch" placeholder="Search by name, email, or address..." 
                               oninput="searchConsumers(this.value)">
                    </div>
                    <div class="filter-group">
                        <select id="statusFilter" onchange="filterConsumers()">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select id="typeFilter" onchange="filterConsumers()">
                            <option value="all">All Types</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option>
                        </select>
                        <button onclick="exportConsumerData()" class="btn-secondary">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-mini">
                        <span class="stat-number" id="totalConsumers">0</span>
                        <span class="stat-label">Total Consumers</span>
                    </div>
                    <div class="stat-mini">
                        <span class="stat-number" id="activeConsumers">0</span>
                        <span class="stat-label">Active</span>
                    </div>
                    <div class="stat-mini">
                        <span class="stat-number" id="inactiveConsumers">0</span>
                        <span class="stat-label">Inactive</span>
                    </div>
                    <div class="stat-mini">
                        <span class="stat-number" id="totalProperties">0</span>
                        <span class="stat-label">Properties</span>
                    </div>
                </div>

                <!-- Consumer Table -->
                <div class="table-container">
                    <table class="table" id="consumersTable">
                        <thead>
                            <tr>
                                <th>Consumer</th>
                                <th>Contact</th>
                                <th>Properties</th>
                                <th>Meter</th>
                                <th>Usage (Month)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="consumersTableBody">
                            <tr>
                                <td colspan="7" class="text-center">Loading consumers...</td>
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

    <!-- Consumer Details Modal -->
    <div class="modal" id="consumerDetailsModal" style="display:none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> Consumer Details</h3>
                <button class="modal-close" onclick="closeModal('consumerDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="consumerDetailsBody">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <script>
    // ============================================
    // CONSUMER MANAGEMENT
    // ============================================
    let currentPage = 1;
    let pageSize = 10;
    let totalConsumers = 0;
    let consumers = [];
    let searchTerm = '';
    let statusFilter = 'all';
    let typeFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        loadConsumers();
        loadConsumerStats();
    });

    // ============================================
    // LOAD CONSUMERS
    // ============================================
    async function loadConsumers() {
        try {
            const token = localStorage.getItem('token');
            const params = new URLSearchParams({
                page: currentPage,
                limit: pageSize,
                search: searchTerm,
                status: statusFilter,
                type: typeFilter
            });
            
            const response = await fetch(`/api/modules/municipal/consumers.php?${params}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                consumers = data.consumers || [];
                totalConsumers = data.total || 0;
                renderConsumers(consumers);
                updatePagination();
            } else {
                showToast('Failed to load consumers', 'danger');
            }
        } catch (error) {
            console.error('Load consumers error:', error);
            showToast('Error loading consumers', 'danger');
        }
    }

    // ============================================
    // RENDER CONSUMERS
    // ============================================
    function renderConsumers(consumers) {
        const tbody = document.getElementById('consumersTableBody');
        
        if (!consumers || consumers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No consumers found</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = consumers.map(consumer => `
            <tr>
                <td>
                    <div class="consumer-info">
                        <div class="avatar" style="background: ${getAvatarColor(consumer.id)}">
                            ${(consumer.name.charAt(0) + consumer.surname.charAt(0)).toUpperCase()}
                        </div>
                        <div>
                            <strong>${consumer.name} ${consumer.surname}</strong>
                            <br>
                            <small class="text-muted">ID: ${consumer.id}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>${consumer.email}</div>
                    <small class="text-muted">${consumer.phone}</small>
                </td>
                <td>
                    ${consumer.property_count || 0}
                    <br>
                    <small class="text-muted">${consumer.property_type || 'N/A'}</small>
                </td>
                <td>
                    ${consumer.meter_id || 'No meter'}
                    <br>
                    <small class="text-muted">${consumer.meter_status || 'Not installed'}</small>
                </td>
                <td>
                    ${consumer.monthly_usage ? consumer.monthly_usage.toFixed(1) : '0'} L
                    <br>
                    <small class="text-muted">${consumer.monthly_bill ? formatCurrency(consumer.monthly_bill) : 'N/A'}</small>
                </td>
                <td>
                    <span class="status-badge ${consumer.is_active ? 'active' : 'inactive'}">
                        ${consumer.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick="viewConsumer('${consumer.id}')" class="btn-sm btn-info" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editConsumer('${consumer.id}')" class="btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleConsumerStatus('${consumer.id}')" class="btn-sm ${consumer.is_active ? 'btn-secondary' : 'btn-success'}" 
                                title="${consumer.is_active ? 'Deactivate' : 'Activate'}">
                            <i class="fas fa-${consumer.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button onclick="viewConsumerUsage('${consumer.id}')" class="btn-sm btn-primary" title="View Usage">
                            <i class="fas fa-chart-line"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // ============================================
    // LOAD CONSUMER STATS
    // ============================================
    async function loadConsumerStats() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/consumer_stats.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('totalConsumers').textContent = data.stats.total || 0;
                document.getElementById('activeConsumers').textContent = data.stats.active || 0;
                document.getElementById('inactiveConsumers').textContent = data.stats.inactive || 0;
                document.getElementById('totalProperties').textContent = data.stats.properties || 0;
            }
        } catch (error) {
            console.error('Load stats error:', error);
        }
    }

    // ============================================
    // SEARCH AND FILTER
    // ============================================
    function searchConsumers(search) {
        searchTerm = search;
        currentPage = 1;
        loadConsumers();
    }

    function filterConsumers() {
        statusFilter = document.getElementById('statusFilter').value;
        typeFilter = document.getElementById('typeFilter').value;
        currentPage = 1;
        loadConsumers();
    }

    // ============================================
    // PAGINATION
    // ============================================
    function changePage(direction) {
        const totalPages = Math.ceil(totalConsumers / pageSize);
        
        if (direction === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (direction === 'next' && currentPage < totalPages) {
            currentPage++;
        } else {
            return;
        }
        
        loadConsumers();
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalConsumers / pageSize);
        const start = ((currentPage - 1) * pageSize) + 1;
        const end = Math.min(currentPage * pageSize, totalConsumers);
        document.getElementById('paginationInfo').textContent = 
            `Showing ${start}-${end} of ${totalConsumers}`;
        document.getElementById('currentPage').textContent = currentPage;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }

    // ============================================
    // VIEW CONSUMER
    // ============================================
    async function viewConsumer(consumerId) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/municipal/consumer_details.php?id=${consumerId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const consumer = data.consumer;
                const body = document.getElementById('consumerDetailsBody');
                
                body.innerHTML = `
                    <div class="consumer-profile">
                        <div class="profile-avatar" style="background: ${getAvatarColor(consumer.id)}">
                            ${(consumer.name.charAt(0) + consumer.surname.charAt(0)).toUpperCase()}
                        </div>
                        <div class="profile-info">
                            <h3>${consumer.name} ${consumer.surname}</h3>
                            <p><i class="fas fa-envelope"></i> ${consumer.email}</p>
                            <p><i class="fas fa-phone"></i> ${consumer.phone}</p>
                            <p><i class="fas fa-address-card"></i> ${consumer.address || 'No address'}</p>
                        </div>
                        <div class="profile-status">
                            <span class="status-badge ${consumer.is_active ? 'active' : 'inactive'}">
                                ${consumer.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                    
                    <div class="consumer-stats-grid">
                        <div class="stat-mini-card">
                            <span class="number">${consumer.property_count || 0}</span>
                            <span class="label">Properties</span>
                        </div>
                        <div class="stat-mini-card">
                            <span class="number">${consumer.meter_count || 0}</span>
                            <span class="label">Meters</span>
                        </div>
                        <div class="stat-mini-card">
                            <span class="number">${consumer.monthly_usage ? consumer.monthly_usage.toFixed(1) : '0'}</span>
                            <span class="label">Monthly Usage (L)</span>
                        </div>
                        <div class="stat-mini-card highlight">
                            <span class="number">${consumer.monthly_bill ? formatCurrency(consumer.monthly_bill) : 'R0.00'}</span>
                            <span class="label">Monthly Bill</span>
                        </div>
                    </div>
                    
                    ${consumer.properties ? `
                        <div class="consumer-properties">
                            <h4>Properties</h4>
                            <div class="property-list">
                                ${consumer.properties.map(p => `
                                    <div class="property-item">
                                        <div class="property-address">
                                            <i class="fas fa-home"></i>
                                            ${p.address}
                                        </div>
                                        <div class="property-details">
                                            <span class="property-type ${p.property_type}">${p.property_type}</span>
                                            <span class="property-meter">${p.meter_id || 'No meter'}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${consumer.alerts && consumer.alerts.length > 0 ? `
                        <div class="consumer-alerts">
                            <h4>Recent Alerts</h4>
                            ${consumer.alerts.slice(0, 5).map(a => `
                                <div class="alert-item alert-${a.severity}">
                                    <span class="alert-icon">
                                        <i class="fas fa-${getAlertIcon(a.alert_type)}"></i>
                                    </span>
                                    <span class="alert-message">${a.message}</span>
                                    <span class="alert-time">${formatDate(a.created_at)}</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                `;
                
                showModal('consumerDetailsModal');
            } else {
                showToast('Failed to load consumer details', 'danger');
            }
        } catch (error) {
            console.error('View consumer error:', error);
            showToast('Error loading consumer details', 'danger');
        }
    }

    // ============================================
    // VIEW CONSUMER USAGE
    // ============================================
    function viewConsumerUsage(consumerId) {
        // Navigate to usage history for this consumer
        window.location.href = `?page=usage_history&consumer=${consumerId}`;
    }

    // ============================================
    // EDIT CONSUMER
    // ============================================
    function editConsumer(consumerId) {
        showToast('Edit functionality coming soon', 'info');
    }

    // ============================================
    // TOGGLE CONSUMER STATUS
    // ============================================
    async function toggleConsumerStatus(consumerId) {
        const consumer = consumers.find(c => c.id === consumerId);
        if (!consumer) return;
        
        const action = consumer.is_active ? 'deactivate' : 'activate';
        if (!confirm(`Are you sure you want to ${action} this consumer?`)) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/toggle_consumer_status.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    consumer_id: consumerId,
                    is_active: !consumer.is_active
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(`Consumer ${action}d successfully`, 'success');
                loadConsumers();
                loadConsumerStats();
            } else {
                showToast(data.message || 'Failed to toggle status', 'danger');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showToast('Error toggling status', 'danger');
        }
    }

    // ============================================
    // EXPORT DATA
    // ============================================
    function exportConsumerData() {
        showToast('Preparing export...', 'info');
        // Implement CSV export
        setTimeout(() => {
            showToast('Export feature coming soon', 'info');
        }, 2000);
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function getAvatarColor(id) {
        const colors = ['#667eea', '#764ba2', '#48bb78', '#f6ad55', '#fc8181', '#4facfe', '#a18cd1', '#f093fb'];
        const index = parseInt(id.substr(-2) || '0') % colors.length;
        return colors[index];
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
    .search-filters {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .search-bar {
        flex: 1;
        min-width: 200px;
        display: flex;
        align-items: center;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 15px;
        transition: all 0.3s;
    }
    .search-bar:focus-within {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }
    .search-bar i {
        color: #a0aec0;
    }
    .search-bar input {
        border: none;
        padding: 12px 15px;
        flex: 1;
        font-size: 14px;
        background: transparent;
        outline: none;
    }
    .filter-group {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .filter-group select {
        padding: 10px 35px 10px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a5568' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }
    .filter-group select:focus {
        border-color: #667eea;
        outline: none;
    }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-mini {
        background: white;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .stat-mini .stat-number {
        display: block;
        font-size: 22px;
        font-weight: bold;
        color: #2d3748;
    }
    .stat-mini .stat-label {
        font-size: 12px;
        color: #718096;
    }
    .consumer-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .consumer-info .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
    }
    .consumer-profile {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: #f7fafc;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .profile-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 24px;
    }
    .profile-info {
        flex: 1;
    }
    .profile-info h3 {
        margin: 0 0 5px 0;
        color: #2d3748;
    }
    .profile-info p {
        margin: 3px 0;
        color: #4a5568;
        font-size: 14px;
    }
    .profile-info p i {
        width: 20px;
        color: #667eea;
    }
    .profile-status {
        text-align: center;
    }
    .consumer-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-mini-card {
        background: #f7fafc;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
    }
    .stat-mini-card .number {
        display: block;
        font-size: 20px;
        font-weight: bold;
        color: #2d3748;
    }
    .stat-mini-card .label {
        font-size: 12px;
        color: #718096;
    }
    .stat-mini-card.highlight {
        background: #ebf4ff;
        border: 2px solid #667eea;
    }
    .stat-mini-card.highlight .number {
        color: #667eea;
    }
    .property-list {
        display: grid;
        gap: 10px;
        margin-top: 10px;
    }
    .property-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        background: #f7fafc;
        border-radius: 8px;
    }
    .property-address {
        font-weight: 500;
        color: #2d3748;
    }
    .property-address i {
        color: #667eea;
        margin-right: 8px;
    }
    .property-details {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .property-type {
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
    }
    .property-type.residential { background: #bee3f8; color: #2b6cb0; }
    .property-type.commercial { background: #c6f6d5; color: #22543d; }
    .property-type.industrial { background: #fefcbf; color: #744210; }
    .property-meter {
        font-size: 12px;
        color: #718096;
    }
    .consumer-alerts {
        margin-top: 20px;
    }
    .consumer-alerts h4 {
        color: #2d3748;
        margin-bottom: 10px;
    }
    .alert-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px 15px;
        background: #f7fafc;
        border-radius: 6px;
        margin-bottom: 5px;
    }
    .alert-item .alert-icon {
        font-size: 18px;
    }
    .alert-item .alert-message {
        flex: 1;
        color: #2d3748;
        font-size: 14px;
    }
    .alert-item .alert-time {
        font-size: 12px;
        color: #718096;
    }
    .alert-item.alert-critical { border-left: 4px solid #fc8181; }
    .alert-item.alert-high { border-left: 4px solid #f6ad55; }
    .alert-item.alert-warning { border-left: 4px solid #fefcbf; }
    .btn-sm {
        padding: 4px 10px;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-sm:hover {
        transform: translateY(-1px);
    }
    .btn-sm.btn-info { background: #4facfe; color: white; }
    .btn-sm.btn-warning { background: #f6ad55; color: white; }
    .btn-sm.btn-primary { background: #667eea; color: white; }
    .btn-sm.btn-secondary { background: #e2e8f0; color: #2d3748; }
    .btn-sm.btn-success { background: #48bb78; color: white; }
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-badge.active { background: #c6f6d5; color: #22543d; }
    .status-badge.inactive { background: #fed7d7; color: #742a2a; }
    .text-center { text-align: center; }
    .text-muted { color: #718096; }
    .empty-state {
        text-align: center;
        padding: 40px;
    }
    .empty-state i {
        font-size: 48px;
        color: #a0aec0;
        margin-bottom: 10px;
    }
    @media (max-width: 768px) {
        .search-filters {
            flex-direction: column;
        }
        .filter-group {
            flex-wrap: wrap;
        }
        .filter-group select {
            flex: 1;
            min-width: 120px;
        }
        .consumer-profile {
            flex-direction: column;
            text-align: center;
        }
        .property-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        .action-buttons {
            flex-wrap: wrap;
        }
    }
    </style>
</body>
</html>
