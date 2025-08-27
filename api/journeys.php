<?php
/**
 * FlowJM API - Journeys Endpoint
 * Handle CRUD operations for journeys
 */

// Define application root
define('FLOWJM_ROOT', dirname(__DIR__));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/Journey.php';
require_once INCLUDES_PATH . 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require authentication
Auth::require();

$method = $_SERVER['REQUEST_METHOD'];
$userId = Auth::id();

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && ($method === 'POST' || $method === 'PUT')) {
    $input = $_POST;
}

try {
    $journey = new Journey();
    
    switch ($method) {
        case 'GET':
            // Get journeys
            $page = (int)($_GET['page'] ?? 1);
            $perPage = min((int)($_GET['per_page'] ?? DEFAULT_PAGE_SIZE), MAX_PAGE_SIZE);
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? '';
            
            if (!empty($search)) {
                $journeys = $journey->search($userId, $search, $page, $perPage);
            } else {
                $journeys = $journey->getByUserId($userId, $status, $page, $perPage);
            }
            
            // Get circle journeys if requested
            $circleJourneys = [];
            if (isset($_GET['include_circle'])) {
                $circleJourneys = $journey->getCircleJourneys($userId);
            }
            
            // Get stats if requested
            $stats = [];
            if (isset($_GET['include_stats'])) {
                $stats = $journey->getStats($userId);
            }
            
            json_response([
                'success' => true,
                'journeys' => $journeys,
                'circle_journeys' => $circleJourneys,
                'stats' => $stats,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage
                ]
            ]);
            break;
            
        case 'POST':
            // Create new journey
            Auth::requireCsrfToken();
            
            $data = array_merge($input, ['user_id' => $userId]);
            
            // Validate data
            $errors = $journey->validate($data);
            if (!empty($errors)) {
                json_response(['error' => 'Validation failed', 'errors' => $errors], 400);
            }
            
            // Create journey
            $journeyId = $journey->create($data);
            
            if (!$journeyId) {
                json_response(['error' => 'Failed to create journey'], 500);
            }
            
            // Get created journey
            $newJourney = $journey->findById($journeyId);
            
            json_response([
                'success' => true,
                'message' => 'Journey created successfully',
                'journey' => $newJourney
            ], 201);
            break;
            
        case 'PUT':
            // Update journey
            Auth::requireCsrfToken();
            
            $journeyId = (int)($_GET['id'] ?? 0);
            if (!$journeyId) {
                json_response(['error' => 'Journey ID required'], 400);
            }
            
            // Verify ownership
            $existingJourney = $journey->findById($journeyId);
            if (!$existingJourney || $existingJourney['user_id'] != $userId) {
                json_response(['error' => 'Journey not found'], 404);
            }
            
            // Validate data
            $errors = $journey->validate($input);
            if (!empty($errors)) {
                json_response(['error' => 'Validation failed', 'errors' => $errors], 400);
            }
            
            // Update journey
            $updated = $journey->update($journeyId, $input);
            
            if (!$updated) {
                json_response(['error' => 'Failed to update journey'], 500);
            }
            
            // Get updated journey
            $updatedJourney = $journey->findById($journeyId);
            
            json_response([
                'success' => true,
                'message' => 'Journey updated successfully',
                'journey' => $updatedJourney
            ]);
            break;
            
        case 'DELETE':
            // Delete/Archive journey
            Auth::requireCsrfToken();
            
            $journeyId = (int)($_GET['id'] ?? 0);
            $permanent = !empty($_GET['permanent']);
            
            if (!$journeyId) {
                json_response(['error' => 'Journey ID required'], 400);
            }
            
            // Verify ownership
            $existingJourney = $journey->findById($journeyId);
            if (!$existingJourney || $existingJourney['user_id'] != $userId) {
                json_response(['error' => 'Journey not found'], 404);
            }
            
            if ($permanent) {
                $deleted = $journey->delete($journeyId);
            } else {
                $deleted = $journey->archive($journeyId);
            }
            
            if (!$deleted) {
                json_response(['error' => 'Failed to delete journey'], 500);
            }
            
            json_response([
                'success' => true,
                'message' => $permanent ? 'Journey deleted permanently' : 'Journey archived successfully'
            ]);
            break;
            
        default:
            json_response(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Journeys API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred'], 500);
}