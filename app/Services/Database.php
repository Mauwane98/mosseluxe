<?php
/**
 * Database Service - Singleton wrapper for MySQLi connection
 * 
 * Usage:
 *   $db = Database::getInstance();
 *   $conn = $db->getConnection();
 * 
 * Or use the helper function:
 *   $conn = db();
 */

namespace App\Services;

class Database
{
    private static ?Database $instance = null;
    private ?\mysqli $connection = null;
    
    private string $host;
    private string $user;
    private string $password;
    private string $database;
    
    /**
     * Private constructor - use getInstance()
     */
    private function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $this->password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $this->database = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'mosse_luxe_db';
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the MySQLi connection
     */
    public function getConnection(): \mysqli
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Establish database connection
     */
    private function connect(): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $this->connection = new \mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database
            );
            
            $this->connection->set_charset('utf8mb4');
            
        } catch (\mysqli_sql_exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed. Please check your configuration.");
        }
    }
    
    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @param string $types Parameter types (i=int, s=string, d=double, b=blob)
     * @return \mysqli_result|bool
     */
    public function query(string $sql, array $params = [], string $types = ''): \mysqli_result|bool
    {
        $conn = $this->getConnection();
        
        if (empty($params)) {
            return $conn->query($sql);
        }
        
        // Auto-detect types if not provided
        if (empty($types)) {
            $types = $this->detectTypes($params);
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        // For INSERT/UPDATE/DELETE, return the statement result
        if ($result === false) {
            return $stmt->affected_rows > 0;
        }
        
        return $result;
    }
    
    /**
     * Fetch a single row
     */
    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $result = $this->query($sql, $params, $types);
        
        if ($result instanceof \mysqli_result) {
            $row = $result->fetch_assoc();
            $result->close();
            return $row;
        }
        
        return null;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = [], string $types = ''): array
    {
        $result = $this->query($sql, $params, $types);
        
        if ($result instanceof \mysqli_result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->close();
            return $rows;
        }
        
        return [];
    }
    
    /**
     * Get the last insert ID
     */
    public function lastInsertId(): int
    {
        return $this->getConnection()->insert_id;
    }
    
    /**
     * Get affected rows from last query
     */
    public function affectedRows(): int
    {
        return $this->getConnection()->affected_rows;
    }
    
    /**
     * Escape a string for safe SQL usage
     */
    public function escape(string $value): string
    {
        return $this->getConnection()->real_escape_string($value);
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->begin_transaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Auto-detect parameter types
     */
    private function detectTypes(array $params): string
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 's'; // Default to string
            }
        }
        return $types;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 */
function db(): \mysqli
{
    return Database::getInstance()->getConnection();
}
