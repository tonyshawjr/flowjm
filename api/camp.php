<?php
/**
 * FlowJM API - Camp Drawer Endpoint
 * Handle camp drawer data requests
 */

// Define application root
define('FLOWJM_ROOT', dirname(__DIR__));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/Journey.php';
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
    $journey = new Journey();
    $moment = new Moment();
    
    // Get circle journeys (priority journeys around the campfire)
    $circleJourneys = $journey->getCircleJourneys($userId);
    
    // Get all active journeys for quick access
    $activeJourneys = $journey->getByUserId($userId, 'active', 1, 50);
    
    // Get overdue journeys
    $overdueJourneys = $journey->getOverdue($userId);
    
    // Get recent moments for quick actions
    $recentMoments = $moment->getRecentByUserId($userId, 1, 5);
    
    // Get journey stats
    $journeyStats = $journey->getStats($userId);
    
    // Process circle journeys
    $processedCircleJourneys = [];
    foreach ($circleJourneys as $journeyData) {
        $processedCircleJourneys[] = [
            'id' => $journeyData['id'],
            'title' => $journeyData['title'],
            'client_name' => $journeyData['client_name'],
            'status' => $journeyData['status'],
            'pulse_status' => $journeyData['pulse_status'],
            'sale_amount' => $journeyData['sale_amount'],
            'paid_amount' => $journeyData['paid_amount'],
            'balance_due' => $journeyData['balance_due'] ?? ($journeyData['sale_amount'] - $journeyData['paid_amount']),
            'due_date' => $journeyData['due_date'],
            'moment_count' => $journeyData['moment_count'] ?? 0,
            'last_moment_at' => $journeyData['last_moment_at'],
            'pulse_color' => get_pulse_color($journeyData['pulse_status']),
            'status_icon' => getStatusIcon($journeyData['status']),
            'formatted_due_date' => $journeyData['due_date'] ? format_date($journeyData['due_date']) : null,
            'is_overdue' => $journeyData['due_date'] && $journeyData['due_date'] < date('Y-m-d')
        ];
    }
    
    // Process active journeys
    $processedActiveJourneys = [];
    foreach ($activeJourneys as $journeyData) {
        $processedActiveJourneys[] = [
            'id' => $journeyData['id'],
            'title' => $journeyData['title'],
            'client_name' => $journeyData['client_name'],
            'pulse_status' => $journeyData['pulse_status'],
            'in_circle' => $journeyData['in_circle'],
            'due_date' => $journeyData['due_date'],
            'formatted_due_date' => $journeyData['due_date'] ? format_date($journeyData['due_date']) : null,
            'pulse_color' => get_pulse_color($journeyData['pulse_status'])
        ];
    }
    
    // Process overdue journeys
    $processedOverdueJourneys = [];
    foreach ($overdueJourneys as $journeyData) {
        $processedOverdueJourneys[] = [
            'id' => $journeyData['id'],
            'title' => $journeyData['title'],
            'client_name' => $journeyData['client_name'],
            'due_date' => $journeyData['due_date'],
            'days_overdue' => $journeyData['days_overdue'],
            'formatted_due_date' => format_date($journeyData['due_date'])
        ];
    }
    
    // Process recent moments
    $processedRecentMoments = [];
    foreach ($recentMoments as $momentData) {
        $processedRecentMoments[] = [
            'id' => $momentData['id'],
            'journey_id' => $momentData['journey_id'],
            'journey_title' => $momentData['journey_title'] ?? null,
            'type' => $momentData['type'],
            'title' => $momentData['title'],
            'created_at' => $momentData['created_at'],
            'time_ago' => time_ago($momentData['created_at']),
            'type_icon' => getMomentTypeIcon($momentData['type'])
        ];
    }
    
    // Calculate dashboard metrics
    $metrics = [
        'total_journeys' => $journeyStats['total_journeys'] ?? 0,
        'active_journeys' => $journeyStats['active_journeys'] ?? 0,
        'circle_journeys' => $journeyStats['circle_journeys'] ?? 0,
        'critical_journeys' => $journeyStats['critical_journeys'] ?? 0,
        'warning_journeys' => $journeyStats['warning_journeys'] ?? 0,
        'total_sales' => $journeyStats['total_sales'] ?? 0,
        'total_paid' => $journeyStats['total_paid'] ?? 0,
        'outstanding_balance' => $journeyStats['outstanding_balance'] ?? 0,
        'overdue_count' => count($overdueJourneys)
    ];
    
    json_response([
        'success' => true,
        'data' => [
            'circle_journeys' => $processedCircleJourneys,
            'active_journeys' => $processedActiveJourneys,
            'overdue_journeys' => $processedOverdueJourneys,
            'recent_moments' => $processedRecentMoments,
            'metrics' => $metrics
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Camp API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred'], 500);
}