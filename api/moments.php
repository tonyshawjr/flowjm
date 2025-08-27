<?php
/**
 * FlowJM API - Moments Endpoint
 * Handle CRUD operations for moments
 */

// Define application root
define('FLOWJM_ROOT', dirname(__DIR__));

// Load required files
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/Moment.php';
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
    $moment = new Moment();
    $journey = new Journey();
    
    switch ($method) {
        case 'GET':
            // Get moments
            $page = (int)($_GET['page'] ?? 1);
            $perPage = min((int)($_GET['per_page'] ?? DEFAULT_PAGE_SIZE), MAX_PAGE_SIZE);
            $journeyId = (int)($_GET['journey_id'] ?? 0);
            $type = $_GET['type'] ?? null;
            
            if ($journeyId) {
                // Verify journey ownership
                $journeyData = $journey->findById($journeyId);
                if (!$journeyData || $journeyData['user_id'] != $userId) {
                    json_response(['error' => 'Journey not found'], 404);
                }
                
                $moments = $moment->getByJourneyId($journeyId, $page, $perPage);
            } else {
                $moments = $moment->getRecentByUserId($userId, $page, $perPage, $type);
            }
            
            // Get stats if requested
            $stats = [];
            if (isset($_GET['include_stats'])) {
                $stats = $moment->getStats($userId);
            }
            
            json_response([
                'success' => true,
                'moments' => $moments,
                'stats' => $stats,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage
                ]
            ]);
            break;
            
        case 'POST':
            // Create new moment
            Auth::requireCsrfToken();
            
            $data = array_merge($input, ['user_id' => $userId]);
            
            // Verify journey ownership if journey_id provided
            if (!empty($data['journey_id'])) {
                $journeyData = $journey->findById($data['journey_id']);
                if (!$journeyData || $journeyData['user_id'] != $userId) {
                    json_response(['error' => 'Journey not found'], 404);
                }
            }
            
            // Validate data
            $errors = $moment->validate($data);
            if (!empty($errors)) {
                json_response(['error' => 'Validation failed', 'errors' => $errors], 400);
            }
            
            // Create moment
            $momentId = $moment->create($data);
            
            if (!$momentId) {
                json_response(['error' => 'Failed to create moment'], 500);
            }
            
            // Update journey's last moment timestamp
            if (!empty($data['journey_id'])) {
                $journey->updateLastMoment($data['journey_id']);
            }
            
            // Get created moment
            $newMoment = $moment->findById($momentId);
            
            json_response([
                'success' => true,
                'message' => 'Moment created successfully',
                'moment' => $newMoment
            ], 201);
            break;
            
        case 'PUT':
            // Update moment
            Auth::requireCsrfToken();
            
            $momentId = (int)($_GET['id'] ?? 0);
            if (!$momentId) {
                json_response(['error' => 'Moment ID required'], 400);
            }
            
            // Verify ownership
            $existingMoment = $moment->findById($momentId);
            if (!$existingMoment || $existingMoment['user_id'] != $userId) {
                json_response(['error' => 'Moment not found'], 404);
            }
            
            // Validate data
            $errors = $moment->validate($input);
            if (!empty($errors)) {
                json_response(['error' => 'Validation failed', 'errors' => $errors], 400);
            }
            
            // Update moment
            $updated = $moment->update($momentId, $input);
            
            if (!$updated) {
                json_response(['error' => 'Failed to update moment'], 500);
            }
            
            // Get updated moment
            $updatedMoment = $moment->findById($momentId);
            
            json_response([
                'success' => true,
                'message' => 'Moment updated successfully',
                'moment' => $updatedMoment
            ]);
            break;
            
        case 'DELETE':
            // Delete moment
            Auth::requireCsrfToken();
            
            $momentId = (int)($_GET['id'] ?? 0);
            
            if (!$momentId) {
                json_response(['error' => 'Moment ID required'], 400);
            }
            
            // Verify ownership
            $existingMoment = $moment->findById($momentId);
            if (!$existingMoment || $existingMoment['user_id'] != $userId) {
                json_response(['error' => 'Moment not found'], 404);
            }
            
            $deleted = $moment->delete($momentId);
            
            if (!$deleted) {
                json_response(['error' => 'Failed to delete moment'], 500);
            }
            
            json_response([
                'success' => true,
                'message' => 'Moment deleted successfully'
            ]);
            break;
            
        default:
            json_response(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Moments API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred'], 500);
}