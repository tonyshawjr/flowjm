<?php
/**
 * FlowJM API - Stack Feed Endpoint
 * Handle stack feed (moment feed) requests with infinite scroll
 */

// Define application root
define('FLOWJM_ROOT', dirname(__DIR__));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/Moment.php';
require_once INCLUDES_PATH . 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Require authentication
Auth::require();

$userId = Auth::id();

try {
    $moment = new Moment();
    
    $page = (int)($_GET['page'] ?? 1);
    $perPage = min((int)($_GET['per_page'] ?? 20), 50); // Limit for feed
    $type = $_GET['type'] ?? null;
    $journeyId = (int)($_GET['journey_id'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0); // For cursor-based pagination
    
    // Get moments for stack feed
    if ($lastId > 0) {
        // Use cursor-based pagination for infinite scroll
        $moments = $moment->getRecentByUserIdAfter($userId, $lastId, $perPage, $type, $journeyId);
    } else {
        if ($journeyId > 0) {
            // Get moments for specific journey
            $moments = $moment->getByJourneyId($journeyId, $page, $perPage);
        } else {
            // Get all recent moments
            $moments = $moment->getRecentByUserId($userId, $page, $perPage, $type);
        }
    }
    
    // Process moments for display
    $processedMoments = [];
    foreach ($moments as $momentData) {
        $processedMoments[] = [
            'id' => $momentData['id'],
            'journey_id' => $momentData['journey_id'],
            'journey_title' => $momentData['journey_title'] ?? null,
            'client_name' => $momentData['client_name'] ?? null,
            'type' => $momentData['type'],
            'title' => $momentData['title'],
            'content' => $momentData['content'],
            'amount' => $momentData['amount'],
            'created_at' => $momentData['created_at'],
            'updated_at' => $momentData['updated_at'],
            'formatted_date' => format_datetime($momentData['created_at']),
            'time_ago' => time_ago($momentData['created_at']),
            'type_icon' => getMomentTypeIcon($momentData['type']),
            'type_color' => get_moment_type_color($momentData['type'])
        ];
    }
    
    $hasMore = count($moments) === $perPage;
    $lastId = !empty($moments) ? end($moments)['id'] : 0;
    
    json_response([
        'success' => true,
        'moments' => $processedMoments,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'last_id' => $lastId,
            'next_page' => $hasMore ? $page + 1 : null
        ],
        'filters' => [
            'type' => $type,
            'journey_id' => $journeyId
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Stack API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred'], 500);
}