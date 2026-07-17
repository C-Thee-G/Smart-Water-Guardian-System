<?php
/**
 * User Management Page
 * Admin can view, manage, and assign roles to users
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'admin') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'User Management';
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
                <a href="?page=users" class="nav-link active">
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
                <!-- User Stats -->
                <div class="stats-grid" id="userStats">
                    <div class="stat-card">
                        <div class="stat-icon">👤</div>
                        <div class="stat-info">
                            <div class="stat-value" id="totalUsers">0</div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏠</div>
                        <div class="stat-info">
                            <div class="stat-value" id="totalConsumers">0</div>
                            <div class="stat-label">Consumers</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏛️</div>
                        <div class="stat-info">
                            <div class="stat-value" id="totalMunicipal">0</div>
                            <div class="stat-label">Municipal</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔧</div>
                        <div class="stat-info">
                            <div class="stat-value" id="totalTechnicians">0</div>
                            <div class="stat-label">Technicians</div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions-bar">
                    <button class="btn-primary" onclick="showAddUserModal()">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                    <button class="btn-secondary" onclick="refreshUsers()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search users..." oninput="filterUsers(this.value)">
                    </div>
                    <div class="filter-group">
                        <select id="roleFilter" onchange="filterUsers()">
                            <option value="">All Roles</option>
                            <option value="consumer">Consumer</option>
                            <option value="municipal">Municipal</option>
                            <option value="technician">Technician</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <!-- User Table -->
                <div class="table-container">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="text-center">Loading users...</td>
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

    <!-- Add/Edit User Modal -->
    <div class="modal" id="userModal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> <span id="userModalTitle">Add User</span></h3>
                <button class="modal-close" onclick="closeModal('userModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm" onsubmit="saveUser(event)">
                    <input type="hidden" id="editUserId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name <span class="required">*</span></label>
                            <input type="text" id="userName" placeholder="First name" required>
                        </div>
                        <div class="form-group">
                            <label>Surname <span class="required">*</span></label>
                            <input type="text" id="userSurname" placeholder="Last name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" id="userEmail" placeholder="Email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone <span class="required">*</span></label>
                        <input type="tel" id="userPhone" placeholder="Phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select id="userRole" required>
                            <option value="consumer">Consumer</option>
                            <option value="municipal">Municipal</option>
                            <option value="technician">Technician</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label>Password <span class="required" id="passwordRequired">*</span></label>
                        <input type="password" id="userPassword" placeholder="Enter password" minlength="8">
                        <small class="help-text">Minimum 8 characters with uppercase, lowercase, number, and special character</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea id="userAddress" rows="2" placeholder="Physical address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select id="userStatus">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('userModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary" id="userSaveBtn">
                            <span id="userSaveText">Create User</span>
                            <span id="userSaveSpinner" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div class="modal" id="userDetailsModal" style="display:none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> User Details</h3>
                <button class="modal-close" onclick="closeModal('userDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="userDetailsBody">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <script>
    // ============================================
    // USER MANAGEMENT
    // ============================================
    let currentPage = 1;
    let pageSize = 10;
    let totalUsers = 0;
    let users = [];
    let searchTerm = '';
    let roleFilter = '';
    let isEditing = false;

    document.addEventListener('DOMContentLoaded', function() {
        loadUsers();
        loadUserStats();
        
        setInterval(() => {
            loadUsers(false);
            loadUserStats();
        }, 30000);
    });

    // ============================================
    // LOAD USERS
    // ============================================
    async function loadUsers(showLoading = true) {
        if (showLoading) {
            document.getElementById('usersTableBody').innerHTML = 
                '<tr><td colspan="7" class="text-center">Loading users...</td></tr>';
        }

        try {
            const token = localStorage.getItem('token');
            const params = new URLSearchParams({
                page: currentPage,
                limit: pageSize,
                search: searchTerm,
                role: roleFilter
            });
            
            const response = await fetch(`/api/modules/admin/users.php?${params}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                users = data.users || [];
                totalUsers = data.total || 0;
                renderUsers(users);
                updatePagination();
            } else {
                showToast('Failed to load users', 'danger');
            }
        } catch (error) {
            console.error('Load users error:', error);
            showToast('Error loading users', 'danger');
        }
    }

    // ============================================
    // RENDER USERS
    // ============================================
    function renderUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        
        if (!users || users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No users found</p>
                            <button class="btn-primary btn-sm" onclick="showAddUserModal()">
                                <i class="fas fa-user-plus"></i> Add User
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>
                    <div class="user-avatar">
                        <span>${(user.name.charAt(0) + user.surname.charAt(0)).toUpperCase()}</span>
                    </div>
                    <div class="user-name-cell">
                        <strong>${user.name} ${user.surname}</strong>
                        <br>
                        <small class="text-muted">ID: ${user.id}</small>
                    </div>
                </td>
                <td>${user.email}</td>
                <td>${user.phone}</td>
                <td>
                    <span class="role-badge ${user.role}">
                        <i class="fas fa-${getRoleIcon(user.role)}"></i>
                        ${user.role}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${user.is_active ? 'active' : 'inactive'}">
                        ${user.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>${formatDate(user.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button onclick="viewUser('${user.id}')" class="btn-sm btn-info" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editUser('${user.id}')" class="btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleUserStatus('${user.id}')" class="btn-sm ${user.is_active ? 'btn-secondary' : 'btn-success'}" 
                                title="${user.is_active ? 'Deactivate' : 'Activate'}">
                            <i class="fas fa-${user.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button onclick="deleteUser('${user.id}')" class="btn-sm btn-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // ============================================
    // LOAD USER STATS
    // ============================================
    async function loadUserStats() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/user_stats.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('totalUsers').textContent = data.stats.total || 0;
                document.getElementById('totalConsumers').textContent = data.stats.consumer || 0;
                document.getElementById('totalMunicipal').textContent = data.stats.municipal || 0;
                document.getElementById('totalTechnicians').textContent = data.stats.technician || 0;
            }
        } catch (error) {
            console.error('Load stats error:', error);
        }
    }

    // ============================================
    // FILTER USERS
    // ============================================
    function filterUsers(search) {
        if (search !== undefined) {
            searchTerm = search;
        }
        roleFilter = document.getElementById('roleFilter').value;
        currentPage = 1;
        loadUsers();
    }

    // ============================================
    // PAGINATION
    // ============================================
    function changePage(direction) {
        const totalPages = Math.ceil(totalUsers / pageSize);
        
        if (direction === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (direction === 'next' && currentPage < totalPages) {
            currentPage++;
        } else {
            return;
        }
        
        loadUsers();
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalUsers / pageSize);
        const start = ((currentPage - 1) * pageSize) + 1;
        const end = Math.min(currentPage * pageSize, totalUsers);
        document.getElementById('paginationInfo').textContent = 
            `Showing ${start}-${end} of ${totalUsers}`;
        document.getElementById('currentPage').textContent = currentPage;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }

    // ============================================
    // ADD/EDIT USER
    // ============================================
    function showAddUserModal() {
        isEditing = false;
        document.getElementById('userModalTitle').textContent = 'Add User';
        document.getElementById('userSaveText').textContent = 'Create User';
        document.getElementById('userForm').reset();
        document.getElementById('editUserId').value = '';
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('userPassword').required = true;
        document.getElementById('userStatus').value = '1';
        showModal('userModal');
    }

    function editUser(userId) {
        const user = users.find(u => u.id === userId);
        if (!user) {
            showToast('User not found', 'danger');
            return;
        }
        
        isEditing = true;
        document.getElementById('userModalTitle').textContent = 'Edit User';
        document.getElementById('userSaveText').textContent = 'Update User';
        document.getElementById('editUserId').value = user.id;
        document.getElementById('userName').value = user.name;
        document.getElementById('userSurname').value = user.surname;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userPhone').value = user.phone;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userAddress').value = user.address || '';
        document.getElementById('userStatus').value = user.is_active ? '1' : '0';
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('passwordRequired').style.display = 'none';
        document.getElementById('userPassword').required = false;
        document.getElementById('userPassword').placeholder = 'Leave blank to keep current password';
        
        showModal('userModal');
    }

    // ============================================
    // SAVE USER
    // ============================================
    async function saveUser(event) {
        event.preventDefault();
        
        const userId = document.getElementById('editUserId').value;
        const name = document.getElementById('userName').value.trim();
        const surname = document.getElementById('userSurname').value.trim();
        const email = document.getElementById('userEmail').value.trim();
        const phone = document.getElementById('userPhone').value.trim();
        const role = document.getElementById('userRole').value;
        const password = document.getElementById('userPassword').value;
        const address = document.getElementById('userAddress').value.trim();
        const isActive = document.getElementById('userStatus').value === '1';
        
        // Validate
        if (!name || !surname || !email || !phone) {
            showToast('Please fill in all required fields', 'warning');
            return;
        }
        
        if (!userId && password.length < 8) {
            showToast('Password must be at least 8 characters', 'warning');
            return;
        }
        
        // Show loading
        const btn = document.getElementById('userSaveBtn');
        const text = document.getElementById('userSaveText');
        const spinner = document.getElementById('userSaveSpinner');
        btn.disabled = true;
        text.textContent = isEditing ? 'Updating...' : 'Creating...';
        spinner.style.display = 'inline';
        
        try {
            const token = localStorage.getItem('token');
            const endpoint = isEditing ? '/api/modules/admin/update_user.php' : '/api/modules/admin/create_user.php';
            const payload = {
                name, surname, email, phone, role, address, is_active
            };
            
            if (userId) {
                payload.user_id = userId;
            }
            if (password) {
                payload.password = password;
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
                showToast(isEditing ? 'User updated successfully!' : 'User created successfully!', 'success');
                closeModal('userModal');
                loadUsers();
                loadUserStats();
            } else {
                showToast(data.message || 'Failed to save user', 'danger');
            }
        } catch (error) {
            console.error('Save user error:', error);
            showToast('Error saving user', 'danger');
        } finally {
            btn.disabled = false;
            text.textContent = isEditing ? 'Update User' : 'Create User';
            spinner.style.display = 'none';
        }
    }

    // ============================================
    // VIEW USER
    // ============================================
    async function viewUser(userId) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/api/modules/admin/user_details.php?id=${userId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const user = data.user;
                const body = document.getElementById('userDetailsBody');
                
                body.innerHTML = `
                    <div class="user-profile">
                        <div class="profile-avatar" style="background: ${getAvatarColor(user.id)}">
                            ${(user.name.charAt(0) + user.surname.charAt(0)).toUpperCase()}
                        </div>
                        <div class="profile-info">
                            <h3>${user.name} ${user.surname}</h3>
                            <p><i class="fas fa-envelope"></i> ${user.email}</p>
                            <p><i class="fas fa-phone"></i> ${user.phone}</p>
                            <p><i class="fas fa-address-card"></i> ${user.address || 'No address'}</p>
                        </div>
                    </div>
                    <div class="user-details-grid">
                        <div class="detail-item">
                            <label>Role</label>
                            <span class="role-badge ${user.role}">${user.role}</span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span class="status-badge ${user.is_active ? 'active' : 'inactive'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label>Joined</label>
                            <span>${formatDate(user.created_at)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Properties</label>
                            <span>${user.property_count || 0}</span>
                        </div>
                        <div class="detail-item">
                            <label>Meters</label>
                            <span>${user.meter_count || 0}</span>
                        </div>
                        <div class="detail-item">
                            <label>Alerts</label>
                            <span>${user.alert_count || 0}</span>
                        </div>
                    </div>
                    ${user.properties ? `
                        <div class="user-properties">
                            <h4>Properties</h4>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Address</th>
                                        <th>Type</th>
                                        <th>Meter</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${user.properties.map(p => `
                                        <tr>
                                            <td>${p.address}</td>
                                            <td>${p.property_type}</td>
                                            <td>${p.meter_id || 'Not assigned'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : ''}
                `;
                
                showModal('userDetailsModal');
            } else {
                showToast('Failed to load user details', 'danger');
            }
        } catch (error) {
            console.error('View user error:', error);
            showToast('Error loading user details', 'danger');
        }
    }

    // ============================================
    // TOGGLE USER STATUS
    // ============================================
    async function toggleUserStatus(userId) {
        const user = users.find(u => u.id === userId);
        if (!user) return;
        
        const action = user.is_active ? 'deactivate' : 'activate';
        if (!confirm(`Are you sure you want to ${action} this user?`)) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/toggle_user_status.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    is_active: !user.is_active
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(`User ${action}d successfully`, 'success');
                loadUsers();
                loadUserStats();
            } else {
                showToast(data.message || 'Failed to toggle user status', 'danger');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showToast('Error toggling user status', 'danger');
        }
    }

    // ============================================
    // DELETE USER
    // ============================================
    async function deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/admin/delete_user.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('User deleted successfully', 'success');
                loadUsers();
                loadUserStats();
            } else {
                showToast(data.message || 'Failed to delete user', 'danger');
            }
        } catch (error) {
            console.error('Delete user error:', error);
            showToast('Error deleting user', 'danger');
        }
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function getRoleIcon(role) {
        const icons = {
            'consumer': 'home',
            'municipal': 'city',
            'technician': 'tools',
            'admin': 'crown'
        };
        return icons[role] || 'user';
    }

    function getAvatarColor(userId) {
        const colors = ['#667eea', '#764ba2', '#48bb78', '#f6ad55', '#fc8181', '#4facfe'];
        const index = parseInt(userId.substr(-2) || '0') % colors.length;
        return colors[index];
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

    function refreshUsers() {
        loadUsers();
        loadUserStats();
        showToast('Refreshed', 'success');
    }

    function showModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
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
    .user-avatar {
        display: inline-block;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #667eea;
        color: white;
        text-align: center;
        line-height: 40px;
        font-weight: bold;
        font-size: 16px;
        margin-right: 10px;
        vertical-align: middle;
    }
    .user-name-cell {
        display: inline-block;
        vertical-align: middle;
    }
    .role-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .role-badge.consumer { background: #bee3f8; color: #2b6cb0; }
    .role-badge.municipal { background: #c6f6d5; color: #22543d; }
    .role-badge.technician { background: #fefcbf; color: #744210; }
    .role-badge.admin { background: #fed7d7; color: #742a2a; }
    .user-profile {
        display: flex;
        gap: 20px;
        padding: 20px;
        background: #f7fafc;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: bold;
        color: white;
    }
    .profile-info h3 {
        margin: 0 0 10px 0;
        color: #2d3748;
    }
    .profile-info p {
        margin: 5px 0;
        color: #4a5568;
    }
    .profile-info p i {
        width: 20px;
        color: #667eea;
    }
    .user-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    .user-properties {
        margin-top: 20px;
    }
    .user-properties h4 {
        margin-bottom: 10px;
        color: #2d3748;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .btn-success { background: #48bb78; color: white; }
    .btn-success:hover { background: #38a169; }
    </style>
</body>
</html>
