<?php
/**
 * FlowJM API - Pulse Health Endpoint
 * Handle pulse health calculation and status updates
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

// Require authentication
Auth::require();

$method = $_SERVER['REQUEST_METHOD'];
$userId = Auth::id();

try {
    $journey = new Journey();
    $moment = new Moment();
    
    switch ($method) {
        case 'GET':
            // Get pulse status for all journeys or specific journey
            $journeyId = (int)($_GET['journey_id'] ?? 0);
            
            if ($journeyId > 0) {
                // Get pulse for specific journey
                $journeyData = $journey->findById($journeyId);
                if (!$journeyData || $journeyData['user_id'] != $userId) {
                    json_response(['error' => 'Journey not found'], 404);
                }
                
                // Update pulse status
                $journey->updatePulseStatus($journeyId);
                
                // Get updated data
                $updatedJourney = $journey->findById($journeyId);
                
                json_response([
                    'success' => true,
                    'journey_id' => $journeyId,
                    'pulse_status' => $updatedJourney['pulse_status'],
                    'pulse_color' => get_pulse_color($updatedJourney['pulse_status']),
                    'pulse_icon' => getStatusIcon($updatedJourney['pulse_status'])
                ]);
            } else {
                // Get pulse summary for all journeys
                $activeJourneys = $journey->getByUserId($userId, 'active');
                $pulseData = [];
                
                foreach ($activeJourneys as $journeyData) {
                    // Update pulse status
                    $journey->updatePulseStatus($journeyData['id']);
                    
                    $pulseData[] = [
                        'journey_id' => $journeyData['id'],
                        'title' => $journeyData['title'],
                        'pulse_status' => $journeyData['pulse_status'],
                        'pulse_color' => get_pulse_color($journeyData['pulse_status'])
                    ];
                }
                
                // Calculate overall pulse
                $criticalCount = count(array_filter($pulseData, fn($p) => $p['pulse_status'] === 'critical'));
                $warningCount = count(array_filter($pulseData, fn($p) => $p['pulse_status'] === 'warning'));
                $healthyCount = count($pulseData) - $criticalCount - $warningCount;
                
                $overallPulse = 'healthy';
                if ($criticalCount > 0) {
                    $overallPulse = 'critical';
                } elseif ($warningCount > 0) {
                    $overallPulse = 'warning';
                }
                
                json_response([
                    'success' => true,
                    'overall_pulse' => $overallPulse,
                    'overall_color' => get_pulse_color($overallPulse),
                    'summary' => [
                        'critical' => $criticalCount,
                        'warning' => $warningCount,
                        'healthy' => $healthyCount,
                        'total' => count($pulseData)
                    ],
                    'journeys' => $pulseData
                ]);
            }
            break;
            
        case 'POST':
            // Update pulse status for specific journey
            Auth::requireCsrfToken();
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $journeyId = (int)($input['journey_id'] ?? 0);
            
            if (!$journeyId) {
                json_response(['error' => 'Journey ID required'], 400);
            }
            
            // Verify ownership
            $journeyData = $journey->findById($journeyId);
            if (!$journeyData || $journeyData['user_id'] != $userId) {
                json_response(['error' => 'Journey not found'], 404);
            }
            
            // Force update pulse status
            $updated = $journey->updatePulseStatus($journeyId);
            
            if (!$updated) {
                json_response(['error' => 'Failed to update pulse status'], 500);
            }
            
            // Get updated journey
            $updatedJourney = $journey->findById($journeyId);
            
            json_response([
                'success' => true,
                'message' => 'Pulse status updated',
                'journey_id' => $journeyId,
                'pulse_status' => $updatedJourney['pulse_status'],
                'pulse_color' => get_pulse_color($updatedJourney['pulse_status'])
            ]);
            break;
            
        case 'PUT':
            // Batch update pulse status for all user's journeys
            Auth::requireCsrfToken();
            
            $activeJourneys = $journey->getByUserId($userId, 'active');
            $updated = 0;
            
            foreach ($activeJourneys as $journeyData) {
                if ($journey->updatePulseStatus($journeyData['id'])) {
                    $updated++;
                }
            }
            
            json_response([
                'success' => true,
                'message' => "Updated pulse status for {$updated} journeys",
                'updated_count' => $updated,
                'total_journeys' => count($activeJourneys)
            ]);
            break;
            
        default:
            json_response(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Pulse API error: " . $e->getMessage());
    json_response(['error' => 'An error occurred'], 500);
}