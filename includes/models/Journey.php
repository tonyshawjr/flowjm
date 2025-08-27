<?php
/**
 * FlowJM Journey Model
 * Handles client projects/engagements with integrated payment tracking
 */

class Journey {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new journey
     */
    public function create($data) {
        $sql = "INSERT INTO journeys (user_id, title, client_name, description, sale_amount, start_date, due_date, status, in_circle) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['user_id'],
            $data['title'],
            $data['client_name'] ?? null,
            $data['description'] ?? null,
            $data['sale_amount'] ?? 0.00,
            $data['start_date'] ?? null,
            $data['due_date'] ?? null,
            $data['status'] ?? 'active',
            $data['in_circle'] ?? false
        ];
        
        return $this->db->insert($sql, $params);
    }

    /**
     * Find journey by ID
     */
    public function findById($id) {
        $sql = "SELECT j.*, 
                COUNT(m.id) as moment_count,
                COALESCE(j.sale_amount - j.paid_amount, 0) as balance_due
                FROM journeys j
                LEFT JOIN moments m ON j.id = m.journey_id
                WHERE j.id = ?
                GROUP BY j.id";
        
        return $this->db->selectOne($sql, [$id]);
    }

    /**
     * Get journeys for a user
     */
    public function getByUserId($userId, $status = null, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $params = [$userId];
        $sql = "SELECT j.*, 
                COUNT(m.id) as moment_count,
                MAX(m.created_at) as last_moment_at,
                COALESCE(j.sale_amount - j.paid_amount, 0) as balance_due
                FROM journeys j
                LEFT JOIN moments m ON j.id = m.journey_id
                WHERE j.user_id = ?";
        
        if ($status) {
            $sql .= " AND j.status = ?";
            $params[] = $status;
        }
        
        $sql .= " GROUP BY j.id ORDER BY j.updated_at DESC";
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, $params);
    }

    /**
     * Get journeys in circle (priority journeys)
     */
    public function getCircleJourneys($userId) {
        $sql = "SELECT j.*, 
                COUNT(m.id) as moment_count,
                MAX(m.created_at) as last_moment_at,
                COALESCE(j.sale_amount - j.paid_amount, 0) as balance_due
                FROM journeys j
                LEFT JOIN moments m ON j.id = m.journey_id
                WHERE j.user_id = ? AND j.in_circle = 1 AND j.status = 'active'
                GROUP BY j.id 
                ORDER BY j.pulse_status DESC, j.due_date ASC, j.updated_at DESC
                LIMIT 7";
        
        return $this->db->select($sql, [$userId]);
    }

    /**
     * Update journey
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'client_name', 'description', 'sale_amount', 'paid_amount', 
                         'start_date', 'due_date', 'status', 'in_circle'];
        
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
        $sql = "UPDATE journeys SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $result = $this->db->execute($sql, $params) > 0;
        
        // Update pulse status if journey was modified
        if ($result) {
            $this->updatePulseStatus($id);
        }
        
        return $result;
    }

    /**
     * Update journey's last moment timestamp
     */
    public function updateLastMoment($journeyId) {
        $sql = "UPDATE journeys SET last_moment_at = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->execute($sql, [$journeyId]);
    }

    /**
     * Update pulse status based on activity and deadlines
     */
    public function updatePulseStatus($journeyId) {
        $journey = $this->findById($journeyId);
        if (!$journey) return false;
        
        $status = 'healthy';
        $now = new DateTime();
        
        // Check for overdue
        if ($journey['due_date'] && new DateTime($journey['due_date']) < $now) {
            $status = 'critical';
        }
        // Check for no activity
        elseif ($journey['last_moment_at']) {
            $lastActivity = new DateTime($journey['last_moment_at']);
            $daysSince = $now->diff($lastActivity)->days;
            
            if ($daysSince > 7) {
                $status = 'critical';
            } elseif ($daysSince > 3) {
                $status = 'warning';
            }
        }
        // Check for approaching deadline
        elseif ($journey['due_date']) {
            $dueDate = new DateTime($journey['due_date']);
            $daysUntil = $now->diff($dueDate)->days;
            
            if ($daysUntil <= 3) {
                $status = 'warning';
            }
        }
        
        $sql = "UPDATE journeys SET pulse_status = ? WHERE id = ?";
        return $this->db->execute($sql, [$status, $journeyId]);
    }

    /**
     * Archive journey (soft delete)
     */
    public function archive($id) {
        $sql = "UPDATE journeys SET status = 'archived', archived_at = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    /**
     * Delete journey permanently
     */
    public function delete($id) {
        $sql = "DELETE FROM journeys WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    /**
     * Get journey statistics for dashboard
     */
    public function getStats($userId) {
        $sql = "SELECT 
            COUNT(*) as total_journeys,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_journeys,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_journeys,
            COUNT(CASE WHEN in_circle = 1 AND status = 'active' THEN 1 END) as circle_journeys,
            COUNT(CASE WHEN pulse_status = 'critical' THEN 1 END) as critical_journeys,
            COUNT(CASE WHEN pulse_status = 'warning' THEN 1 END) as warning_journeys,
            SUM(sale_amount) as total_sales,
            SUM(paid_amount) as total_paid,
            SUM(CASE WHEN status = 'active' THEN sale_amount - paid_amount ELSE 0 END) as outstanding_balance
        FROM journeys 
        WHERE user_id = ?";
        
        return $this->db->selectOne($sql, [$userId]);
    }

    /**
     * Search journeys
     */
    public function search($userId, $query, $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $searchTerm = '%' . $this->db->escapeLike($query) . '%';
        
        $sql = "SELECT j.*, 
                COUNT(m.id) as moment_count,
                MAX(m.created_at) as last_moment_at,
                COALESCE(j.sale_amount - j.paid_amount, 0) as balance_due
                FROM journeys j
                LEFT JOIN moments m ON j.id = m.journey_id
                WHERE j.user_id = ? 
                AND (j.title LIKE ? OR j.client_name LIKE ? OR j.description LIKE ?)
                GROUP BY j.id 
                ORDER BY j.updated_at DESC";
        
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, [$userId, $searchTerm, $searchTerm, $searchTerm]);
    }

    /**
     * Get overdue journeys
     */
    public function getOverdue($userId) {
        $sql = "SELECT j.*, 
                COUNT(m.id) as moment_count,
                DATEDIFF(CURRENT_DATE, j.due_date) as days_overdue
                FROM journeys j
                LEFT JOIN moments m ON j.id = m.journey_id
                WHERE j.user_id = ? 
                AND j.status = 'active' 
                AND j.due_date < CURRENT_DATE
                GROUP BY j.id 
                ORDER BY j.due_date ASC";
        
        return $this->db->select($sql, [$userId]);
    }

    /**
     * Validate journey data
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['title']) || strlen(trim($data['title'])) < 3) {
            $errors['title'] = 'Title must be at least 3 characters long';
        }
        
        if (isset($data['sale_amount']) && (!is_numeric($data['sale_amount']) || $data['sale_amount'] < 0)) {
            $errors['sale_amount'] = 'Sale amount must be a positive number';
        }
        
        if (isset($data['paid_amount']) && (!is_numeric($data['paid_amount']) || $data['paid_amount'] < 0)) {
            $errors['paid_amount'] = 'Paid amount must be a positive number';
        }
        
        if (isset($data['paid_amount']) && isset($data['sale_amount']) && $data['paid_amount'] > $data['sale_amount']) {
            $errors['paid_amount'] = 'Paid amount cannot exceed sale amount';
        }
        
        if (isset($data['start_date']) && $data['start_date'] && !$this->isValidDate($data['start_date'])) {
            $errors['start_date'] = 'Invalid start date format';
        }
        
        if (isset($data['due_date']) && $data['due_date'] && !$this->isValidDate($data['due_date'])) {
            $errors['due_date'] = 'Invalid due date format';
        }
        
        if (isset($data['start_date']) && isset($data['due_date']) && 
            $data['start_date'] && $data['due_date'] && 
            $data['start_date'] > $data['due_date']) {
            $errors['due_date'] = 'Due date must be after start date';
        }
        
        if (isset($data['status']) && !in_array($data['status'], ['active', 'completed', 'archived', 'on_hold'])) {
            $errors['status'] = 'Invalid status specified';
        }
        
        return $errors;
    }

    /**
     * Check if date string is valid
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}