<?php
/**
 * FlowJM API Health Check
 * Simple endpoint to check if the application is responsive
 */

// Define application root
define('FLOWJM_ROOT', dirname(__DIR__));

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Basic health check
$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => APP_VERSION
];

// Check database connectivity
try {
    require_once FLOWJM_ROOT . '/includes/database.php';
    $db = Database::getInstance();
    $db->getConnection()->query('SELECT 1');
    $health['database'] = 'connected';
} catch (Exception $e) {
    $health['database'] = 'error';
    $health['status'] = 'error';
    http_response_code(500);
}

// Return health status
echo json_encode($health);
exit;