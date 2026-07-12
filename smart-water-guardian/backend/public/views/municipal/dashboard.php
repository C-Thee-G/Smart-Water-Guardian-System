<div class="municipal-dashboard">
    <header class="dashboard-header">
        <div class="logo">
            <h1>🏙️ Smart Water Guardian - Municipal</h1>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['name'] ?? 'Municipal Admin'; ?></span>
            <button onclick="logout()">Logout</button>
        </div>
    </header>

    <div class="municipal-content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-value" id="totalMeters">0</div>
                    <div class="stat-label">Total Meters</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🟢</div>
                <div class="stat-info">
                    <div class="stat-value" id="onlineMeters">0</div>
                    <div class="stat-label">Online Meters</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <div class="stat-value" id="totalConsumers">0</div>
                    <div class="stat-label">Total Consumers</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💧</div>
                <div class="stat-info">
                    <div class="stat-value" id="todayUsage">0</div>
                    <div class="stat-label">Today's Usage (L)</div>
                </div>
            </div>
            <div class="stat-card stat-nrw">
                <div class="stat-icon">📉</div>
                <div class="stat-info">
                    <div class="stat-value" id="nrwPercentage">0%</div>
                    <div class="stat-label">NRW Percentage</div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Daily Usage Trend</h3>
                <canvas id="dailyTrendChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Top NRW Areas</h3>
                <canvas id="nrwChart"></canvas>
            </div>
        </div>

        <div class="grid-2-col">
            <div class="section">
                <h3>Recent Alerts</h3>
                <div id="recentAlerts"></div>
            </div>
            <div class="section">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <button onclick="window.location.href='?page=nrw_report'">
                        Generate NRW Report
                    </button>
                    <button onclick="window.location.href='?page=consumer_management'">
                        Manage Consumers
                    </button>
                    <button onclick="window.location.href='?page=tariff_management'">
                        Manage Tariffs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadMunicipalDashboard() {
    try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/modules/dashboard/municipal.php', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        const data = response.data.data;
        updateStats(data.summary);
        updateTrendChart(data.daily_trend);
        updateNRWChart(data.top_nrw_areas);
        updateAlerts(data.recent_alerts);
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function updateStats(stats) {
    document.getElementById('totalMeters').textContent = stats.total_meters;
    document.getElementById('onlineMeters').textContent = stats.online_meters;
    document.getElementById('totalConsumers').textContent = stats.total_consumers;
    document.getElementById('todayUsage').textContent = stats.today_usage.toLocaleString();
    document.getElementById('nrwPercentage').textContent = stats.nrw_percentage + '%';
}

function updateTrendChart(data) {
    const ctx = document.getElementById('dailyTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'Daily Usage (L)',
                data: data.map(d => d.total),
                borderColor: 'rgba(52, 152, 219, 1)',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function updateNRWChart(data) {
    const ctx = document.getElementById('nrwChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.suburb),
            datasets: [
                {
                    label: 'Supplied',
                    data: data.map(d => d.supplied),
                    backgroundColor: 'rgba(46, 204, 113, 0.5)'
                },
                {
                    label: 'Billed',
                    data: data.map(d => d.billed),
                    backgroundColor: 'rgba(52, 152, 219, 0.5)'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: false
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateAlerts(alerts) {
    const container = document.getElementById('recentAlerts');
    if (alerts.length === 0) {
        container.innerHTML = '<p>No recent alerts</p>';
        return;
    }
    
    container.innerHTML = alerts.map(alert => `
        <div class="alert-item alert-${alert.severity}">
            <span class="alert-type">${alert.alert_type}</span>
            <span class="alert-message">${alert.message}</span>
            <span class="alert-user">${alert.name} ${alert.surname}</span>
            <span class="alert-time">${new Date(alert.created_at).toLocaleString()}</span>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadMunicipalDashboard);
</script>

<style>
.municipal-dashboard {
    padding: 20px;
    background: #f5f7fa;
    min-height: 100vh;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-card .stat-icon {
    font-size: 32px;
}

.stat-card .stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
}

.stat-card .stat-label {
    color: #7f8c8d;
    font-size: 14px;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.grid-2-col {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quick-actions button {
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: #3498db;
    color: white;
    cursor: pointer;
    transition: background 0.3s;
}

.quick-actions button:hover {
    background: #2980b9;
}

.alert-item {
    display: grid;
    grid-template-columns: 100px 1fr 150px 150px;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 8px;
    font-size: 14px;
}

.alert-item.alert-critical { background: #fde8e8; }
.alert-item.alert-high { background: #fef5e7; }
.alert-item.alert-warning { background: #fef9e7; }

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .grid-2-col {
        grid-template-columns: 1fr;
    }
    
    .alert-item {
        grid-template-columns: 1fr;
        gap: 4px;
    }
}
</style>
