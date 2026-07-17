/**
 * Smart Water Guardian - Firebase Realtime Integration
 * Handles real-time data updates from Firebase
 */

// ============================================
// FIREBASE CONFIGURATION
// ============================================
const FirebaseConfig = {
    apiKey: "YOUR_FIREBASE_API_KEY",
    authDomain: "YOUR_PROJECT.firebaseapp.com",
    databaseURL: "https://YOUR_PROJECT-default-rtdb.firebaseio.com",
    projectId: "YOUR_PROJECT",
    storageBucket: "YOUR_PROJECT.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// ============================================
// FIREBASE STATE
// ============================================
const FirebaseState = {
    initialized: false,
    connected: false,
    listeners: {},
    lastUpdate: null,
    retryCount: 0,
    maxRetries: 5,
    retryDelay: 3000
};

// ============================================
// FIREBASE SERVICE
// ============================================
class FirebaseService {
    constructor() {
        this.app = null;
        this.database = null;
        this.auth = null;
    }
    
    initialize() {
        if (FirebaseState.initialized) return;
        
        try {
            // Initialize Firebase
            if (typeof firebase !== 'undefined' && firebase.initializeApp) {
                this.app = firebase.initializeApp(FirebaseConfig);
                this.database = firebase.database(this.app);
                this.auth = firebase.auth(this.app);
                FirebaseState.initialized = true;
                
                this.setupConnectionMonitor();
                console.log('Firebase initialized successfully');
            } else {
                console.warn('Firebase SDK not loaded. Using polling fallback.');
                this.setupPollingFallback();
            }
        } catch (error) {
            console.error('Firebase initialization error:', error);
            this.setupPollingFallback();
        }
    }
    
    setupConnectionMonitor() {
        if (!this.database) return;
        
        const connectedRef = this.database.ref('.info/connected');
        connectedRef.on('value', (snap) => {
            FirebaseState.connected = snap.val() === true;
            if (FirebaseState.connected) {
                console.log('Firebase connected');
                FirebaseState.retryCount = 0;
                this.onConnect();
            } else {
                console.log('Firebase disconnected');
                this.onDisconnect();
            }
        });
    }
    
    setupPollingFallback() {
        console.log('Using polling fallback for real-time updates');
        // Poll every 10 seconds as fallback
        setInterval(() => {
            this.pollForUpdates();
        }, 10000);
    }
    
    onConnect() {
        // Re-subscribe to all listeners
        Object.keys(FirebaseState.listeners).forEach(path => {
            const listener = FirebaseState.listeners[path];
            this.subscribe(path, listener.callback);
        });
    }
    
    onDisconnect() {
        // Handle disconnect
    }
    
    // ============================================
    // SUBSCRIBE TO REAL-TIME UPDATES
    // ============================================
    subscribe(path, callback, options = {}) {
        if (!this.database) {
            // Use polling fallback
            this.addPollingListener(path, callback, options);
            return;
        }
        
        const ref = this.database.ref(path);
        const listener = ref.on('value', (snapshot) => {
            const data = snapshot.val();
            FirebaseState.lastUpdate = new Date();
            callback(data, { type: 'update', path });
        }, (error) => {
            console.error('Firebase subscription error:', error);
            callback(null, { type: 'error', error });
        });
        
        FirebaseState.listeners[path] = {
            ref,
            listener,
            callback
        };
    }
    
    unsubscribe(path) {
        if (FirebaseState.listeners[path]) {
            const { ref, listener } = FirebaseState.listeners[path];
            ref.off('value', listener);
            delete FirebaseState.listeners[path];
        }
    }
    
    // ============================================
    // POLLING FALLBACK
    // ============================================
    addPollingListener(path, callback, options) {
        const interval = options.interval || 10000;
        const poller = setInterval(async () => {
            try {
                const response = await fetch(`/api/firebase-proxy.php?path=${path}`);
                const data = await response.json();
                callback(data, { type: 'poll', path });
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, interval);
        
        FirebaseState.listeners[path] = {
            poller,
            callback,
            isPolling: true
        };
    }
    
    async pollForUpdates() {
        // Poll all active listeners
        Object.keys(FirebaseState.listeners).forEach(path => {
            const listener = FirebaseState.listeners[path];
            if (listener.isPolling) {
                // Already polling
                return;
            }
            
            // Trigger a manual poll
            this.fetchData(path).then(data => {
                listener.callback(data, { type: 'poll', path });
            });
        });
    }
    
    // ============================================
    // FETCH DATA
    // ============================================
    async fetchData(path) {
        if (this.database) {
            const snapshot = await this.database.ref(path).once('value');
            return snapshot.val();
        } else {
            // Fetch via API proxy
            const response = await fetch(`/api/firebase-proxy.php?path=${path}`);
            const data = await response.json();
            return data;
        }
    }
    
    // ============================================
    // WRITE DATA
    // ============================================
    async setData(path, data) {
        if (this.database) {
            await this.database.ref(path).set(data);
            return { success: true };
        } else {
            const response = await fetch('/api/firebase-proxy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ path, data })
            });
            return await response.json();
        }
    }
    
    async updateData(path, data) {
        if (this.database) {
            await this.database.ref(path).update(data);
            return { success: true };
        } else {
            const response = await fetch('/api/firebase-proxy.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ path, data })
            });
            return await response.json();
        }
    }
}

// ============================================
// REALTIME UPDATES HANDLER
// ============================================
class RealtimeHandler {
    constructor() {
        this.firebase = new FirebaseService();
        this.callbacks = {
            onMeterUpdate: null,
            onAlertUpdate: null,
            onDashboardUpdate: null
        };
    }
    
    init() {
        this.firebase.initialize();
        this.setupListeners();
    }
    
    setupListeners() {
        // Listen for meter updates
        this.firebase.subscribe('meters', (data, meta) => {
            if (this.callbacks.onMeterUpdate) {
                this.callbacks.onMeterUpdate(data, meta);
            }
            
            // Update UI with new meter data
            this.handleMeterUpdate(data);
        });
        
        // Listen for alert updates
        this.firebase.subscribe('alerts', (data, meta) => {
            if (this.callbacks.onAlertUpdate) {
                this.callbacks.onAlertUpdate(data, meta);
            }
            
            // Update UI with new alerts
            this.handleAlertUpdate(data);
        });
        
        // Listen for dashboard updates
        this.firebase.subscribe('dashboard', (data, meta) => {
            if (this.callbacks.onDashboardUpdate) {
                this.callbacks.onDashboardUpdate(data, meta);
            }
        });
    }
    
    // ============================================
    // HANDLE METER UPDATES
    // ============================================
    handleMeterUpdate(data) {
        if (!data) return;
        
        // Update meter cards
        Object.keys(data).forEach(meterId => {
            const meter = data[meterId];
            const card = document.querySelector(`[data-meter-id="${meterId}"]`);
            if (card) {
                this.updateMeterCard(card, meter);
            }
            
            // Update flow rate display
            const flowElement = document.querySelector(`[data-meter-flow="${meterId}"]`);
            if (flowElement && meter.lastReading) {
                flowElement.textContent = `${meter.lastReading.flow_rate || 0} L/min`;
            }
            
            // Update volume display
            const volumeElement = document.querySelector(`[data-meter-volume="${meterId}"]`);
            if (volumeElement && meter.lastReading) {
                volumeElement.textContent = `${(meter.lastReading.volume || 0).toFixed(1)} L`;
            }
        });
    }
    
    updateMeterCard(card, meter) {
        const lastReading = meter.lastReading || {};
        
        // Update status
        const statusEl = card.querySelector('.meter-status');
        if (statusEl) {
            statusEl.className = `meter-status ${meter.status || 'offline'}`;
            statusEl.textContent = meter.status || 'Offline';
        }
        
        // Update battery
        const batteryEl = card.querySelector('.meter-battery');
        if (batteryEl && lastReading.battery !== undefined) {
            const level = lastReading.battery;
            batteryEl.innerHTML = `
                <i class="fas fa-battery-${this.getBatteryIcon(level)}"></i>
                ${level}%
            `;
        }
        
        // Update flow rate
        const flowEl = card.querySelector('.meter-flow');
        if (flowEl) {
            flowEl.textContent = `${lastReading.flow_rate || 0} L/min`;
        }
        
        // Update last update time
        const timeEl = card.querySelector('.meter-last-update');
        if (timeEl && lastReading.timestamp) {
            timeEl.textContent = `Last update: ${formatDate(lastReading.timestamp)}`;
        }
    }
    
    getBatteryIcon(level) {
        if (level > 75) return 'full';
        if (level > 50) return 'three-quarters';
        if (level > 25) return 'half';
        if (level > 10) return 'quarter';
        return 'empty';
    }
    
    // ============================================
    // HANDLE ALERT UPDATES
    // ============================================
    handleAlertUpdate(data) {
        if (!data) return;
        
        // Get active alerts count
        let activeCount = 0;
        let criticalCount = 0;
        
        Object.keys(data).forEach(userId => {
            const userAlerts = data[userId];
            if (!userAlerts) return;
            
            Object.keys(userAlerts).forEach(alertId => {
                const alert = userAlerts[alertId];
                if (!alert.read && alert.status !== 'resolved') {
                    activeCount++;
                    if (alert.severity === 'critical') {
                        criticalCount++;
                    }
                }
            });
        });
        
        // Update alert badge
        this.updateAlertBadge(activeCount);
        
        // Show notification for critical alerts
        if (criticalCount > 0) {
            this.showAlertNotification(`You have ${criticalCount} critical alert(s)!`);
        }
    }
    
    updateAlertBadge(count) {
        const badge = document.getElementById('alertBadge');
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
    
    showAlertNotification(message) {
        // Show browser notification if permitted
        if (Notification.permission === 'granted') {
            new Notification('Smart Water Guardian Alert', {
                body: message,
                icon: '/assets/images/logo.png'
            });
        } else if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Show toast notification
        showToast(message, 'danger');
    }
    
    // ============================================
    // REGISTER CALLBACKS
    // ============================================
    onMeterUpdate(callback) {
        this.callbacks.onMeterUpdate = callback;
    }
    
    onAlertUpdate(callback) {
        this.callbacks.onAlertUpdate = callback;
    }
    
    onDashboardUpdate(callback) {
        this.callbacks.onDashboardUpdate = callback;
    }
}

// ============================================
// REAL-TIME DASHBOARD UPDATER
// ============================================
class RealtimeDashboard {
    constructor() {
        this.chartInstances = {};
        this.lastData = null;
    }
    
    updateDashboard(data) {
        this.lastData = data;
        
        // Update summary stats
        this.updateStats(data);
        
        // Update charts
        this.updateCharts(data);
        
        // Update alerts
        this.updateAlerts(data);
        
        // Update meter status
        this.updateMeterStatus(data);
    }
    
    updateStats(data) {
        const stats = data.summary || {};
        const statMap = {
            'totalMeters': 'total_meters',
            'onlineMeters': 'online_meters',
            'totalConsumers': 'total_consumers',
            'todayUsage': 'today_usage',
            'nrwPercentage': 'nrw_percentage'
        };
        
        Object.keys(statMap).forEach(elementId => {
            const el = document.getElementById(elementId);
            if (el && stats[statMap[elementId]] !== undefined) {
                const value = stats[statMap[elementId]];
                el.textContent = typeof value === 'number' ? 
                    value.toLocaleString() : value;
            }
        });
    }
    
    updateCharts(data) {
        // Update daily trend chart
        if (data.daily_trend && this.chartInstances.daily) {
            const chart = this.chartInstances.daily;
            const labels = data.daily_trend.map(d => d.date);
            const values = data.daily_trend.map(d => d.total);
            
            chart.data.labels = labels;
            chart.data.datasets[0].data = values;
            chart.update('none');
        }
    }
    
    updateAlerts(data) {
        const alerts = data.recent_alerts || [];
        const container = document.getElementById('recentAlertsList');
        
        if (container) {
            container.innerHTML = alerts.slice(0, 5).map(alert => `
                <div class="alert-item alert-${alert.severity}">
                    <span class="alert-message">${alert.message}</span>
                    <span class="alert-time">${formatDate(alert.created_at)}</span>
                </div>
            `).join('');
        }
    }
    
    updateMeterStatus(data) {
        // Update individual meter cards
        const meters = data.meters || [];
        meters.forEach(meter => {
            const card = document.querySelector(`[data-meter-id="${meter.id}"]`);
            if (card) {
                const statusEl = card.querySelector('.status-indicator');
                if (statusEl) {
                    statusEl.className = `status-indicator ${meter.status}`;
                    statusEl.textContent = meter.status;
                }
            }
        });
    }
}

// ============================================
// INITIALIZE REALTIME
// ============================================
let realtimeHandler = null;
let realtimeDashboard = null;

function initRealtime() {
    if (realtimeHandler) return;
    
    realtimeHandler = new RealtimeHandler();
    realtimeDashboard = new RealtimeDashboard();
    
    // Register callbacks
    realtimeHandler.onMeterUpdate((data, meta) => {
        console.log('Meter update:', data);
        if (realtimeDashboard) {
            realtimeDashboard.updateDashboard({ meters: data });
        }
    });
    
    realtimeHandler.onAlertUpdate((data, meta) => {
        console.log('Alert update:', data);
        // Handle alert updates
        if (data && Object.keys(data).length > 0) {
            // Update alert badge count
            let count = 0;
            Object.keys(data).forEach(userId => {
                const userAlerts = data[userId];
                if (userAlerts) {
                    Object.values(userAlerts).forEach(alert => {
                        if (!alert.read) count++;
                    });
                }
            });
            
            const badge = document.getElementById('alertBadge');
            if (badge) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        }
    });
    
    realtimeHandler.onDashboardUpdate((data, meta) => {
        console.log('Dashboard update:', data);
        if (realtimeDashboard && data) {
            realtimeDashboard.updateDashboard(data);
        }
    });
    
    // Initialize
    realtimeHandler.init();
    
    console.log('Realtime updates initialized');
}

// ============================================
// EXPOSE TO GLOBAL
// ============================================
window.FirebaseService = FirebaseService;
window.RealtimeHandler = RealtimeHandler;
window.RealtimeDashboard = RealtimeDashboard;
window.initRealtime = initRealtime;
window.realtimeHandler = realtimeHandler;
window.realtimeDashboard = realtimeDashboard;

// ============================================
// AUTO-INITIALIZE
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in and on a page that needs real-time updates
    if (AppState.isAuthenticated) {
        // Initialize with slight delay to allow other scripts to load
        setTimeout(initRealtime, 1000);
    }
});

// ============================================
// REALTIME CONNECTION STATUS INDICATOR
// ============================================
function updateConnectionStatus(status) {
    const indicator = document.getElementById('connectionStatus');
    if (indicator) {
        indicator.className = `connection-status ${status}`;
        indicator.title = status === 'connected' ? 'Connected to real-time updates' : 'Reconnecting...';
        indicator.innerHTML = status === 'connected' ? '🟢' : '🟡';
    }
}

// Export for use in other files
window.updateConnectionStatus = updateConnectionStatus;
