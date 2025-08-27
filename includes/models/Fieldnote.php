<?php
/**
 * FlowJM Fieldnote Model
 * Handles private annotations attached to journeys or moments for personal context
 */

class Fieldnote {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new fieldnote
     */
    public function create($data) {
        // Validate that fieldnote is attached to either journey OR moment, not both
        if ((!empty($data['journey_id']) && !empty($data['moment_id'])) ||
            (empty($data['journey_id']) && empty($data['moment_id']))) {
            return false;
        }

        $sql = "INSERT INTO fieldnotes (user_id, journey_id, moment_id, content) 
                VALUES (?, ?, ?, ?)";
        
        $params = [
            $data['user_id'],
            $data['journey_id'] ?? null,
            $data['moment_id'] ?? null,
            $data['content']
        ];
        
        return $this->db->insert($sql, $params);
    }

    /**
     * Find fieldnote by ID
     */
    public function findById($id) {
        $sql = "SELECT fn.*, 
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN j.title 
                    WHEN fn.moment_id IS NOT NULL THEN CONCAT('Moment: ', LEFT(m.content, 50), '...') 
                END as attached_to_title,
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN 'journey'
                    WHEN fn.moment_id IS NOT NULL THEN 'moment'
                END as attached_to_type
                FROM fieldnotes fn
                LEFT JOIN journeys j ON fn.journey_id = j.id
                LEFT JOIN moments m ON fn.moment_id = m.id
                WHERE fn.id = ?";
        
        return $this->db->selectOne($sql, [$id]);
    }

    /**
     * Get fieldnotes by journey
     */
    public function getByJourneyId($journeyId, $userId) {
        $sql = "SELECT fn.*, j.title as journey_title
                FROM fieldnotes fn
                JOIN journeys j ON fn.journey_id = j.id
                WHERE fn.journey_id = ? AND fn.user_id = ?
                ORDER BY fn.created_at DESC";
        
        return $this->db->select($sql, [$journeyId, $userId]);
    }

    /**
     * Get fieldnotes by moment
     */
    public function getByMomentId($momentId, $userId) {
        $sql = "SELECT fn.*, LEFT(m.content, 100) as moment_preview
                FROM fieldnotes fn
                JOIN moments m ON fn.moment_id = m.id
                WHERE fn.moment_id = ? AND fn.user_id = ?
                ORDER BY fn.created_at DESC";
        
        return $this->db->select($sql, [$momentId, $userId]);
    }

    /**
     * Get all fieldnotes for a user
     */
    public function getByUserId($userId, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $sql = "SELECT fn.*, 
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN j.title 
                    WHEN fn.moment_id IS NOT NULL THEN CONCAT('Moment: ', LEFT(m.content, 50), '...') 
                END as attached_to_title,
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN 'journey'
                    WHEN fn.moment_id IS NOT NULL THEN 'moment'
                END as attached_to_type,
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN fn.journey_id
                    WHEN fn.moment_id IS NOT NULL THEN m.journey_id
                END as related_journey_id
                FROM fieldnotes fn
                LEFT JOIN journeys j ON fn.journey_id = j.id
                LEFT JOIN moments m ON fn.moment_id = m.id
                WHERE fn.user_id = ?
                ORDER BY fn.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$userId]);
    }

    /**
     * Update fieldnote
     */
    public function update($id, $data) {
        if (!isset($data['content'])) {
            return false;
        }
        
        $sql = "UPDATE fieldnotes SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->execute($sql, [$data['content'], $id]) > 0;
    }

    /**
     * Delete fieldnote
     */
    public function delete($id) {
        $sql = "DELETE FROM fieldnotes WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    /**
     * Search fieldnotes
     */
    public function search($userId, $query, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $searchTerm = '%' . $this->db->escapeLike($query) . '%';
        
        $sql = "SELECT fn.*, 
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN j.title 
                    WHEN fn.moment_id IS NOT NULL THEN CONCAT('Moment: ', LEFT(m.content, 50), '...') 
                END as attached_to_title,
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN 'journey'
                    WHEN fn.moment_id IS NOT NULL THEN 'moment'
                END as attached_to_type
                FROM fieldnotes fn
                LEFT JOIN journeys j ON fn.journey_id = j.id
                LEFT JOIN moments m ON fn.moment_id = m.id
                WHERE fn.user_id = ? AND fn.content LIKE ?
                ORDER BY fn.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$userId, $searchTerm]);
    }

    /**
     * Get fieldnote statistics
     */
    public function getStats($userId) {
        $sql = "SELECT 
            COUNT(*) as total_fieldnotes,
            COUNT(CASE WHEN journey_id IS NOT NULL THEN 1 END) as journey_notes,
            COUNT(CASE WHEN moment_id IS NOT NULL THEN 1 END) as moment_notes,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as this_week,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as this_month
        FROM fieldnotes 
        WHERE user_id = ?";
        
        return $this->db->selectOne($sql, [$userId]);
    }

    /**
     * Get recent fieldnotes
     */
    public function getRecent($userId, $limit = 5) {
        $sql = "SELECT fn.*, 
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN j.title 
                    WHEN fn.moment_id IS NOT NULL THEN CONCAT('Moment: ', LEFT(m.content, 30), '...') 
                END as attached_to_title,
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN 'journey'
                    WHEN fn.moment_id IS NOT NULL THEN 'moment'
                END as attached_to_type
                FROM fieldnotes fn
                LEFT JOIN journeys j ON fn.journey_id = j.id
                LEFT JOIN moments m ON fn.moment_id = m.id
                WHERE fn.user_id = ?
                ORDER BY fn.created_at DESC
                LIMIT ?";
        
        return $this->db->select($sql, [$userId, $limit]);
    }

    /**
     * Get fieldnotes for a specific journey (including moment fieldnotes)
     */
    public function getAllForJourney($journeyId, $userId) {
        $sql = "SELECT fn.*, 
                CASE 
                    WHEN fn.journey_id IS NOT NULL THEN 'journey'
                    WHEN fn.moment_id IS NOT NULL THEN 'moment'
                END as note_type,
                CASE 
                    WHEN fn.moment_id IS NOT NULL THEN LEFT(m.content, 50)
                    ELSE NULL
                END as moment_preview
                FROM fieldnotes fn
                LEFT JOIN moments m ON fn.moment_id = m.id
                WHERE fn.user_id = ? AND (
                    fn.journey_id = ? OR 
                    (fn.moment_id IS NOT NULL AND m.journey_id = ?)
                )
                ORDER BY fn.created_at DESC";
        
        return $this->db->select($sql, [$userId, $journeyId, $journeyId]);
    }

    /**
     * Count fieldnotes for a journey
     */
    public function countForJourney($journeyId, $userId) {
        $sql = "SELECT COUNT(*) as count
                FROM fieldnotes fn
                LEFT JOIN moments m ON fn.moment_id = m.id
                WHERE fn.user_id = ? AND (
                    fn.journey_id = ? OR 
                    (fn.moment_id IS NOT NULL AND m.journey_id = ?)
                )";
        
        $result = $this->db->selectOne($sql, [$userId, $journeyId, $journeyId]);
        return $result['count'];
    }

    /**
     * Count fieldnotes for a moment
     */
    public function countForMoment($momentId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM fieldnotes WHERE moment_id = ? AND user_id = ?";
        $result = $this->db->selectOne($sql, [$momentId, $userId]);
        return $result['count'];
    }

    /**
     * Validate fieldnote data
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['content']) || strlen(trim($data['content'])) < 3) {
            $errors['content'] = 'Fieldnote content must be at least 3 characters long';
        }
        
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            $errors['user_id'] = 'Valid user is required';
        }
        
        // Check that exactly one attachment point is specified
        $hasJourney = !empty($data['journey_id']);
        $hasMoment = !empty($data['moment_id']);
        
        if (!$hasJourney && !$hasMoment) {
            $errors['attachment'] = 'Fieldnote must be attached to either a journey or moment';
        } elseif ($hasJourney && $hasMoment) {
            $errors['attachment'] = 'Fieldnote cannot be attached to both journey and moment';
        }
        
        if ($hasJourney && !is_numeric($data['journey_id'])) {
            $errors['journey_id'] = 'Valid journey ID is required';
        }
        
        if ($hasMoment && !is_numeric($data['moment_id'])) {
            $errors['moment_id'] = 'Valid moment ID is required';
        }
        
        return $errors;
    }

    /**
     * Format fieldnote content for display
     */
    public function formatContent($content) {
        // Convert line breaks and escape HTML
        $content = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
        
        // Simple formatting similar to moments
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        
        return $content;
    }
}