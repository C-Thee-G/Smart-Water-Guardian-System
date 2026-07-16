<?php
/**
 * Application Constants
 */

// Application
define('APP_NAME', 'Smart Water Guardian');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Africa/Johannesburg');
date_default_timezone_set(APP_TIMEZONE);

// Security
define('JWT_SECRET', 'SMART_WATER_GUARDIAN_SECRET_KEY_2026_VERY_SECURE');
define('JWT_EXPIRY', 2592000); // 30 days in seconds
define('API_KEY', 'SMART_WATER_API_KEY_2026');
define('PASSWORD_MIN_LENGTH', 8);

// Firebase
define('FIREBASE_URL', 'https://smart-water-guardian-default-rtdb.firebaseio.com/');
define('FIREBASE_SECRET', 'YOUR_FIREBASE_SECRET');

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Alert Thresholds
define('LEAK_FLOW_RATE_THRESHOLD', 20); // L/min
define('CRITICAL_LEAK_FLOW_RATE', 30); // L/min
define('HIGH_USAGE_THRESHOLD', 25); // L/min
define('LOW_BATTERY_THRESHOLD', 20); // %
define('OFFLINE_TIMEOUT', 7200); // 2 hours in seconds
define('MAX_RETRIES', 3);

// Data Retention
define('READINGS_RETENTION_DAYS', 90);
define('AGGREGATED_RETENTION_DAYS', 730);
define('LOG_RETENTION_DAYS', 365);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Cache
define('CACHE_ENABLED', true);
define('CACHE_EXPIRY', 300); // 5 minutes

// Paths
define('BASE_PATH', dirname(__DIR__, 2));
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
define('LOG_PATH', BASE_PATH . '/logs');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development');
ini_set('log_errors', true);
ini_set('error_log', LOG_PATH . '/error.log');
?>
