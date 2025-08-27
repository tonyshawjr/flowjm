<?php
/**
 * FlowJM Configuration
 * Core application configuration for database, paths, and environment settings
 */

// Prevent direct access
if (!defined('FLOWJM_ROOT')) {
    die('Direct access not permitted');
}

// Environment Configuration
define('ENVIRONMENT', 'development'); // development, staging, production

// Check for local config override (for LocalWP or custom MySQL)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    // Default Database Configuration
    // Update these values for your hosting environment
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'LookoutJM');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
    define('DB_CHARSET', 'utf8mb4');
}

// Application Configuration
define('APP_NAME', 'FlowJM');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://your-domain.com'); // Update for production

// Security Configuration
define('SESSION_NAME', 'flowjm_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', FLOWJM_ROOT . '/uploads/');

// Path Configuration
define('COMPONENTS_PATH', FLOWJM_ROOT . '/components/');
define('INCLUDES_PATH', FLOWJM_ROOT . '/includes/');
define('ASSETS_PATH', FLOWJM_ROOT . '/assets/');

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Cache Configuration (for future implementation)
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 hour

// Email Configuration (for future notifications)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@your-domain.com');
define('FROM_NAME', 'FlowJM');

// Timezone Configuration
date_default_timezone_set('UTC');

// Error Reporting Based on Environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG', false);
}

// Journey Status Colors (Campsite Theme)
define('STATUS_COLORS', [
    'active' => 'green',      // Green Blaze - Trail Open
    'completed' => 'gray',    // Gray Blaze - Trail Memory
    'archived' => 'gray',     // Gray Blaze - Trail Memory
    'on_hold' => 'blue'       // Rest Stop Blue
]);

// Pulse Status Colors (Trail Markers)
define('PULSE_COLORS', [
    'healthy' => 'green',     // Trail Open Green
    'warning' => 'yellow',    // Caution Yellow
    'critical' => 'red'       // Danger Red
]);

// Moment Type Colors
define('MOMENT_TYPE_COLORS', [
    'update' => 'blue',       // Lake Blue
    'milestone' => 'green',   // Pine Green
    'payment' => 'orange',    // Sunrise Orange
    'delivery' => 'orange',   // Sunrise Orange
    'feedback' => 'yellow',   // Caution Yellow
    'note' => 'gray'          // Stone Gray
]);