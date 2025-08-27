<?php
/**
 * FlowJM API - Login Endpoint
 * Handle AJAX login requests
 */

// Define application root
define('FLOWJM_ROOT', dirname(dirname(__DIR__)));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/User.php';
require_once INCLUDES_PATH . 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Initialize auth
Auth::init();

// Check if already logged in
if (Auth::check()) {
    json_response(['error' => 'Already logged in'], 400);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = sanitize_input($input['email'] ?? '');
$password = $input['password'] ?? '';
$csrfToken = $input[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$rememberMe = !empty($input['remember']);

try {
    // Validate CSRF token
    if (!Auth::verifyCsrfToken($csrfToken)) {
        json_response(['error' => 'Invalid security token'], 403);
    }
    
    // Check for brute force attempts
    if (Auth::checkBruteForce($email)) {
        json_response(['error' => 'Too many failed attempts. Please wait 15 minutes.'], 429);
    }
    
    // Validate input
    if (empty($email) || empty($password)) {
        json_response(['error' => 'Email and password are required'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => 'Invalid email format'], 400);
    }
    
    // Attempt authentication
    $user = new User();
    $authenticatedUser = $user->authenticate($email, $password);
    
    if (!$authenticatedUser) {
        Auth::recordFailedAttempt($email);
        Auth::logActivity('failed_login_api', "Failed API login attempt for: $email");
        json_response(['error' => 'Invalid email or password'], 401);
    }
    
    // Clear failed attempts
    Auth::clearFailedAttempts($email);
    
    // Login user
    Auth::login($authenticatedUser);
    
    // Set remember me cookie if requested
    if ($rememberMe) {
        $rememberToken = bin2hex(random_bytes(32));
        setcookie('remember_token', $rememberToken, time() + (86400 * 30), '/', '', true, true);
    }
    
    // Return success response
    json_response([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $authenticatedUser['id'],
            'name' => $authenticatedUser['name'],
            'email' => $authenticatedUser['email'],
            'role' => $authenticatedUser['role']
        ],
        'redirect' => $input['redirect'] ?? '/'
    ]);
    
} catch (Exception $e) {
    error_log("Login API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred during login'], 500);
}