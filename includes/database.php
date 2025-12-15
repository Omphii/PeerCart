<?php
/**
 * Database Singleton Class
 * Handles database connections using PDO with proper error handling
 */
class Database {
    private static $instance = null;
    private $connection;
    private $config;

    private function __construct() {
        // Database configuration
        $this->config = [
            'host' => 'localhost',
            'dbname' => 'peercart',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // Better for most web applications
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];

        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            // Test the connection
            $this->connection->query("SELECT 1");

        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            
            // User-friendly error message
            if (getenv('APP_ENV') === 'production') {
                die("Database connection failed. Please try again later.");
            } else {
                die("Database connection error: " . $e->getMessage());
            }
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        // Verify connection is still alive
        try {
            $this->connection->query("SELECT 1");
        } catch (PDOException $e) {
            // Reconnect if connection is lost
            $this->connect();
        }
        
        return $this->connection;
    }

    public function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }

    /**
     * Execute a query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Get a single row
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Get all rows
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single value
     */
    public function getValue($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->prepare($sql);
        $stmt->execute($data);
        
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = :$column";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $stmt = $this->prepare($sql);
        $stmt->execute(array_merge($data, $whereParams));
        
        return $stmt->rowCount();
    }

    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        try {
            $result = $this->getValue(
                "SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = ? AND table_name = ?",
                [$this->config['dbname'], $tableName]
            );
            return $result > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get database statistics
     */
    public function getStats() {
        return [
            'tables' => $this->getValue("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?", [$this->config['dbname']]),
            'size' => $this->getValue(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 
                 FROM information_schema.tables 
                 WHERE table_schema = ?", 
                [$this->config['dbname']]
            )
        ];
    }

    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {}
}
?>