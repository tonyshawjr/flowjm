<?php
/**
 * FlowJM User Model
 * Handles user authentication, management, and CRUD operations
 */

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        $sql = "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)";
        $params = [
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['name'],
            $data['role'] ?? 'user'
        ];
        
        return $this->db->insert($sql, $params);
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT id, email, name, role, created_at, updated_at, last_login FROM users WHERE id = ?";
        return $this->db->selectOne($sql, [$id]);
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT id, email, name, role, created_at, updated_at, last_login FROM users WHERE email = ?";
        return $this->db->selectOne($sql, [$email]);
    }

    /**
     * Find user by email with password hash (for authentication)
     */
    public function findByEmailWithPassword($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->selectOne($sql, [$email]);
    }

    /**
     * Update user information
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        // Build dynamic update query
        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }
        
        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }
        
        if (isset($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * Delete user (soft delete by setting archived status)
     */
    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    /**
     * Authenticate user with email and password
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmailWithPassword($email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Return user without password hash
        unset($user['password_hash']);
        return $user;
    }

    /**
     * Check if email already exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $perPage = DEFAULT_PAGE_SIZE, $search = '') {
        $params = [];
        $sql = "SELECT id, email, name, role, created_at, last_login FROM users";
        
        if ($search) {
            $sql .= " WHERE name LIKE ? OR email LIKE ?";
            $searchTerm = '%' . $this->db->escapeLike($search) . '%';
            $params = [$searchTerm, $searchTerm];
        }
        
        $sql .= " ORDER BY created_at DESC";
        $sql .= " " . $this->db->buildLimit($page, $perPage);
        
        return $this->db->select($sql, $params);
    }

    /**
     * Get total user count
     */
    public function getTotalCount($search = '') {
        $params = [];
        $sql = "SELECT COUNT(*) as count FROM users";
        
        if ($search) {
            $sql .= " WHERE name LIKE ? OR email LIKE ?";
            $searchTerm = '%' . $this->db->escapeLike($search) . '%';
            $params = [$searchTerm, $searchTerm];
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result['count'];
    }

    /**
     * Get user statistics
     */
    public function getStats($userId) {
        $sql = "SELECT 
            u.name,
            u.email,
            u.created_at,
            u.last_login,
            COUNT(DISTINCT j.id) as total_journeys,
            COUNT(DISTINCT CASE WHEN j.status = 'active' THEN j.id END) as active_journeys,
            COUNT(DISTINCT m.id) as total_moments,
            COUNT(DISTINCT CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN m.id END) as recent_moments
        FROM users u
        LEFT JOIN journeys j ON u.id = j.user_id
        LEFT JOIN moments m ON u.id = m.user_id
        WHERE u.id = ?
        GROUP BY u.id";
        
        return $this->db->selectOne($sql, [$userId]);
    }

    /**
     * Validate user data
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name']) || strlen($data['name']) < 2) {
                $errors['name'] = 'Name must be at least 2 characters long';
            }
        }
        
        if (!$isUpdate || isset($data['email'])) {
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Valid email address is required';
            } elseif ($this->emailExists($data['email'], $data['id'] ?? null)) {
                $errors['email'] = 'Email address is already in use';
            }
        }
        
        if (!$isUpdate || isset($data['password'])) {
            if (!$isUpdate && (empty($data['password']) || strlen($data['password']) < PASSWORD_MIN_LENGTH)) {
                $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
            } elseif (isset($data['password']) && strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
            }
        }
        
        if (isset($data['role']) && !in_array($data['role'], ['admin', 'user', 'client'])) {
            $errors['role'] = 'Invalid role specified';
        }
        
        return $errors;
    }
}