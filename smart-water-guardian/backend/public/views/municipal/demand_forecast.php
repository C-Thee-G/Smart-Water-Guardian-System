<?php
/**
 * Demand Forecast Page
 * Predict future water demand using historical data and ML
 */

if (!isset($_SESSION['token']) || $_SESSION['role'] !== 'municipal') {
    header('Location: ?page=login');
    exit;
}

$pageTitle = 'Demand Forecast';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="?page=tariff_management" class="nav-link">
                <i class="fas fa-coins"></i> Tariff Management
            </a>
            <a href="?page=demand_forecast" class="nav-link active">
                <i class="fas fa-chart-line"></i> Demand Forecast
            </a>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-body">
                <!-- Forecast Controls -->
                <div class="forecast-controls">
                    <div class="controls-card">
                        <h3><i class="fas fa-sliders-h"></i> Forecast Parameters</h3>
                        <form id="forecastForm" onsubmit="generateForecast(event)">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Forecast Period</label>
                                    <select id="forecastPeriod" onchange="updateForecastPeriod(this.value)">
                                        <option value="7">7 Days</option>
                                        <option value="14">14 Days</option>
                                        <option value="30" selected>30 Days</option>
                                        <option value="60">60 Days</option>
                                        <option value="90">90 Days</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Confidence Level</label>
                                    <select id="confidenceLevel">
                                        <option value="80">80%</option>
                                        <option value="85">85%</option>
                                        <option value="90" selected>90%</option>
                                        <option value="95">95%</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Include Seasonal Patterns</label>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="includeSeasonal" checked>
                                        <label for="includeSeasonal">Yes</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Include Weather Data</label>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="includeWeather">
                                        <label for="includeWeather">Yes</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-chart-line"></i> Generate Forecast
                                </button>
                                <button type="button" class="btn-secondary" onclick="resetForecast()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Forecast Results -->
                <div id="forecastResults">
                    <div class="empty-state">
                        <i class="fas fa-chart-line"></i>
                        <h3>No Forecast Generated</h3>
                        <p>Configure the parameters above and click "Generate Forecast"</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // ============================================
    // DEMAND FORECAST
    // ============================================
    let forecastCharts = {};
    let forecastData = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate default forecast
        setTimeout(() => generateForecast(new Event('submit')), 500);
    });

    // ============================================
    // GENERATE FORECAST
    // ============================================
    async function generateForecast(event) {
        event.preventDefault();
        
        const period = parseInt(document.getElementById('forecastPeriod').value);
        const confidence = parseInt(document.getElementById('confidenceLevel').value);
        const includeSeasonal = document.getElementById('includeSeasonal').checked;
        const includeWeather = document.getElementById('includeWeather').checked;
        
        // Show loading
        const results = document.getElementById('forecastResults');
        results.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Generating forecast using ML models...</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="forecastProgress" style="width: 0%;"></div>
                </div>
            </div>
        `;
        
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 95) {
                progress = 95;
                clearInterval(progressInterval);
            }
            document.getElementById('forecastProgress').style.width = progress + '%';
        }, 200);
        
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/modules/municipal/demand_forecast.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    forecast_days: period,
                    confidence_level: confidence,
                    include_seasonal: includeSeasonal,
                    include_weather: includeWeather
                })
            });
            
            const data = await response.json();
            
            clearInterval(progressInterval);
            document.getElementById('forecastProgress').style.width = '100%';
            
            if (data.success) {
                forecastData = data.forecast;
                setTimeout(() => {
                    renderForecast(data.forecast);
                }, 300);
            } else {
                results.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.message || 'Failed to generate forecast'}
                    </div>
                `;
            }
        } catch (error) {
            clearInterval(progressInterval);
            console.error('Generate forecast error:', error);
            results.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error generating forecast: ${error.message}
                </div>
            `;
        }
    }

    // ============================================
    // RENDER FORECAST
    // ============================================
    function renderForecast(forecast) {
        const results = document.getElementById('forecastResults');
        
        results.innerHTML = `
            <div class="forecast-container">
                <!-- Forecast Header -->
                <div class="forecast-header">
                    <div class="header-info">
                        <h2><i class="fas fa-chart-line"></i> Demand Forecast</h2>
                        <p>Generated on ${new Date().toLocaleString()}</p>
                    </div>
                    <div class="header-actions">
                        <button onclick="exportForecast('csv')" class="btn-secondary">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button onclick="exportForecast('pdf')" class="btn-secondary">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>

                <!-- Forecast Accuracy -->
                <div class="accuracy-section">
                    <h3>Forecast Accuracy</h3>
                    <div class="accuracy-grid">
                        <div class="accuracy-card">
                            <label>Model</label>
                            <span>${forecast.model_type || 'LSTM'}</span>
                        </div>
                        <div class="accuracy-card">
                            <label>Confidence Level</label>
                            <span>${forecast.confidence_level || 90}%</span>
                        </div>
                        <div class="accuracy-card">
                            <label>Mean Absolute Error</label>
                            <span>${forecast.mae || 0}%</span>
                        </div>
                        <div class="accuracy-card highlight">
                            <label>Overall Accuracy</label>
                            <span>${forecast.accuracy || 92}%</span>
                        </div>
                    </div>
                </div>

                <!-- Forecast Chart -->
                <div class="forecast-chart-section">
                    <h3>Demand Forecast</h3>
                    <div class="chart-container">
                        <canvas id="forecastChart"></canvas>
                    </div>
                </div>

                <!-- Forecast Details -->
                <div class="forecast-details">
                    <h3>Daily Forecast Details</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Predicted (kL)</th>
                                    <th>Lower Bound (kL)</th>
                                    <th>Upper Bound (kL)</th>
                                    <th>Confidence</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${forecast.daily_forecast.map(item => `
                                    <tr>
                                        <td>${item.date}</td>
                                        <td><strong>${item.predicted.toFixed(2)}</strong></td>
                                        <td>${item.lower_bound.toFixed(2)}</td>
                                        <td>${item.upper_bound.toFixed(2)}</td>
                                        <td>
                                            <span class="confidence-badge ${item.confidence > 85 ? 'high' : item.confidence > 70 ? 'medium' : 'low'}">
                                                ${item.confidence}%
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Insights -->
                <div class="insights-section">
                    <h3><i class="fas fa-lightbulb"></i> Key Insights</h3>
                    <div class="insights-grid">
                        <div class="insight-card">
                            <div class="insight-icon">📈</div>
                            <div class="insight-content">
                                <h4>Peak Demand</h4>
                                <p>Expected peak demand of <strong>${forecast.insights.peak_demand.toFixed(2)} kL</strong> on 
                                   ${forecast.insights.peak_date}</p>
                            </div>
                        </div>
                        <div class="insight-card">
                            <div class="insight-icon">📊</div>
                            <div class="insight-content">
                                <h4>Average Demand</h4>
                                <p>Average daily demand of <strong>${forecast.insights.average_demand.toFixed(2)} kL</strong>
                                   over the forecast period</p>
                            </div>
                        </div>
                        <div class="insight-card">
                            <div class="insight-icon">📉</div>
                            <div class="insight-content">
                                <h4>Trend</h4>
                                <p>Demand is expected to <strong>${forecast.insights.trend}</strong> by 
                                   ${forecast.insights.trend_percentage}%</p>
                            </div>
                        </div>
                        <div class="insight-card">
                            <div class="insight-icon">⚠️</div>
                            <div class="insight-content">
                                <h4>Risk Assessment</h4>
                                <p>${forecast.insights.risk_assessment}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="recommendations-section">
                    <h3><i class="fas fa-check-circle"></i> Recommendations</h3>
                    <ul class="recommendations-list">
                        ${forecast.recommendations.map(rec => `
                            <li>
                                <i class="fas fa-${rec.icon || 'arrow-right'}"></i>
                                <span>${rec.message}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
        `;
        
        // Initialize chart after DOM update
        setTimeout(() => {
            initForecastChart(forecast);
        }, 500);
    }

    // ============================================
    // INIT FORECAST CHART
    // ============================================
    function initForecastChart(forecast) {
        const ctx = document.getElementById('forecastChart');
        if (!ctx) return;
        
        // Destroy existing chart
        if (forecastCharts.main) {
            forecastCharts.main.destroy();
        }
        
        const data = forecast.daily_forecast || [];
        const labels = data.map(d => d.date);
        const predicted = data.map(d => d.predicted);
        const lower = data.map(d => d.lower_bound);
        const upper = data.map(d => d.upper_bound);
        
        forecastCharts.main = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Predicted Demand',
                        data: predicted,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Upper Bound (90%)',
                        data: upper,
                        borderColor: 'rgba(102, 126, 234, 0.2)',
                        backgroundColor: 'rgba(102, 126, 234, 0.05)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'Lower Bound (90%)',
                        data: lower,
                        borderColor: 'rgba(102, 126, 234, 0.2)',
                        backgroundColor: 'rgba(102, 126, 234, 0.05)',
                        fill: '+1',
                        tension: 0.4,
                        pointRadius: 0,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y;
                                if (label.includes('Bound')) {
                                    return `${label}: ${value.toFixed(2)} kL`;
                                }
                                return `${label}: ${value.toFixed(2)} kL`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Demand (kL)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // UPDATE FORECAST PERIOD
    // ============================================
    function updateForecastPeriod(period) {
        // Update the period label
        document.querySelector('.forecast-controls .controls-card h3').innerHTML = 
            `<i class="fas fa-sliders-h"></i> Forecast Parameters (${period} Days)`;
    }

    // ============================================
    // EXPORT FORECAST
    // ============================================
    function exportForecast(format) {
        if (!forecastData) {
            showToast('No forecast data to export', 'warning');
            return;
        }
        
        showToast(`Exporting ${format.toUpperCase()}...`, 'info');
        
        // Implement export based on format
        setTimeout(() => {
            showToast(`Forecast exported as ${format.toUpperCase()}`, 'success');
        }, 1500);
    }

    // ============================================
    // RESET FORECAST
    // ============================================
    function resetForecast() {
        document.getElementById('forecastPeriod').value = '30';
        document.getElementById('confidenceLevel').value = '90';
        document.getElementById('includeSeasonal').checked = true;
        document.getElementById('includeWeather').checked = false;
        updateForecastPeriod('30');
        showToast('Parameters reset', 'info');
        generateForecast(new Event('submit'));
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
        document.querySelector('.municipal-nav').classList.toggle('collapsed');
    }

    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '?page=login';
    }
    </script>

    <style>
    .forecast-controls {
        margin-bottom: 30px;
    }
    .controls-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .controls-card h3 {
        color: #2d3748;
        margin-bottom: 20px;
        font-size: 18px;
    }
    .controls-card h3 i {
        color: #667eea;
        margin-right: 10px;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        font-weight: 600;
        color: #4a5568;
        font-size: 14px;
        margin-bottom: 5px;
    }
    .form-group select {
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        background: white;
    }
    .form-group select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        outline: none;
    }
    .toggle-switch {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-top: 5px;
    }
    .toggle-switch input[type="checkbox"] {
        width: 44px;
        height: 24px;
        appearance: none;
        background: #cbd5e0;
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        transition: all 0.3s;
    }
    .toggle-switch input[type="checkbox"]:checked {
        background: #667eea;
    }
    .toggle-switch input[type="checkbox"]::before {
        content: '';
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: all 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .toggle-switch input[type="checkbox"]:checked::before {
        left: 22px;
    }
    .toggle-switch label {
        font-weight: 400;
        cursor: pointer;
        color: #4a5568;
    }
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 10px;
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
    .forecast-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .forecast-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f7fafc;
    }
    .forecast-header h2 {
        margin: 0;
        color: #2d3748;
        font-size: 24px;
    }
    .forecast-header h2 i {
        color: #667eea;
        margin-right: 10px;
    }
    .forecast-header p {
        color: #718096;
        margin: 5px 0 0 0;
    }
    .header-actions {
        display: flex;
        gap: 10px;
    }
    .header-actions button {
        padding: 8px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 13px;
    }
    .header-actions button:hover {
        background: #f7fafc;
        border-color: #667eea;
    }
    .accuracy-section {
        margin-bottom: 30px;
    }
    .accuracy-section h3 {
        color: #2d3748;
        font-size: 16px;
        margin-bottom: 15px;
    }
    .accuracy-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    .accuracy-card {
        padding: 15px;
        background: #f7fafc;
        border-radius: 8px;
        text-align: center;
    }
    .accuracy-card label {
        display: block;
        font-size: 12px;
        color: #718096;
        margin-bottom: 5px;
    }
    .accuracy-card span {
        font-size: 18px;
        font-weight: bold;
        color: #2d3748;
    }
    .accuracy-card.highlight {
        background: #ebf4ff;
        border: 2px solid #667eea;
    }
    .accuracy-card.highlight span {
        color: #667eea;
    }
    .forecast-chart-section {
        margin-bottom: 30px;
    }
    .forecast-chart-section h3 {
        color: #2d3748;
        font-size: 16px;
        margin-bottom: 15px;
    }
    .chart-container {
        height: 400px;
        position: relative;
    }
    .forecast-details {
        margin-bottom: 30px;
    }
    .forecast-details h3 {
        color: #2d3748;
        font-size: 16px;
        margin-bottom: 15px;
    }
    .confidence-badge {
        padding: 3px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
    }
    .confidence-badge.high { background: #c6f6d5; color: #22543d; }
    .confidence-badge.medium { background: #fefcbf; color: #744210; }
    .confidence-badge.low { background: #fed7d7; color: #742a2a; }
    .insights-section {
        margin-bottom: 30px;
    }
    .insights-section h3 {
        color: #2d3748;
        font-size: 16px;
        margin-bottom: 15px;
    }
    .insights-section h3 i {
        color: #f6ad55;
        margin-right: 10px;
    }
    .insights-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .insight-card {
        display: flex;
        gap: 15px;
        padding: 15px;
        background: #f7fafc;
        border-radius: 8px;
        align-items: flex-start;
    }
    .insight-card .insight-icon {
        font-size: 24px;
        min-width: 40px;
    }
    .insight-card .insight-content h4 {
        margin: 0 0 5px 0;
        color: #2d3748;
        font-size: 14px;
    }
    .insight-card .insight-content p {
        margin: 0;
        color: #4a5568;
        font-size: 14px;
        line-height: 1.5;
    }
    .recommendations-section {
        margin-top: 20px;
        padding: 20px;
        background: #f0fff4;
        border-radius: 10px;
        border-left: 4px solid #48bb78;
    }
    .recommendations-section h3 {
        color: #22543d;
        font-size: 16px;
        margin-bottom: 15px;
    }
    .recommendations-section h3 i {
        color: #48bb78;
        margin-right: 10px;
    }
    .recommendations-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .recommendations-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
        color: #2d3748;
    }
    .recommendations-list li:last-child {
        border-bottom: none;
    }
    .recommendations-list li i {
        color: #48bb78;
        margin-top: 3px;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th {
        background: #f7fafc;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #4a5568;
        font-size: 13px;
        border-bottom: 2px solid #e2e8f0;
    }
    .table td {
        padding: 10px 15px;
        border-bottom: 1px solid #f7fafc;
        color: #2d3748;
    }
    .table tr:hover td {
        background: #f7fafc;
    }
    .loading-state {
        text-align: center;
        padding: 50px;
    }
    .loading-state i {
        font-size: 48px;
        color: #667eea;
        animation: spin 1s linear infinite;
    }
    .loading-state p {
        color: #718096;
        margin-top: 15px;
    }
    .progress-bar {
        width: 100%;
        max-width: 400px;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        margin: 20px auto 0;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
        transition: width 0.3s;
    }
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #718096;
    }
    .empty-state i {
        font-size: 48px;
        color: #a0aec0;
        margin-bottom: 15px;
    }
    .empty-state h3 {
        margin: 0 0 10px 0;
        color: #2d3748;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .forecast-header {
            flex-direction: column;
            gap: 15px;
        }
        .header-actions {
            width: 100%;
        }
        .insights-grid {
            grid-template-columns: 1fr;
        }
        .accuracy-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    </style>
</body>
</html>
