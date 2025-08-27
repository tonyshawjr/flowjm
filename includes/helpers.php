<?php
/**
 * FlowJM Helper Functions
 * Utility functions for common operations throughout the application
 */

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require login - redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require specific role
 */
function require_role($role) {
    require_login();
    if (!has_role($role)) {
        redirect('/unauthorized.php');
        exit;
    }
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Format date for display
 */
function format_date($date, $format = 'M j, Y') {
    if (!$date) return '';
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Format datetime for display
 */
function format_datetime($datetime, $format = 'M j, Y g:i A') {
    if (!$datetime) return '';
    
    $dateObj = new DateTime($datetime);
    return $dateObj->format($format);
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function time_ago($datetime) {
    if (!$datetime) return '';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hour' . (floor($time/3600) > 1 ? 's' : '') . ' ago';
    if ($time < 2592000) return floor($time/86400) . ' day' . (floor($time/86400) > 1 ? 's' : '') . ' ago';
    if ($time < 31104000) return floor($time/2592000) . ' month' . (floor($time/2592000) > 1 ? 's' : '') . ' ago';
    
    return floor($time/31104000) . ' year' . (floor($time/31104000) > 1 ? 's' : '') . ' ago';
}

/**
 * Format currency amount
 */
function format_currency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Get status color class for Tailwind
 */
function get_status_color($status) {
    return STATUS_COLORS[$status] ?? 'gray';
}

/**
 * Get pulse color class for Tailwind
 */
function get_pulse_color($pulseStatus) {
    return PULSE_COLORS[$pulseStatus] ?? 'gray';
}

/**
 * Get moment type color class for Tailwind
 */
function get_moment_type_color($momentType) {
    return MOMENT_TYPE_COLORS[$momentType] ?? 'gray';
}

/**
 * Get initials from name
 */
function get_initials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Truncate text to specified length
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Clean filename for upload
 */
function clean_filename($filename) {
    // Get file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Clean the filename
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-zA-Z0-9-_]/', '', $name);
    $name = substr($name, 0, 50); // Limit length
    
    return $name . '.' . $ext;
}

/**
 * Generate unique filename
 */
function generate_filename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid() . '_' . time() . '.' . $ext;
}

/**
 * Validate file upload
 */
function validate_upload($file) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload';
        return $errors;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file was uploaded';
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File exceeds maximum size limit';
            break;
        default:
            $errors[] = 'Unknown upload error';
            break;
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        $errors[] = 'File size exceeds maximum limit of ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB';
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_TYPES)) {
        $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', UPLOAD_ALLOWED_TYPES);
    }
    
    return $errors;
}

/**
 * Create pagination array
 */
function create_pagination($currentPage, $totalPages, $url = '') {
    $pagination = [
        'current' => $currentPage,
        'total' => $totalPages,
        'prev' => $currentPage > 1 ? $currentPage - 1 : null,
        'next' => $currentPage < $totalPages ? $currentPage + 1 : null,
        'pages' => []
    ];
    
    // Calculate page range to show
    $range = 5;
    $start = max(1, $currentPage - floor($range / 2));
    $end = min($totalPages, $start + $range - 1);
    
    // Adjust start if we're near the end
    if ($end - $start < $range - 1) {
        $start = max(1, $end - $range + 1);
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'url' => $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . $i,
            'current' => $i === $currentPage
        ];
    }
    
    return $pagination;
}

/**
 * Flash message functions
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash_messages() {
    if (isset($_SESSION['flash'])) {
        $messages = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $messages;
    }
    return [];
}

/**
 * Debug function (only works in development)
 */
function debug($data) {
    if (DEBUG) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

/**
 * Log error to file
 */
function log_error($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage . PHP_EOL, 3, FLOWJM_ROOT . '/logs/error.log');
}

/**
 * Generate random password
 */
function generate_password($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

/**
 * Send JSON response
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Campsite theme color utilities
 */
function get_campsite_colors() {
    return [
        'canvas' => '#F5F0E8',
        'night-sky' => '#3A3938',
        'forest-floor' => '#2D2C2B',
        'stone-gray' => '#6B6968',
        'morning-mist' => '#9B9998',
        'sunrise-orange' => '#EA580C',
        'pine-green' => '#059669',
        'lake-blue' => '#0284C7',
        'trail-brown' => '#92400E',
        'danger-red' => '#DC2626',
        'caution-yellow' => '#F59E0B',
        'trail-green' => '#10B981',
        'rest-blue' => '#0284C7'
    ];
}

/**
 * Get trail blaze (journey status) indicator
 */
function get_trail_blaze($status) {
    $blazes = [
        'active' => 'green',    // Green Blaze - good hiking weather
        'completed' => 'gray',  // Gray Blaze - trail memory
        'archived' => 'gray',   // Gray Blaze - trail memory
        'on_hold' => 'blue',    // Rest Stop Blue - pause point
        'critical' => 'red',    // Red Blaze - urgent campfire needed
        'warning' => 'yellow'   // Yellow Blaze - check the map
    ];
    
    return $blazes[$status] ?? 'gray';
}