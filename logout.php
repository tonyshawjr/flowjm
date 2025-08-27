<?php
/**
 * FlowJM Logout Handler
 * Destroys user session and redirects to login page
 */

// Define application root
define('FLOWJM_ROOT', __DIR__);

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'auth.php';

// Initialize auth system
Auth::init();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Perform logout
Auth::logout();

// Redirect to login page with success message
redirect('/login.php?message=logged_out');