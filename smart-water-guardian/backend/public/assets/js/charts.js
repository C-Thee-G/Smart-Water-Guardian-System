/**
 * Smart Water Guardian - Chart Configuration
 * Centralized chart configuration and helper functions
 */

// ============================================
// CHART DEFAULTS
// ============================================
const ChartDefaults = {
    colors: {
        primary: '#667eea',
        secondary: '#764ba2',
        success: '#48bb78',
        danger: '#fc8181',
        warning: '#f6ad55',
        info: '#4facfe',
        background: 'rgba(102, 126, 234, 0.1)',
        border: 'rgba(102, 126, 234, 0.8)'
    },
    font: {
        family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
        size: 12
    },
    responsive: true,
    maintainAspectRatio: false
};

// ============================================
// CHART COLOR PALETTES
// ============================================
const ChartColors = {
    primary: [
        'rgba(102, 126, 234, 0.8)',
        'rgba(118, 75, 162, 0.8)',
        'rgba(79, 172, 254, 0.8)',
        'rgba(72, 187, 120, 0.8)',
        'rgba(246, 173, 85, 0.8)',
        'rgba(252, 129, 129, 0.8)'
    ],
    secondary: [
        'rgba(102, 126, 234, 0.3)',
        'rgba(118, 75, 162, 0.3)',
        'rgba(79, 172, 254, 0.3)',
        'rgba(72, 187, 120, 0.3)',
        'rgba(246, 173, 85, 0.3)',
        'rgba(252, 129, 129, 0.3)'
    ],
    border: [
        'rgba(102, 126, 234, 1)',
        'rgba(118, 75, 162, 1)',
        'rgba(79, 172, 254, 1)',
        'rgba(72, 187, 120, 1)',
        'rgba(246, 173, 85, 1)',
        'rgba(252, 129, 129, 1)'
    ]
};

// ============================================
// CHART TYPES
// ============================================
const ChartTypes = {
    USAGE_TREND: 'usageTrend',
    USAGE_COMPARISON: 'usageComparison',
    NRW_BREAKDOWN: 'nrwBreakdown',
    CONSUMPTION_BY_TYPE: 'consumptionByType',
    DAILY_PATTERN: 'dailyPattern',
    HOURLY_PATTERN: 'hourlyPattern'
};

// ============================================
// CHART CONFIGURATION FACTORY
// ============================================
class ChartFactory {
    static createConfig(type, data, options = {}) {
        const configs = {
            [ChartTypes.USAGE_TREND]: this.usageTrendConfig,
            [ChartTypes.USAGE_COMPARISON]: this.usageComparisonConfig,
            [ChartTypes.NRW_BREAKDOWN]: this.nrwBreakdownConfig,
            [ChartTypes.CONSUMPTION_BY_TYPE]: this.consumptionByTypeConfig,
            [ChartTypes.DAILY_PATTERN]: this.dailyPatternConfig,
            [ChartTypes.HOURLY_PATTERN]: this.hourlyPatternConfig
        };
        
        const configFn = configs[type];
        if (!configFn) {
            throw new Error(`Unknown chart type: ${type}`);
        }
        
        return configFn(data, options);
    }
    
    static usageTrendConfig(data, options) {
        const defaultOptions = {
            label: 'Usage (L)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
        };
        const opts = { ...defaultOptions, ...options };
        
        return {
            type: 'line',
            data: {
                labels: data.labels || data.map(d => d.label || d.date),
                datasets: [{
                    label: opts.label,
                    data: data.values || data.map(d => d.value || d.total),
                    borderColor: ChartDefaults.colors.primary,
                    backgroundColor: ChartDefaults.colors.background,
                    fill: opts.fill,
                    tension: opts.tension,
                    pointBackgroundColor: ChartDefaults.colors.primary,
                    pointRadius: opts.pointRadius
                }]
            },
            options: {
                responsive: ChartDefaults.responsive,
                maintainAspectRatio: ChartDefaults.maintainAspectRatio,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y.toFixed(1)} L`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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
        };
    }
    
    static usageComparisonConfig(data, options) {
        const defaultOptions = {
            label1: 'Current',
            label2: 'Previous'
        };
        const opts = { ...defaultOptions, ...options };
        
        return {
            type: 'bar',
            data: {
                labels: data.labels || data.map(d => d.label || d.date),
                datasets: [
                    {
                        label: opts.label1,
                        data: data.values1 || data.map(d => d.current || d.total),
                        backgroundColor: ChartColors.primary[0],
                        borderColor: ChartColors.border[0],
                        borderWidth: 2
                    },
                    {
                        label: opts.label2,
                        data: data.values2 || data.map(d => d.previous || d.total),
                        backgroundColor: ChartColors.primary[1],
                        borderColor: ChartColors.border[1],
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: ChartDefaults.responsive,
                maintainAspectRatio: ChartDefaults.maintainAspectRatio,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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
        };
    }
    
    static nrwBreakdownConfig(data, options) {
        return {
            type: 'doughnut',
            data: {
                labels: data.labels || data.map(d => d.label || d.suburb),
                datasets: [{
                    data: data.values || data.map(d => d.value || d.nrw_percentage || d.nrw),
                    backgroundColor: ChartColors.primary,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: ChartDefaults.responsive,
                maintainAspectRatio: ChartDefaults.maintainAspectRatio,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                return `${label}: ${percentage}%`;
                            }
                        }
                    }
                }
            }
        };
    }
    
    static consumptionByTypeConfig(data, options) {
        return {
            type: 'pie',
            data: {
                labels: data.labels || data.map(d => d.label || d.property_type),
                datasets: [{
                    data: data.values || data.map(d => d.value || d.total_usage),
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(118, 75, 162, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(72, 187, 120, 0.8)'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: ChartDefaults.responsive,
                maintainAspectRatio: ChartDefaults.maintainAspectRatio,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                return `${label}: ${value.toFixed(1)} kL (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };
    }
    
    static dailyPatternConfig(data, options) {
        const defaultOptions = {
            label: 'Average Daily Usage (L)'
        };
        const opts = { ...defaultOptions, ...options };
        
        return {
            type: 'bar',
            data: {
                labels: data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: opts.label,
                    data: data.values || data,
                    backgroundColor: data.map((_, i) => {
                        const colors = [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(102, 126, 234, 0.7)',
                            'rgba(102, 126, 234, 0.6)',
                            'rgba(102, 126, 234, 0.5)',
                            'rgba(102, 126, 234, 0.4)',
                            'rgba(102, 126, 234, 0.7)',
                            'rgba(102, 126, 234, 0.9)'
                        ];
                        return colors[i % colors.length];
                    }),
                    borderColor: ChartDefaults.colors.primary,
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: ChartDefaults.responsive,
                maintainAspectRatio: ChartDefaults.maintainAspectRatio,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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
        };
    }
    
    static hourlyPatternConfig(data, options) {
        const defaultOptions = {
            label: 'Hourly Usage (L)'
        };
        const opts = { ...defaultOptions, ...options };
        
        return {
            type: 'line',
            data: {
                labels: data.labels || data.map(d => `${d.hour || d.label}:00`),
                datasets: [{
                    label: opts.label,
                    data: data.values || data.map(d => d.value || d.total),
                    borderColor: ChartDefaults.colors.secondary,
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: ChartDefaults.colors.secondary,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: ChartDefaults.responsive,
                maintainAspectRatio: ChartDefaults.maintainAspectRatio,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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
        };
    }
}

// ============================================
// CHART UTILITY FUNCTIONS
// ============================================
function createChart(ctx, config) {
    if (!ctx) {
        console.error('Chart context not found');
        return null;
    }
    
    try {
        return new Chart(ctx, config);
    } catch (error) {
        console.error('Chart creation error:', error);
        return null;
    }
}

function updateChart(chart, data) {
    if (!chart) return;
    
    try {
        chart.data = data;
        chart.update();
    } catch (error) {
        console.error('Chart update error:', error);
    }
}

function destroyChart(chart) {
    if (chart) {
        try {
            chart.destroy();
        } catch (error) {
            console.error('Chart destroy error:', error);
        }
    }
}

function getChartInstance(id) {
    const canvas = document.getElementById(id);
    if (!canvas) return null;
    return Chart.getChart(canvas);
}

// ============================================
// CHART DATA TRANSFORMERS
// ============================================
function transformReadingsToChartData(readings, type = 'daily') {
    if (!readings || readings.length === 0) return { labels: [], values: [] };
    
    let labels = [];
    let values = [];
    
    switch(type) {
        case 'daily':
            labels = readings.map(r => r.date);
            values = readings.map(r => r.total);
            break;
        case 'monthly':
            labels = readings.map(r => r.month);
            values = readings.map(r => r.total);
            break;
        case 'hourly':
            labels = readings.map(r => `${r.hour}:00`);
            values = readings.map(r => r.total);
            break;
        default:
            labels = readings.map(r => r.label || r.date);
            values = readings.map(r => r.value || r.total);
    }
    
    return { labels, values };
}

function aggregateDataByPeriod(readings, period = 'day') {
    if (!readings || readings.length === 0) return [];
    
    const groups = {};
    
    readings.forEach(reading => {
        const date = new Date(reading.reading_time || reading.timestamp);
        let key;
        
        switch(period) {
            case 'hour':
                key = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')} ${String(date.getHours()).padStart(2,'0')}:00`;
                break;
            case 'day':
                key = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
                break;
            case 'week':
                const weekStart = new Date(date);
                weekStart.setDate(date.getDate() - date.getDay());
                key = `${weekStart.getFullYear()}-W${String(Math.ceil((weekStart.getDate() + 6) / 7)).padStart(2,'0')}`;
                break;
            case 'month':
                key = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}`;
                break;
            default:
                key = date.toISOString().split('T')[0];
        }
        
        if (!groups[key]) {
            groups[key] = { total: 0, count: 0, key };
        }
        groups[key].total += reading.volume || reading.value || 0;
        groups[key].count++;
    });
    
    return Object.values(groups).map(g => ({
        label: g.key,
        total: g.total,
        average: g.total / g.count
    }));
}

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.ChartFactory = ChartFactory;
window.ChartTypes = ChartTypes;
window.ChartDefaults = ChartDefaults;
window.ChartColors = ChartColors;
window.createChart = createChart;
window.updateChart = updateChart;
window.destroyChart = destroyChart;
window.getChartInstance = getChartInstance;
window.transformReadingsToChartData = transformReadingsToChartData;
window.aggregateDataByPeriod = aggregateDataByPeriod;
