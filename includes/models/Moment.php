<?php
/**
 * FlowJM Moment Model
 * Handles individual updates/events within journeys forming the narrative timeline
 */

class Moment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new moment
     */
    public function create($data) {
        $sql = "INSERT INTO moments (journey_id, user_id, title, content, moment_type, amount, visibility) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['journey_id'],
            $data['user_id'],
            $data['title'] ?? '',
            $data['content'],
            $data['moment_type'] ?? 'update',
            $data['amount'] ?? null,
            $data['visibility'] ?? 'private'
        ];
        
        $momentId = $this->db->insert($sql, $params);
        
        if ($momentId) {
            // Update parent journey's last moment timestamp
            $journey = new Journey();
            $journey->updateLastMoment($data['journey_id']);
            $journey->updatePulseStatus($data['journey_id']);
        }
        
        return $momentId;
    }

    /**
     * Find moment by ID
     */
    public function findById($id) {
        $sql = "SELECT m.*, j.title as journey_title, u.name as user_name
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                WHERE m.id = ?";
        
        return $this->db->selectOne($sql, [$id]);
    }

    /**
     * Get moments for a journey
     */
    public function getByJourneyId($journeyId, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $sql = "SELECT m.*, u.name as user_name,
                COUNT(fn.id) as fieldnote_count
                FROM moments m
                JOIN users u ON m.user_id = u.id
                LEFT JOIN fieldnotes fn ON m.id = fn.moment_id
                WHERE m.journey_id = ?
                GROUP BY m.id
                ORDER BY m.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$journeyId]);
    }

    /**
     * Get recent moments for user (stack feed)
     */
    public function getRecentByUserId($userId, $page = 1, $perPage = DEFAULT_PAGE_SIZE, $type = null) {
        $params = [$userId];
        $sql = "SELECT m.*, j.title as journey_title, j.client_name,
                u.name as user_name,
                COUNT(fn.id) as fieldnote_count
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                LEFT JOIN fieldnotes fn ON m.id = fn.moment_id
                WHERE m.user_id = ?";
        
        if ($type) {
            $sql .= " AND m.moment_type = ?";
            $params[] = $type;
        }
        
        $sql .= " GROUP BY m.id ORDER BY m.created_at DESC";
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Get recent moments after a specific ID for infinite scroll
     */
    public function getRecentByUserIdAfter($userId, $lastId, $perPage = DEFAULT_PAGE_SIZE, $type = null, $journeyId = 0) {
        $params = [$userId, $lastId];
        $sql = "SELECT m.*, j.title as journey_title, j.client_name,
                       u.name as user_name
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                WHERE m.user_id = ? AND m.id < ?";
        
        if ($type) {
            $sql .= " AND m.moment_type = ?";
            $params[] = $type;
        }
        
        if ($journeyId > 0) {
            $sql .= " AND m.journey_id = ?";
            $params[] = $journeyId;
        }
        
        $sql .= " ORDER BY m.created_at DESC";
        $sql .= " LIMIT " . intval($perPage);
        
        return $this->db->select($sql, $params);
    }

    /**
     * Get moments by type
     */
    public function getByType($userId, $momentType, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $sql = "SELECT m.*, j.title as journey_title, u.name as user_name
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                WHERE m.user_id = ? AND m.moment_type = ?
                ORDER BY m.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$userId, $momentType]);
    }

    /**
     * Update moment
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'content', 'moment_type', 'amount', 'visibility'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE moments SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Delete moment
     */
    public function delete($id) {
        // First get the journey_id for pulse update
        $moment = $this->findById($id);
        if (!$moment) return false;
        
        $sql = "DELETE FROM moments WHERE id = ?";
        $result = $this->db->execute($sql, [$id]) > 0;
        
        if ($result) {
            // Update parent journey's pulse status
            $journey = new Journey();
            $journey->updatePulseStatus($moment['journey_id']);
        }
        
        return $result;
    }

    /**
     * Search moments
     */
    public function search($userId, $query, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $searchTerm = '%' . $this->db->escapeLike($query) . '%';
        
        $sql = "SELECT m.*, j.title as journey_title, u.name as user_name,
                MATCH(m.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                WHERE m.user_id = ? 
                AND (MATCH(m.content) AGAINST(? IN NATURAL LANGUAGE MODE) 
                     OR m.content LIKE ?)
                ORDER BY relevance DESC, m.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$query, $userId, $query, $searchTerm]);
    }

    /**
     * Get moment statistics
     */
    public function getStats($userId) {
        $sql = "SELECT 
            COUNT(*) as total_moments,
            COUNT(CASE WHEN moment_type = 'update' THEN 1 END) as updates,
            COUNT(CASE WHEN moment_type = 'milestone' THEN 1 END) as milestones,
            COUNT(CASE WHEN moment_type = 'payment' THEN 1 END) as payments,
            COUNT(CASE WHEN moment_type = 'delivery' THEN 1 END) as deliveries,
            COUNT(CASE WHEN moment_type = 'feedback' THEN 1 END) as feedback,
            COUNT(CASE WHEN moment_type = 'note' THEN 1 END) as notes,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as this_week,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as this_month
        FROM moments 
        WHERE user_id = ?";
        
        return $this->db->selectOne($sql, [$userId]);
    }

    /**
     * Get moments with attachments
     */
    public function getWithAttachments($userId, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $sql = "SELECT m.*, j.title as journey_title, u.name as user_name,
                COUNT(a.id) as attachment_count
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                LEFT JOIN attachments a ON m.id = a.moment_id
                WHERE m.user_id = ?
                GROUP BY m.id
                HAVING attachment_count > 0
                ORDER BY m.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$userId]);
    }

    /**
     * Get moments by visibility level
     */
    public function getByVisibility($userId, $visibility, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $sql = "SELECT m.*, j.title as journey_title, u.name as user_name
                FROM moments m
                JOIN journeys j ON m.journey_id = j.id
                JOIN users u ON m.user_id = u.id
                WHERE m.user_id = ? AND m.visibility = ?
                ORDER BY m.created_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$userId, $visibility]);
    }

    /**
     * Get moment activity timeline for journey
     */
    public function getJourneyTimeline($journeyId) {
        $sql = "SELECT m.*, u.name as user_name,
                DATE(m.created_at) as activity_date,
                COUNT(*) OVER (PARTITION BY DATE(m.created_at)) as daily_count
                FROM moments m
                JOIN users u ON m.user_id = u.id
                WHERE m.journey_id = ?
                ORDER BY m.created_at DESC";
        
        return $this->db->select($sql, [$journeyId]);
    }

    /**
     * Validate moment data
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['content']) || strlen(trim($data['content'])) < 3) {
            $errors['content'] = 'Content must be at least 3 characters long';
        }
        
        if (isset($data['moment_type']) && 
            !in_array($data['moment_type'], ['update', 'milestone', 'payment', 'delivery', 'feedback', 'note'])) {
            $errors['moment_type'] = 'Invalid moment type specified';
        }
        
        if (isset($data['visibility']) && 
            !in_array($data['visibility'], ['private', 'team', 'client'])) {
            $errors['visibility'] = 'Invalid visibility level specified';
        }
        
        if (empty($data['journey_id']) || !is_numeric($data['journey_id'])) {
            $errors['journey_id'] = 'Valid journey is required';
        }
        
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            $errors['user_id'] = 'Valid user is required';
        }
        
        return $errors;
    }

    /**
     * Get moment type color for UI
     */
    public function getTypeColor($momentType) {
        return MOMENT_TYPE_COLORS[$momentType] ?? 'gray';
    }

    /**
     * Format moment content for display (basic markdown-like formatting)
     */
    public function formatContent($content) {
        // Convert line breaks
        $content = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
        
        // Simple bold formatting **text**
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        
        // Simple italic formatting *text*
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        
        // Convert URLs to links
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>',
            $content
        );
        
        return $content;
    }
}