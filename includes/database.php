<?php
/**
 * Database Singleton Class
 * Handles database connections using PDO with proper error handling
 */
class Database {
    private static $instance = null;
    private $connection;
    private $config;
    private $connected = false;

    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    private function loadConfig(): void {
        // Try to load config from config.php
        if (file_exists(__DIR__ . '/config.php')) {
            require_once __DIR__ . '/config.php';
            $this->config = [
                'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
                'port' => defined('DB_PORT') ? DB_PORT : '3306',
                'dbname' => defined('DB_NAME') ? DB_NAME : 'peercart_db',
                'username' => defined('DB_USER') ? DB_USER : 'root',
                'password' => defined('DB_PASS') ? DB_PASS : '',
                'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
            ];
        } else {
            // Fallback configuration
            $this->config = [
                'host' => 'localhost',
                'port' => '3306',
                'dbname' => 'peercart_db',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ];
        }

        // PDO options
        $this->config['options'] = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 30
        ];
    }

    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            // Test the connection
            $this->connection->query("SELECT 1");
            $this->connected = true;

            app_log("Database connected successfully to {$this->config['dbname']}", 'INFO');

        } catch (PDOException $e) {
            $this->connected = false;
            $errorMessage = "Database connection error: " . $e->getMessage();
            
            // Log the error
            if (function_exists('app_log')) {
                app_log($errorMessage, 'ERROR');
            } else {
                error_log($errorMessage);
            }
            
            // User-friendly error message
            if (defined('APP_ENV') && APP_ENV === 'production') {
                // Show generic error in production
                $this->showErrorPage("Database connection failed. Please try again later.");
            } else {
                // Show detailed error in development
                $this->showErrorPage("Database Error: " . $e->getMessage());
            }
        }
    }

    private function showErrorPage(string $message): void {
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Error - PeerCart</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .error-container {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(10px);
                    padding: 40px;
                    border-radius: 15px;
                    max-width: 600px;
                    text-align: center;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
                }
                h1 { 
                    font-size: 48px; 
                    margin-bottom: 20px;
                    color: #ff6b6b;
                }
                h2 { 
                    font-size: 24px; 
                    margin-bottom: 30px;
                    opacity: 0.9;
                }
                .error-details {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    text-align: left;
                    font-family: monospace;
                }
                .actions {
                    margin-top: 30px;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background: white;
                    color: #667eea;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    margin: 0 10px;
                    transition: transform 0.3s;
                }
                .btn:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                }
                .logo {
                    font-size: 32px;
                    font-weight: bold;
                    margin-bottom: 20px;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="logo">PeerCart</div>
                <h1>⚠️ Database Error</h1>
                <h2>We're experiencing technical difficulties</h2>
                
                <div class="error-details">
                    <strong>Error Details:</strong><br>
                    <?= htmlspecialchars($message) ?>
                </div>
                
                <p>This is usually temporary. Please try:</p>
                <ul style="text-align: left; display: inline-block;">
                    <li>Refreshing the page</li>
                    <li>Coming back in a few minutes</li>
                    <li>Contacting support if the problem persists</li>
                </ul>
                
                <div class="actions">
                    <a href="javascript:location.reload()" class="btn">🔄 Refresh Page</a>
                    <a href="/" class="btn">🏠 Go Home</a>
                </div>
                
                <div style="margin-top: 30px; font-size: 14px; opacity: 0.7;">
                    <p>If you're the site administrator, check your database configuration.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        // Verify connection is still alive
        if (!$this->connected) {
            $this->connect();
        } else {
            try {
                $this->connection->query("SELECT 1");
            } catch (PDOException $e) {
                // Reconnect if connection is lost
                $this->connect();
            }
        }
        
        return $this->connection;
    }

    public function prepare(string $sql): PDOStatement {
        return $this->getConnection()->prepare($sql);
    }

    /**
     * Execute a query and return results
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            app_log("Query error: " . $e->getMessage() . " - SQL: " . $sql, 'ERROR', $params);
            throw $e;
        }
    }

    /**
     * Get a single row
     */
    public function getRow(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all rows
     */
    public function getRows(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single value
     */
    public function getValue(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert data and return last insert ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->prepare($sql);
        $stmt->execute($data);
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Update data
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = :$column";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        // Merge data and where params
        $allParams = array_merge($data, $whereParams);
        
        $stmt = $this->prepare($sql);
        $stmt->execute($allParams);
        
        return $stmt->rowCount();
    }

    /**
     * Delete data
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool {
        return $this->getConnection()->rollBack();
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $tableName): bool {
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
    public function getStats(): array {
        try {
            return [
                'tables' => $this->getValue(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?",
                    [$this->config['dbname']]
                ),
                'size' => $this->getValue(
                    "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 
                     FROM information_schema.tables 
                     WHERE table_schema = ?", 
                    [$this->config['dbname']]
                ),
                'connections' => $this->getValue("SHOW STATUS LIKE 'Threads_connected'"),
                'uptime' => $this->getValue("SHOW STATUS LIKE 'Uptime'")
            ];
        } catch (PDOException $e) {
            return [
                'tables' => 0,
                'size' => 0,
                'connections' => 0,
                'uptime' => 0
            ];
        }
    }

    /**
     * Backup database
     */
    public function backup(string $backupPath): bool {
        try {
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s',
                escapeshellarg($this->config['host']),
                escapeshellarg($this->config['username']),
                escapeshellarg($this->config['password']),
                escapeshellarg($this->config['dbname']),
                escapeshellarg($backupPath)
            );
            
            exec($command, $output, $returnCode);
            
            return $returnCode === 0;
        } catch (Exception $e) {
            app_log("Database backup failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Check database connection
     */
    public function isConnected(): bool {
        return $this->connected;
    }

    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>