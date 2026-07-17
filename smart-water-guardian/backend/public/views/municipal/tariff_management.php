<?php
/**
 * Tariff Management Page
 * Municipal can configure and manage water tariffs
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'municipal') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'Tariff Management';
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
            <a href="?page=consumer_management" class="nav-link">
                <i class="fas fa-users"></i> Consumer Management
            </a>
            <a href="?page=tariff_management" class="nav-link active">
                <i class="fas fa-coins"></i> Tariff Management
            </a>
            <a href="?page=demand_forecast" class="nav-link">
                <i class="fas fa-chart-line"></i> Demand Forecast
            </a>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-body">
                <!-- Current Tariffs -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-coins"></i> Current Tariffs</h3>
                        <button onclick="showAddTariffModal()" class="btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Tariff Tier
                        </button>
                    </div>
                    
                    <div class="tariff-table-container">
                        <table class="table" id="tariffsTable">
                            <thead>
                                <tr>
                                    <th>Tariff Name</th>
                                    <th>Tier</th>
                                    <th>Min (kL)</th>
                                    <th>Max (kL)</th>
                                    <th>Rate (per kL)</th>
                                    <th>Effective From</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tariffsTableBody">
                                <tr>
                                    <td colspan="8" class="text-center">Loading tariffs...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tariff Chart -->
                <div class="section-card">
                    <h3><i class="fas fa-chart-bar"></i> Tariff Structure Visualization</h3>
                    <div class="chart-container">
                        <canvas id="tariffChart"></canvas>
                    </div>
                </div>

                <!-- Tariff History -->
                <div class="section-card">
                    <h3><i class="fas fa-history"></i> Tariff History</h3>
                    <div class="table-responsive">
                        <table class="table" id="historyTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Tariff</th>
                                    <th>Changes</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <tr>
                                    <td colspan="5" class="text-center">Loading history...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Tariff Modal -->
    <div class="modal" id="tariffModal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-coins"></i> <span id="tariffModalTitle">Add Tariff Tier</span></h3>
                <button class="modal-close" onclick="closeModal('tariffModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="tariffForm" onsubmit="saveTariff(event)">
                    <input type="hidden" id="editTariffId">
                    
                    <div class="form-group">
                        <label>Tariff Name <span class="required">*</span></label>
                        <input type="text" id="tariffName" placeholder="e.g., Residential Tariff" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tier Number <span class="required">*</span></label>
                        <input type="number" id="tariffTier" placeholder="1" min="1" required>
                        <small class="help-text">Tier order (1, 2, 3, etc.)</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Minimum Usage (kL) <span class="required">*</span></label>
                            <input type="number" id="tariffMin" placeholder="0" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Maximum Usage (kL)</label>
                            <input type="number" id="tariffMax" placeholder="Leave empty for unlimited" step="0.01" min="0">
                            <small class="help-text">Leave empty for unlimited</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Rate per kL (ZAR) <span class="required">*</span></label>
                        <input type="number" id="tariffRate" placeholder="18.50" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Effective Date <span class="required">*</span></label>
                        <input type="date" id="tariffEffective" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('tariffModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary" id="tariffSaveBtn">
                            <span id="tariffSaveText">Add Tariff</span>
                            <span id="tariffSaveSpinner" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // ============================================
    // TARIFF MANAGEMENT
    // ============================================
    let tariffChart = null;
    let isEditing = false;

    document.addEventListener('DOMContentLoaded', function() {
        loadTariffs();
        loadTariffHistory();
    });

    // ============================================
    // LOAD TARIFFS
    // ============================================
    async function loadTariffs() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/tariffs.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                renderTariffs(data.tariffs);
                renderTariffChart(data.tariffs);
            } else {
                showToast('Failed to load tariffs', 'danger');
            }
        } catch (error) {
            console.error('Load tariffs error:', error);
            showToast('Error loading tariffs', 'danger');
        }
    }

    // ============================================
    // RENDER TARIFFS
    // ============================================
    function renderTariffs(tariffs) {
        const tbody = document.getElementById('tariffsTableBody');
        
        if (!tariffs || tariffs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-coins"></i>
                            <p>No tariffs configured</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = tariffs.map(tariff => `
            <tr>
                <td><strong>${tariff.name}</strong></td>
                <td>Tier ${tariff.tier}</td>
                <td>${tariff.min_usage}</td>
                <td>${tariff.max_usage || '∞'}</td>
                <td><strong>R${tariff.rate_per_kl.toFixed(2)}</strong></td>
                <td>${formatDate(tariff.effective_from)}</td>
                <td>
                    <span class="status-badge ${tariff.is_active ? 'active' : 'inactive'}">
                        ${tariff.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick="editTariff('${tariff.id}')" class="btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleTariffStatus('${tariff.id}')" class="btn-sm ${tariff.is_active ? 'btn-secondary' : 'btn-success'}" 
                                title="${tariff.is_active ? 'Deactivate' : 'Activate'}">
                            <i class="fas fa-${tariff.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button onclick="deleteTariff('${tariff.id}')" class="btn-sm btn-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // ============================================
    // RENDER TARIFF CHART
    // ============================================
    function renderTariffChart(tariffs) {
        const ctx = document.getElementById('tariffChart');
        if (!ctx) return;
        
        // Destroy existing chart
        if (tariffChart) {
            tariffChart.destroy();
        }
        
        const activeTariffs = tariffs.filter(t => t.is_active).sort((a, b) => a.tier - b.tier);
        
        if (activeTariffs.length === 0) {
            ctx.parentElement.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <p>No active tariffs to display</p>
                </div>
            `;
            return;
        }
        
        // Create chart data
        const labels = activeTariffs.map(t => `Tier ${t.tier}`);
        const rates = activeTariffs.map(t => t.rate_per_kl);
        const ranges = activeTariffs.map(t => 
            `${t.min_usage}${t.max_usage ? ` - ${t.max_usage}` : '+'} kL`
        );
        
        tariffChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Rate per kL (ZAR)',
                    data: rates,
                    backgroundColor: [
                        'rgba(72, 187, 120, 0.6)',
                        'rgba(102, 126, 234, 0.6)',
                        'rgba(118, 75, 162, 0.6)',
                        'rgba(246, 173, 85, 0.6)',
                        'rgba(252, 129, 129, 0.6)'
                    ],
                    borderColor: [
                        'rgba(72, 187, 120, 1)',
                        'rgba(102, 126, 234, 1)',
                        'rgba(118, 75, 162, 1)',
                        'rgba(246, 173, 85, 1)',
                        'rgba(252, 129, 129, 1)'
                    ],
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
                            afterBody: function(context) {
                                const index = context[0].dataIndex;
                                return `Range: ${ranges[index]}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Rate (ZAR per kL)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'R' + value.toFixed(2);
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tariff Tier'
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // LOAD TARIFF HISTORY
    // ============================================
    async function loadTariffHistory() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/tariff_history.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                renderHistory(data.history);
            }
        } catch (error) {
            console.error('Load history error:', error);
        }
    }

    function renderHistory(history) {
        const tbody = document.getElementById('historyTableBody');
        
        if (!history || history.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">No tariff history available</td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = history.map(h => `
            <tr>
                <td>${formatDate(h.created_at)}</td>
                <td><span class="action-badge ${h.action}">${h.action}</span></td>
                <td>${h.tariff_name}</td>
                <td>${h.changes}</td>
                <td>${h.user_name || 'System'}</td>
            </tr>
        `).join('');
    }

    // ============================================
    // ADD/EDIT TARIFF
    // ============================================
    function showAddTariffModal() {
        isEditing = false;
        document.getElementById('tariffModalTitle').textContent = 'Add Tariff Tier';
        document.getElementById('tariffSaveText').textContent = 'Add Tariff';
        document.getElementById('tariffForm').reset();
        document.getElementById('editTariffId').value = '';
        document.getElementById('tariffEffective').value = new Date().toISOString().split('T')[0];
        showModal('tariffModal');
    }

    function editTariff(tariffId) {
        // Find tariff in list
        fetch(`/api/modules/municipal/tariff.php?id=${tariffId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const t = data.tariff;
                isEditing = true;
                document.getElementById('tariffModalTitle').textContent = 'Edit Tariff';
                document.getElementById('tariffSaveText').textContent = 'Update Tariff';
                document.getElementById('editTariffId').value = t.id;
                document.getElementById('tariffName').value = t.name;
                document.getElementById('tariffTier').value = t.tier;
                document.getElementById('tariffMin').value = t.min_usage;
                document.getElementById('tariffMax').value = t.max_usage || '';
                document.getElementById('tariffRate').value = t.rate_per_kl;
                document.getElementById('tariffEffective').value = t.effective_from;
                showModal('tariffModal');
            } else {
                showToast('Failed to load tariff', 'danger');
            }
        })
        .catch(error => {
            console.error('Edit tariff error:', error);
            showToast('Error loading tariff', 'danger');
        });
    }

    // ============================================
    // SAVE TARIFF
    // ============================================
    async function saveTariff(event) {
        event.preventDefault();
        
        const tariffId = document.getElementById('editTariffId').value;
        const name = document.getElementById('tariffName').value.trim();
        const tier = parseInt(document.getElementById('tariffTier').value);
        const minUsage = parseFloat(document.getElementById('tariffMin').value);
        const maxUsage = document.getElementById('tariffMax').value ? 
            parseFloat(document.getElementById('tariffMax').value) : null;
        const rate = parseFloat(document.getElementById('tariffRate').value);
        const effectiveFrom = document.getElementById('tariffEffective').value;
        
        if (!name || !tier || isNaN(minUsage) || isNaN(rate) || !effectiveFrom) {
            showToast('Please fill in all required fields', 'warning');
            return;
        }
        
        // Show loading
        const btn = document.getElementById('tariffSaveBtn');
        const text = document.getElementById('tariffSaveText');
        const spinner = document.getElementById('tariffSaveSpinner');
        btn.disabled = true;
        text.textContent = isEditing ? 'Updating...' : 'Adding...';
        spinner.style.display = 'inline';
        
        try {
            const token = localStorage.getItem('token');
            const endpoint = isEditing ? '/api/modules/municipal/update_tariff.php' : '/api/modules/municipal/create_tariff.php';
            const payload = {
                name, tier, min_usage: minUsage, max_usage: maxUsage,
                rate_per_kl: rate, effective_from: effectiveFrom
            };
            if (tariffId) {
                payload.tariff_id = tariffId;
            }
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(isEditing ? 'Tariff updated successfully!' : 'Tariff added successfully!', 'success');
                closeModal('tariffModal');
                loadTariffs();
                loadTariffHistory();
            } else {
                showToast(data.message || 'Failed to save tariff', 'danger');
            }
        } catch (error) {
            console.error('Save tariff error:', error);
            showToast('Error saving tariff', 'danger');
        } finally {
            btn.disabled = false;
            text.textContent = isEditing ? 'Update Tariff' : 'Add Tariff';
            spinner.style.display = 'none';
        }
    }

    // ============================================
    // TOGGLE TARIFF STATUS
    // ============================================
    async function toggleTariffStatus(tariffId) {
        if (!confirm('Are you sure you want to change this tariff\'s status?')) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/toggle_tariff_status.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tariff_id: tariffId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Tariff status updated', 'success');
                loadTariffs();
            } else {
                showToast(data.message || 'Failed to update status', 'danger');
            }
        } catch (error) {
            console.error('Toggle tariff error:', error);
            showToast('Error updating tariff status', 'danger');
        }
    }

    // ============================================
    // DELETE TARIFF
    // ============================================
    async function deleteTariff(tariffId) {
        if (!confirm('Are you sure you want to delete this tariff? This action cannot be undone.')) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/delete_tariff.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tariff_id: tariffId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Tariff deleted successfully', 'success');
                loadTariffs();
                loadTariffHistory();
            } else {
                showToast(data.message || 'Failed to delete tariff', 'danger');
            }
        } catch (error) {
            console.error('Delete tariff error:', error);
            showToast('Error deleting tariff', 'danger');
        }
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function formatDate(date) {
        if (!date) return 'Never';
        return new Date(date).toLocaleString('en-ZA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
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
    .section-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .section-header h3 {
        margin: 0;
        color: #2d3748;
        font-size: 18px;
    }
    .section-header h3 i {
        color: #667eea;
        margin-right: 10px;
    }
    .btn-sm {
        padding: 6px 14px;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-sm:hover {
        transform: translateY(-1px);
    }
    .btn-primary.btn-sm {
        background: #667eea;
        color: white;
    }
    .btn-primary.btn-sm:hover {
        background: #5a67d8;
    }
    .btn-sm.btn-warning { background: #f6ad55; color: white; }
    .btn-sm.btn-warning:hover { background: #ed8936; }
    .btn-sm.btn-success { background: #48bb78; color: white; }
    .btn-sm.btn-success:hover { background: #38a169; }
    .btn-sm.btn-secondary { background: #e2e8f0; color: #2d3748; }
    .btn-sm.btn-secondary:hover { background: #cbd5e0; }
    .btn-sm.btn-danger { background: #fc8181; color: white; }
    .btn-sm.btn-danger:hover { background: #e53e3e; }
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .action-badge {
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
    }
    .action-badge.create { background: #c6f6d5; color: #22543d; }
    .action-badge.update { background: #bee3f8; color: #2b6cb0; }
    .action-badge.delete { background: #fed7d7; color: #742a2a; }
    .action-badge.toggle { background: #fefcbf; color: #744210; }
    .chart-container {
        height: 300px;
        position: relative;
    }
    .table-container {
        overflow-x: auto;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-badge.active { background: #c6f6d5; color: #22543d; }
    .status-badge.inactive { background: #fed7d7; color: #742a2a; }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        color: #4a5568;
        font-size: 14px;
        margin-bottom: 5px;
    }
    .form-group .required {
        color: #e53e3e;
    }
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    .form-group input:focus,
    .form-group select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        outline: none;
    }
    .form-group .help-text {
        display: block;
        font-size: 12px;
        color: #718096;
        margin-top: 3px;
    }
    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
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
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #718096;
    }
    .empty-state i {
        font-size: 32px;
        color: #a0aec0;
        margin-bottom: 10px;
    }
    .text-center { text-align: center; }
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .section-header {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        .action-buttons {
            flex-wrap: wrap;
        }
    }
    </style>
</body>
</html>
