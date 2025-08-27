<?php
/**
 * FlowJM Configuration Sample
 * Copy this file to config.php and update with your database credentials
 */

// Prevent direct access
if (!defined('FLOWJM_ROOT')) {
    die('Direct access not permitted');
}

// Environment Configuration
define('ENVIRONMENT', 'development'); // development, staging, production

// Database Configuration
// Update these values for your hosting environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

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

// Timezone Configuration
date_default_timezone_set('America/New_York');

// Debug Configuration
define('DEBUG', ENVIRONMENT === 'development');
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}