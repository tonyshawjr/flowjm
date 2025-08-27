<?php
/**
 * FlowJM Database Connection Class
 * Handles PDO database connections with connection pooling and error handling
 * Optimized for shared hosting environments
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;

        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false, // Disable for shared hosting compatibility
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
            PDO::ATTR_TIMEOUT => 30 // 30 second timeout
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set timezone to UTC for consistency
            $this->pdo->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            } else {
                error_log("Database connection error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
    }

    /**
     * Get PDO instance
     */
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->isConnectionAlive()) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Check if database connection is still alive
     */
    private function isConnectionAlive() {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Execute a prepared statement with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("Query failed: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                error_log("Database query error: " . $e->getMessage() . " SQL: " . $sql);
                throw new Exception("Database query failed");
            }
        }
    }

    /**
     * Execute a SELECT query and return all results
     */
    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT query and return single row
     */
    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Execute INSERT and return last insert ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Execute UPDATE/DELETE and return affected rows
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }

    /**
     * Check if currently in transaction
     */
    public function inTransaction() {
        return $this->getConnection()->inTransaction();
    }

    /**
     * Escape string for use in LIKE queries
     */
    public function escapeLike($string) {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $string);
    }

    /**
     * Build WHERE clause from array of conditions
     */
    public function buildWhere($conditions, &$params = []) {
        if (empty($conditions)) {
            return '';
        }

        $where = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Handle IN conditions
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $where[] = "$field IN ($placeholders)";
                $params = array_merge($params, $value);
            } elseif (strpos($field, ' ') !== false) {
                // Handle operators like 'field >' or 'field LIKE'
                $where[] = $field . ' ?';
                $params[] = $value;
            } else {
                // Handle equality
                $where[] = "$field = ?";
                $params[] = $value;
            }
        }

        return 'WHERE ' . implode(' AND ', $where);
    }

    /**
     * Build ORDER BY clause from array
     */
    public function buildOrderBy($orderBy) {
        if (empty($orderBy)) {
            return '';
        }

        if (is_string($orderBy)) {
            return "ORDER BY $orderBy";
        }

        $orders = [];
        foreach ($orderBy as $field => $direction) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $orders[] = "$field $direction";
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Build LIMIT clause for pagination
     */
    public function buildLimit($page = 1, $perPage = DEFAULT_PAGE_SIZE) {
        $perPage = min($perPage, MAX_PAGE_SIZE);
        $offset = ($page - 1) * $perPage;
        return "LIMIT $offset, $perPage";
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    private function __wakeup() {}
}