<?php
/**
 * FlowJM Authentication Middleware
 * Handles session management, authorization, and security for protected routes
 */

// Prevent direct access
if (!defined('FLOWJM_ROOT')) {
    die('Direct access not permitted');
}

class Auth {
    /**
     * Initialize authentication system
     */
    public static function init() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
            
            session_name(SESSION_NAME);
            session_start();
        }
        
        // Regenerate session ID periodically for security
        self::regenerateSessionId();
        
        // Update activity timestamp
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Regenerate session ID every 30 minutes
     */
    private static function regenerateSessionId() {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function check() {
        self::init();
        
        // Check for session timeout (4 hours)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 14400)) {
            self::logout();
            return false;
        }
        
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function require($redirectUrl = null) {
        if (!self::check()) {
            $redirect = $redirectUrl ?: $_SERVER['REQUEST_URI'];
            redirect('/login.php?redirect=' . urlencode($redirect));
        }
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        if (!self::check()) {
            return false;
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($role, $redirectUrl = '/unauthorized.php') {
        self::require();
        
        if (!self::hasRole($role)) {
            redirect($redirectUrl);
        }
    }
    
    /**
     * Login user
     */
    public static function login($user) {
        self::init();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Update user's last login timestamp
        $userModel = new User();
        $userModel->updateLastLogin($user['id']);
        
        // Log successful login
        self::logActivity('login', 'User logged in successfully');
        
        return true;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::init();
        
        // Log logout activity
        if (self::check()) {
            self::logActivity('logout', 'User logged out');
        }
        
        // Clear session data
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Get current user data
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        // Use null coalescing operator to prevent undefined index notices
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? '',
            'name' => $_SESSION['user_name'] ?? 'User',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }
    
    /**
     * Get user ID
     */
    public static function id() {
        return self::check() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Generate and store CSRF token
     */
    public static function generateCsrfToken() {
        self::init();
        
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        self::init();
        
        if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !$token) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Require valid CSRF token
     */
    public static function requireCsrfToken() {
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!self::verifyCsrfToken($token)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                json_response(['error' => 'Invalid CSRF token'], 403);
            } else {
                die('CSRF token validation failed');
            }
        }
    }
    
    /**
     * Check for brute force attempts
     */
    public static function checkBruteForce($identifier, $maxAttempts = 5, $timeWindow = 900) {
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }
        
        $attempts = $_SESSION['failed_attempts'][$identifier] ?? [];
        $recentAttempts = array_filter($attempts, function($time) use ($timeWindow) {
            return (time() - $time) < $timeWindow;
        });
        
        return count($recentAttempts) >= $maxAttempts;
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedAttempt($identifier) {
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }
        
        if (!isset($_SESSION['failed_attempts'][$identifier])) {
            $_SESSION['failed_attempts'][$identifier] = [];
        }
        
        $_SESSION['failed_attempts'][$identifier][] = time();
        
        // Keep only last 10 attempts
        $_SESSION['failed_attempts'][$identifier] = array_slice(
            $_SESSION['failed_attempts'][$identifier], -10
        );
    }
    
    /**
     * Clear failed attempts for identifier
     */
    public static function clearFailedAttempts($identifier) {
        if (isset($_SESSION['failed_attempts'][$identifier])) {
            unset($_SESSION['failed_attempts'][$identifier]);
        }
    }
    
    /**
     * Log security activity
     */
    public static function logActivity($action, $details = '', $userId = null) {
        $userId = $userId ?: self::id();
        $ip = get_client_ip();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'session_id' => session_id()
        ];
        
        // Log to file
        $logMessage = sprintf(
            "[%s] User:%s Action:%s IP:%s - %s",
            $logData['timestamp'],
            $userId ?: 'guest',
            $action,
            $ip,
            $details
        );
        
        error_log($logMessage . PHP_EOL, 3, FLOWJM_ROOT . '/logs/auth.log');
    }
    
    /**
     * Generate secure password reset token
     */
    public static function generatePasswordResetToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store in database
        $db = Database::getInstance();
        $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP";
        
        $db->execute($sql, [$userId, password_hash($token, PASSWORD_DEFAULT), $expires]);
        
        return $token;
    }
    
    /**
     * Verify password reset token
     */
    public static function verifyPasswordResetToken($token) {
        $db = Database::getInstance();
        $sql = "SELECT pr.*, u.email, u.name 
                FROM password_resets pr 
                JOIN users u ON pr.user_id = u.id 
                WHERE pr.expires_at > NOW() 
                ORDER BY pr.created_at DESC";
        
        $resets = $db->select($sql);
        
        foreach ($resets as $reset) {
            if (password_verify($token, $reset['token'])) {
                return $reset;
            }
        }
        
        return false;
    }
    
    /**
     * Delete password reset token
     */
    public static function deletePasswordResetToken($userId) {
        $db = Database::getInstance();
        $sql = "DELETE FROM password_resets WHERE user_id = ?";
        return $db->execute($sql, [$userId]);
    }
    
    /**
     * Clean expired password reset tokens
     */
    public static function cleanExpiredTokens() {
        $db = Database::getInstance();
        $sql = "DELETE FROM password_resets WHERE expires_at < NOW()";
        return $db->execute($sql);
    }
}