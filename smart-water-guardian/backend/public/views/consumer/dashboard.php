<div class="consumer-dashboard">
    <header class="dashboard-header">
        <div class="logo">
            <h1>💧 Smart Water Guardian</h1>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['name'] ?? 'User'; ?></span>
            <button onclick="logout()">Logout</button>
        </div>
    </header>

    <div class="dashboard-content">
        <div class="usage-summary">
            <div class="summary-card">
                <h3>Current Flow Rate</h3>
                <div class="value" id="currentFlow">0.0</div>
                <div class="unit">L/min</div>
            </div>
            <div class="summary-card">
                <h3>Today's Usage</h3>
                <div class="value" id="todayUsage">0.0</div>
                <div class="unit">Litres</div>
            </div>
            <div class="summary-card">
                <h3>Monthly Usage</h3>
                <div class="value" id="monthlyUsage">0.0</div>
                <div class="unit">kL</div>
            </div>
            <div class="summary-card">
                <h3>Estimated Bill</h3>
                <div class="value" id="estimatedBill">R0.00</div>
                <div class="unit">This month</div>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3>Daily Usage Trend</h3>
                <canvas id="dailyChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Weekly Comparison</h3>
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <div class="alerts-section">
            <h3>Active Alerts</h3>
            <div id="alertsList"></div>
        </div>
    </div>
</div>

<script>
// Firebase real-time listener
const firebaseConfig = {
    // Your Firebase config
};

// Load dashboard data
async function loadDashboard() {
    try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/modules/dashboard/consumer.php', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        const data = response.data.data;
        updateDashboard(data);
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function updateDashboard(data) {
    const property = data.properties[0];
    if (property) {
        document.getElementById('currentFlow').textContent = 
            property.current_usage.flow_rate.toFixed(1);
        document.getElementById('todayUsage').textContent = 
            property.current_usage.today_total.toFixed(0);
        
        // Update charts
        updateDailyChart(property.weekly_usage);
        updateMonthlyChart(property.monthly_usage);
        
        // Update alerts
        updateAlerts(property.active_alerts);
    }
}

function updateDailyChart(data) {
    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'Daily Usage (L)',
                data: data.map(d => d.total),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateAlerts(alerts) {
    const container = document.getElementById('alertsList');
    if (alerts.length === 0) {
        container.innerHTML = '<p class="no-alerts">No active alerts</p>';
        return;
    }
    
    container.innerHTML = alerts.map(alert => `
        <div class="alert-card alert-${alert.severity}">
            <div class="alert-icon">${getAlertIcon(alert.alert_type)}</div>
            <div class="alert-content">
                <div class="alert-message">${alert.message}</div>
                <div class="alert-time">${new Date(alert.created_at).toLocaleString()}</div>
            </div>
            <button onclick="acknowledgeAlert('${alert.id}')">Acknowledge</button>
        </div>
    `).join('');
}

function getAlertIcon(type) {
    const icons = {
        'leak': '💧',
        'critical_leak': '⚠️',
        'threshold': '📊',
        'battery_low': '🔋',
        'offline': '📡'
    };
    return icons[type] || '🔔';
}

// Real-time updates via Firebase
function setupRealtimeUpdates() {
    // Listen for new readings
    // Listen for new alerts
}

// Load on page load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    setupRealtimeUpdates();
    
    // Refresh every 60 seconds
    setInterval(loadDashboard, 60000);
});
</script>

<style>
.consumer-dashboard {
    padding: 20px;
    background: #f5f7fa;
    min-height: 100vh;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.usage-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.summary-card .value {
    font-size: 32px;
    font-weight: bold;
    color: #2c3e50;
    margin: 10px 0;
}

.charts-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alerts-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alert-card {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid;
}

.alert-card.alert-critical { border-color: #e74c3c; background: #fde8e8; }
.alert-card.alert-high { border-color: #f39c12; background: #fef5e7; }
.alert-card.alert-warning { border-color: #f1c40f; background: #fef9e7; }
.alert-card.alert-info { border-color: #3498db; background: #eaf2f8; }

@media (max-width: 768px) {
    .charts-container {
        grid-template-columns: 1fr;
    }
    
    .usage-summary {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
