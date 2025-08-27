<?php
/**
 * FlowJM API - Logout Endpoint
 * Handle AJAX logout requests
 */

// Define application root
define('FLOWJM_ROOT', dirname(dirname(__DIR__)));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Initialize auth
Auth::init();

// Check if user is logged in
if (!Auth::check()) {
    json_response(['error' => 'Not logged in'], 401);
}

try {
    // Verify CSRF token
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Auth::verifyCsrfToken($csrfToken)) {
        json_response(['error' => 'Invalid security token'], 403);
    }
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Perform logout
    Auth::logout();
    
    // Return success response
    json_response([
        'success' => true,
        'message' => 'Logout successful',
        'redirect' => '/login.php'
    ]);
    
} catch (Exception $e) {
    error_log("Logout API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred during logout'], 500);
}