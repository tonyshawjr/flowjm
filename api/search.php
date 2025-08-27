<?php
/**
 * FlowJM API - Search Endpoint
 * Handle search across journeys, moments, and fieldnotes
 */

// Define application root
define('FLOWJM_ROOT', dirname(__DIR__));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/Journey.php';
require_once INCLUDES_PATH . 'models/Moment.php';
require_once INCLUDES_PATH . 'models/Fieldnote.php';
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
    $query = trim($_GET['q'] ?? '');
    $type = $_GET['type'] ?? 'all'; // all, journeys, moments, fieldnotes
    $page = (int)($_GET['page'] ?? 1);
    $perPage = min((int)($_GET['per_page'] ?? DEFAULT_PAGE_SIZE), MAX_PAGE_SIZE);
    
    if (empty($query) || strlen($query) < 2) {
        json_response(['error' => 'Search query must be at least 2 characters'], 400);
    }
    
    $results = [];
    
    // Search journeys
    if ($type === 'all' || $type === 'journeys') {
        $journey = new Journey();
        $journeyResults = $journey->search($userId, $query, 1, 10); // Limit for mixed search
        
        foreach ($journeyResults as $journeyData) {
            $results[] = [
                'type' => 'journey',
                'id' => $journeyData['id'],
                'title' => $journeyData['title'],
                'client_name' => $journeyData['client_name'],
                'description' => truncate($journeyData['description'] ?? '', 100),
                'status' => $journeyData['status'],
                'pulse_status' => $journeyData['pulse_status'],
                'created_at' => $journeyData['created_at'],
                'updated_at' => $journeyData['updated_at'],
                'moment_count' => $journeyData['moment_count'] ?? 0,
                'balance_due' => $journeyData['balance_due'] ?? 0,
                'url' => "/journey.php?id={$journeyData['id']}",
                'highlight' => 'journey'
            ];
        }
    }
    
    // Search moments
    if ($type === 'all' || $type === 'moments') {
        $moment = new Moment();
        $momentResults = $moment->search($userId, $query, 1, 15); // Limit for mixed search
        
        foreach ($momentResults as $momentData) {
            $results[] = [
                'type' => 'moment',
                'id' => $momentData['id'],
                'journey_id' => $momentData['journey_id'],
                'journey_title' => $momentData['journey_title'] ?? null,
                'title' => $momentData['title'],
                'content' => truncate($momentData['content'] ?? '', 150),
                'moment_type' => $momentData['type'],
                'amount' => $momentData['amount'],
                'created_at' => $momentData['created_at'],
                'time_ago' => time_ago($momentData['created_at']),
                'type_icon' => getMomentTypeIcon($momentData['type']),
                'url' => "/journey.php?id={$momentData['journey_id']}#moment-{$momentData['id']}",
                'highlight' => 'moment'
            ];
        }
    }
    
    // Search fieldnotes
    if ($type === 'all' || $type === 'fieldnotes') {
        $fieldnote = new Fieldnote();
        $fieldnoteResults = $fieldnote->search($userId, $query, 1, 10); // Limit for mixed search
        
        foreach ($fieldnoteResults as $fieldnoteData) {
            $results[] = [
                'type' => 'fieldnote',
                'id' => $fieldnoteData['id'],
                'journey_id' => $fieldnoteData['journey_id'],
                'journey_title' => $fieldnoteData['journey_title'] ?? null,
                'title' => $fieldnoteData['title'],
                'content' => truncate($fieldnoteData['content'] ?? '', 150),
                'tags' => $fieldnoteData['tags'],
                'created_at' => $fieldnoteData['created_at'],
                'updated_at' => $fieldnoteData['updated_at'],
                'time_ago' => time_ago($fieldnoteData['created_at']),
                'url' => "/journey.php?id={$fieldnoteData['journey_id']}#fieldnote-{$fieldnoteData['id']}",
                'highlight' => 'fieldnote'
            ];
        }
    }
    
    // Sort results by relevance (updated_at for now)
    usort($results, function($a, $b) {
        $aTime = strtotime($a['updated_at'] ?? $a['created_at']);
        $bTime = strtotime($b['updated_at'] ?? $b['created_at']);
        return $bTime - $aTime;
    });
    
    // Apply pagination to combined results
    $totalResults = count($results);
    $offset = ($page - 1) * $perPage;
    $results = array_slice($results, $offset, $perPage);
    
    // Group results by type for display
    $groupedResults = [
        'journeys' => array_filter($results, fn($r) => $r['type'] === 'journey'),
        'moments' => array_filter($results, fn($r) => $r['type'] === 'moment'),
        'fieldnotes' => array_filter($results, fn($r) => $r['type'] === 'fieldnote')
    ];
    
    // Calculate result counts
    $counts = [
        'total' => $totalResults,
        'journeys' => count($groupedResults['journeys']),
        'moments' => count($groupedResults['moments']),
        'fieldnotes' => count($groupedResults['fieldnotes'])
    ];
    
    json_response([
        'success' => true,
        'query' => $query,
        'type' => $type,
        'results' => array_values($results), // Re-index array
        'grouped_results' => $groupedResults,
        'counts' => $counts,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_results' => $totalResults,
            'total_pages' => ceil($totalResults / $perPage),
            'has_more' => $totalResults > ($page * $perPage)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred during search'], 500);
}