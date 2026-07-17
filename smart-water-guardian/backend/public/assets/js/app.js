/**
 * Smart Water Guardian - Main Application Controller
 * Handles global app state, navigation, and common utilities
 */

// ============================================
// APPLICATION CONFIGURATION
// ============================================
const APP = {
    name: 'Smart Water Guardian',
    version: '1.0.0',
    apiBase: '/api/modules',
    tokenKey: 'token',
    userKey: 'user',
    refreshInterval: 60000, // 60 seconds
    maxRetries: 3,
    retryDelay: 1000
};

// ============================================
// STATE MANAGEMENT
// ============================================
const AppState = {
    user: null,
    token: null,
    isAuthenticated: false,
    currentPage: 'dashboard',
    loading: false,
    notifications: [],
    alerts: [],
    properties: []
};

// ============================================
// DOM REFERENCES
// ============================================
const DOM = {
    app: document.getElementById('app'),
    sidebar: document.getElementById('sidebar'),
    mainContent: document.getElementById('mainContent'),
    navLinks: document.querySelectorAll('.nav-link'),
    userMenu: document.getElementById('userMenu'),
    notifications: document.getElementById('notifications'),
    loadingOverlay: document.getElementById('loadingOverlay')
};

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initApp();
});

function initApp() {
    // Check authentication
    checkAuth();
    
    // Setup event listeners
    setupEventListeners();
    
    // Load user data
    loadUserData();
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Setup service worker (if needed)
    setupServiceWorker();
    
    console.log(`${APP.name} v${APP.version} initialized`);
}

// ============================================
// AUTHENTICATION
// ============================================
function checkAuth() {
    const token = localStorage.getItem(APP.tokenKey);
    const userData = localStorage.getItem(APP.userKey);
    
    if (token && userData) {
        try {
            AppState.token = token;
            AppState.user = JSON.parse(userData);
            AppState.isAuthenticated = true;
            
            // Validate token with server
            validateToken(token);
        } catch (e) {
            console.error('Error parsing user data:', e);
            logout();
        }
    } else {
        // Redirect to login if not on login/register page
        const isAuthPage = window.location.pathname.includes('login') || 
                          window.location.pathname.includes('register');
        if (!isAuthPage) {
            window.location.href = '?page=login';
        }
    }
}

async function validateToken(token) {
    try {
        const response = await fetch(`${APP.apiBase}/auth/validate.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (!data.success) {
            logout();
        }
    } catch (error) {
        console.error('Token validation error:', error);
        logout();
    }
}

function logout() {
    localStorage.removeItem(APP.tokenKey);
    localStorage.removeItem(APP.userKey);
    AppState.isAuthenticated = false;
    AppState.user = null;
    AppState.token = null;
    
    // Redirect to login
    window.location.href = '?page=login';
}

// ============================================
// USER DATA
// ============================================
function loadUserData() {
    if (AppState.isAuthenticated && AppState.user) {
        // Update UI with user data
        updateUserUI();
        
        // Load role-specific data
        loadRoleData(AppState.user.role);
    }
}

function updateUserUI() {
    const user = AppState.user;
    
    // Update user menu
    const userMenuItems = document.querySelectorAll('.user-name');
    userMenuItems.forEach(el => {
        el.textContent = `${user.name} ${user.surname}`;
    });
    
    const userAvatar = document.querySelector('.user-avatar');
    if (userAvatar) {
        userAvatar.textContent = user.name.charAt(0).toUpperCase();
        userAvatar.style.backgroundColor = getAvatarColor(user.id);
    }
    
    // Update role badge
    const roleBadge = document.querySelector('.role-badge');
    if (roleBadge) {
        roleBadge.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
    }
}

function getAvatarColor(userId) {
    const colors = [
        '#667eea', '#764ba2', '#f093fb', '#4facfe',
        '#43e97b', '#fa709a', '#fee140', '#a18cd1'
    ];
    const index = parseInt(userId.substr(-1)) % colors.length;
    return colors[index];
}

// ============================================
// ROLE-BASED DATA LOADING
// ============================================
function loadRoleData(role) {
    switch(role) {
        case 'consumer':
            loadConsumerDashboard();
            break;
        case 'municipal':
            loadMunicipalDashboard();
            break;
        case 'admin':
            loadAdminDashboard();
            break;
        case 'technician':
            loadTechnicianDashboard();
            break;
        default:
            console.warn('Unknown role:', role);
    }
}

function loadConsumerDashboard() {
    // This will be called by dashboard.js
    if (typeof loadConsumerData === 'function') {
        loadConsumerData();
    }
}

function loadMunicipalDashboard() {
    if (typeof loadMunicipalData === 'function') {
        loadMunicipalData();
    }
}

function loadAdminDashboard() {
    if (typeof loadAdminData === 'function') {
        loadAdminData();
    }
}

function loadTechnicianDashboard() {
    if (typeof loadTechnicianData === 'function') {
        loadTechnicianData();
    }
}

// ============================================
// API HELPERS
// ============================================
const API = {
    async get(endpoint, params = {}) {
        const url = new URL(`${APP.apiBase}${endpoint}`, window.location.origin);
        Object.keys(params).forEach(key => {
            url.searchParams.append(key, params[key]);
        });
        
        return this.request(url, { method: 'GET' });
    },
    
    async post(endpoint, data = {}) {
        return this.request(`${APP.apiBase}${endpoint}`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    async put(endpoint, data = {}) {
        return this.request(`${APP.apiBase}${endpoint}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    async delete(endpoint) {
        return this.request(`${APP.apiBase}${endpoint}`, {
            method: 'DELETE'
        });
    },
    
    async request(url, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${AppState.token}`
        };
        
        const config = {
            ...options,
            headers: {
                ...headers,
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                if (response.status === 401) {
                    logout();
                }
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
};

// ============================================
// EVENT LISTENERS
// ============================================
function setupEventListeners() {
    // Navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.dataset.page;
            if (page) {
                navigateTo(page);
            }
        });
    });
    
    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
    
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }
    
    // Global error handler
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('Global error:', message, error);
        showToast('An unexpected error occurred', 'danger');
    };
    
    // Unhandled promise rejection
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled promise rejection:', e.reason);
        showToast('An unexpected error occurred', 'danger');
    });
}

// ============================================
// NAVIGATION
// ============================================
function navigateTo(page) {
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.history.pushState({ page }, '', url);
    
    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.toggle('active', link.dataset.page === page);
    });
    
    // Load page content
    loadPage(page);
}

function loadPage(page) {
    const content = document.getElementById('mainContent');
    
    // Show loading
    showLoading(true);
    
    // Fetch page content
    fetch(`/views/pages/${page}.php`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            // Initialize page-specific scripts
            if (typeof window[`init${page.charAt(0).toUpperCase() + page.slice(1)}`] === 'function') {
                window[`init${page.charAt(0).toUpperCase() + page.slice(1)}`]();
            }
        })
        .catch(error => {
            console.error('Page load error:', error);
            content.innerHTML = `
                <div class="error-page">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Page Not Found</h2>
                    <p>The page you're looking for doesn't exist.</p>
                    <button onclick="navigateTo('dashboard')">Go to Dashboard</button>
                </div>
            `;
        })
        .finally(() => {
            showLoading(false);
        });
}

// ============================================
// LOADING OVERLAY
// ============================================
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
    AppState.loading = show;
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================
function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${getToastIcon(type)}"></i>
        </div>
        <div class="toast-content">
            <p>${message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, duration);
    
    // Add animation
    setTimeout(() => toast.classList.add('show'), 100);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
        width: 100%;
    `;
    document.body.appendChild(container);
    return container;
}

function getToastIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'times-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// ============================================
// AUTO REFRESH
// ============================================
function startAutoRefresh() {
    setInterval(() => {
        if (AppState.isAuthenticated) {
            refreshData();
        }
    }, APP.refreshInterval);
}

function refreshData() {
    if (AppState.user) {
        loadRoleData(AppState.user.role);
    }
}

// ============================================
// SIDEBAR TOGGLE
// ============================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
    }
}

// ============================================
// SERVICE WORKER
// ============================================
function setupServiceWorker() {
    if ('serviceWorker' in navigator && 'production' === 'development') {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered:', registration);
            })
            .catch(error => {
                console.log('ServiceWorker registration failed:', error);
            });
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatDate(date) {
    if (typeof date === 'string') {
        date = new Date(date);
    }
    return date.toLocaleDateString('en-ZA', {
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

function formatNumber(num, decimals = 1) {
    return Number(num).toFixed(decimals);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export for use in other files
window.APP = APP;
window.AppState = AppState;
window.API = API;
window.showToast = showToast;
window.navigateTo = navigateTo;
window.logout = logout;
window.formatDate = formatDate;
window.formatCurrency = formatCurrency;
