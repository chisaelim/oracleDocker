<?php
/**
 * Application Configuration
 */

// Database Configuration
define('DB_HOST', 'oracle-db');
define('DB_PORT', '1521');
define('DB_SERVICE', 'XEPDB1');
define('DB_USERNAME', 'appuser');
define('DB_PASSWORD', 'appuser123');

// Application Configuration
define('APP_NAME', 'Oracle Business Admin');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost:8090');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_NAME', '_csrf_token');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>