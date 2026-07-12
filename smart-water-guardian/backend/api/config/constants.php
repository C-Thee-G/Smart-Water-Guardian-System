<?php
// Application Constants
define('APP_NAME', 'Smart Water Guardian');
define('APP_VERSION', '1.0.0');

// Security
define('JWT_SECRET', 'SMART_WATER_GUARDIAN_SECRET_KEY_2026');
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
define('LOW_BATTERY_THRESHOLD', 20); // %
define('OFFLINE_TIMEOUT', 7200); // 2 hours in seconds
?>
