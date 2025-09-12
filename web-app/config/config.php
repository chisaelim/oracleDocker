<?php
// Application Configuration
class AppConfig {
    // Application settings
    public const APP_NAME = 'Oracle Business Management System';
    public const APP_VERSION = '1.0.0';
    public const APP_TIMEZONE = 'UTC';
    
    // Debug settings
    public const DEBUG_MODE = true;
    
    // Security settings
    public const SESSION_TIMEOUT = 3600; // 1 hour
    
    // Pagination settings
    public const ITEMS_PER_PAGE = 10;
    
    /**
     * Initialize application configuration
     */
    public static function init() {
        // Set timezone
        date_default_timezone_set(self::APP_TIMEZONE);
        
        // Set error reporting based on debug mode
        if (self::DEBUG_MODE) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Get base URL for the application
     * @return string
     */
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME']);
        return $protocol . '://' . $host . $script;
    }
}

// Initialize configuration
AppConfig::init();
?>