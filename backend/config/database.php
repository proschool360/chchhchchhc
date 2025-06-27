<?php
/**
 * Database Connection Class
 * Handles database connections and operations for HRMS
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    private $port;
    
    private function __construct() {
        $this->host = DB_HOST;
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        $this->port = DB_PORT;
        
        $this->connect();
    }
    
    /**
     * Get singleton instance of Database
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
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            // Add MySQL specific options only if using MySQL
            if (strpos($dsn, 'mysql:') === 0) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$this->charset}";
            }
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            if (SQL_DEBUG && APP_ENV === 'development') {
                $this->log('Database connection established successfully');
            }
            
        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }
        
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Execute a SELECT query
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            if (SQL_DEBUG) {
                $this->logQuery($query, $params);
            }
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('Select query failed: ' . $e->getMessage() . ' Query: ' . $query);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute a SELECT query and return single row
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            if (SQL_DEBUG) {
                $this->logQuery($query, $params);
            }
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError('Select one query failed: ' . $e->getMessage() . ' Query: ' . $query);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute an INSERT query
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->prepare($query);
            $result = $stmt->execute($params);
            
            if (SQL_DEBUG) {
                $this->logQuery($query, $params);
            }
            
            return $result ? $this->connection->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->logError('Insert query failed: ' . $e->getMessage() . ' Query: ' . $query);
            throw new Exception('Database insert failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute an UPDATE query
     */
    public function update($query, $params = []) {
        try {
            $stmt = $this->prepare($query);
            $result = $stmt->execute($params);
            
            if (SQL_DEBUG) {
                $this->logQuery($query, $params);
            }
            
            return $result ? $stmt->rowCount() : false;
        } catch (PDOException $e) {
            $this->logError('Update query failed: ' . $e->getMessage() . ' Query: ' . $query);
            throw new Exception('Database update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute a DELETE query
     */
    public function delete($query, $params = []) {
        try {
            $stmt = $this->prepare($query);
            $result = $stmt->execute($params);
            
            if (SQL_DEBUG) {
                $this->logQuery($query, $params);
            }
            
            return $result ? $stmt->rowCount() : false;
        } catch (PDOException $e) {
            $this->logError('Delete query failed: ' . $e->getMessage() . ' Query: ' . $query);
            throw new Exception('Database delete failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute a custom query
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->prepare($query);
            $result = $stmt->execute($params);
            
            if (SQL_DEBUG) {
                $this->logQuery($query, $params);
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->logError('Execute query failed: ' . $e->getMessage() . ' Query: ' . $query);
            throw new Exception('Database execution failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare a statement
     */
    public function prepare($query) {
        return $this->getConnection()->prepare($query);
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
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Count records
     */
    public function count($table, $where = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM `{$table}`";
        if (!empty($where)) {
            $query .= " WHERE {$where}";
        }
        
        $result = $this->selectOne($query, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Get paginated results
     */
    public function paginate($query, $params = [], $page = 1, $perPage = null) {
        if ($perPage === null) {
            $perPage = DEFAULT_PAGE_SIZE;
        }
        
        $perPage = min($perPage, MAX_PAGE_SIZE);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
        $totalResult = $this->selectOne($countQuery, $params);
        $total = $totalResult ? (int)$totalResult['total'] : 0;
        
        // Get paginated data
        $paginatedQuery = $query . " LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->select($paginatedQuery, $params);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Escape string for LIKE queries
     */
    public function escapeLike($string) {
        return str_replace(['%', '_'], ['\\%', '\\_'], $string);
    }
    
    /**
     * Build WHERE clause from array
     */
    public function buildWhere($conditions, $operator = 'AND') {
        if (empty($conditions)) {
            return ['', []];
        }
        
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $where[] = "`{$field}` IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $where[] = "`{$field}` = ?";
                $params[] = $value;
            }
        }
        
        return [implode(" {$operator} ", $where), $params];
    }
    
    /**
     * Log query for debugging
     */
    private function logQuery($query, $params = []) {
        if (SQL_DEBUG && APP_ENV === 'development') {
            $logMessage = date('Y-m-d H:i:s') . " - Query: {$query}";
            if (!empty($params)) {
                $logMessage .= " | Params: " . json_encode($params);
            }
            $this->log($logMessage);
        }
    }
    
    /**
     * Log message
     */
    private function log($message) {
        $logFile = LOG_PATH . 'database_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $message . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log error
     */
    private function logError($message) {
        $logFile = LOG_PATH . 'database_error_' . date('Y-m-d') . '.log';
        $logMessage = date('Y-m-d H:i:s') . " - " . $message . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}

// Helper function to get database instance
function getDB() {
    return Database::getInstance();
}

?>