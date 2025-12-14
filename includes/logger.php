<?php
/**
 * PeerCart Logger Class
 * Handles database and file logging
 */
class Logger {
    private static $instance = null;
    private $db;
    private $logToDatabase = true;
    private $logToFile = true;
    private $logFile;
    private $maxFileSize = 10485760; // 10MB
    private $logLevel = 'INFO';
    
    private function __construct() {
        $this->initialize();
    }
    
    private function initialize(): void {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            $this->logToDatabase = false;
            $this->logToFile("Failed to get database connection for logging: " . $e->getMessage());
        }
        
        $this->logFile = defined('LOG_FILE') ? LOG_FILE : ROOT_PATH . '/logs/app.log';
        $this->logLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Rotate log file if it's too large
        $this->rotateLogIfNeeded();
    }
    
    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }
    
    /**
     * Check if should log based on level
     */
    private function shouldLog(string $level): bool {
        $levels = ['DEBUG' => 1, 'INFO' => 2, 'WARNING' => 3, 'ERROR' => 4, 'CRITICAL' => 5];
        
        $currentLevel = strtoupper($this->logLevel);
        $checkLevel = strtoupper($level);
        
        return isset($levels[$checkLevel]) && 
               isset($levels[$currentLevel]) && 
               $levels[$checkLevel] >= $levels[$currentLevel];
    }
    
    /**
     * Log an error
     */
    public function error(string $message, array $context = [], ?int $userId = null): void {
        $this->log('ERROR', 'error', $message, $context, $userId);
    }
    
    /**
     * Log a warning
     */
    public function warning(string $message, array $context = [], ?int $userId = null): void {
        $this->log('WARNING', 'warning', $message, $context, $userId);
    }
    
    /**
     * Log info
     */
    public function info(string $message, array $context = [], ?int $userId = null): void {
        $this->log('INFO', 'info', $message, $context, $userId);
    }
    
    /**
     * Log debug info
     */
    public function debug(string $message, array $context = [], ?int $userId = null): void {
        $this->log('DEBUG', 'debug', $message, $context, $userId);
    }
    
    /**
     * Log security events
     */
    public function security(string $message, array $context = [], ?int $userId = null): void {
        $this->log('SECURITY', 'alert', $message, $context, $userId);
    }
    
    /**
     * Log critical events
     */
    public function critical(string $message, array $context = [], ?int $userId = null): void {
        $this->log('CRITICAL', 'critical', $message, $context, $userId);
    }
    
    /**
     * Main logging method
     */
    private function log(string $type, string $level, string $message, array $context, ?int $userId): void {
        // Check if we should log this level
        if (!$this->shouldLog($type)) {
            return;
        }
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? $backtrace[1] ?? $backtrace[0] ?? [];
        
        $logData = [
            'log_type' => $type,
            'log_level' => $level,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null,
            'source_file' => $caller['file'] ?? null,
            'source_line' => $caller['line'] ?? null,
            'source_function' => $caller['function'] ?? null,
            'source_class' => $caller['class'] ?? null,
            'user_id' => $userId,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_url' => $_SERVER['REQUEST_URI'] ?? null,
            'request_data' => $this->getRequestData(),
            'server_name' => $_SERVER['SERVER_NAME'] ?? null,
            'trace' => json_encode($backtrace, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Log to database
        if ($this->logToDatabase) {
            $this->logToDatabase($logData);
        }
        
        // Log to file
        if ($this->logToFile) {
            $this->logToFile($logData);
        }
    }
    
    /**
     * Log to database
     */
    private function logToDatabase(array $data): void {
        try {
            // Check if logs table exists
            if (!$this->tableExists('logs')) {
                $this->createLogsTable();
            }
            
            $sql = "INSERT INTO logs (
                log_type, log_level, message, context, 
                source_file, source_line, source_function, source_class,
                user_id, user_ip, user_agent,
                request_method, request_url, request_data,
                server_name, trace, created_at
            ) VALUES (
                :log_type, :log_level, :message, :context, 
                :source_file, :source_line, :source_function, :source_class,
                :user_id, :user_ip, :user_agent,
                :request_method, :request_url, :request_data,
                :server_name, :trace, :created_at
            )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            
        } catch (Exception $e) {
            // Fallback to file logging if database fails
            $this->logToFileOnly("Database logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create logs table if it doesn't exist
     */
    private function createLogsTable(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                log_type VARCHAR(20) NOT NULL,
                log_level VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                context TEXT,
                source_file VARCHAR(255),
                source_line INT,
                source_function VARCHAR(100),
                source_class VARCHAR(100),
                user_id INT,
                user_ip VARCHAR(45),
                user_agent TEXT,
                request_method VARCHAR(10),
                request_url TEXT,
                request_data TEXT,
                server_name VARCHAR(255),
                trace TEXT,
                created_at DATETIME NOT NULL,
                INDEX idx_log_type (log_type),
                INDEX idx_created_at (created_at),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            $this->db->exec($sql);
        } catch (Exception $e) {
            $this->logToFileOnly("Failed to create logs table: " . $e->getMessage());
        }
    }
    
    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool {
        try {
            $sql = "SELECT COUNT(*) FROM information_schema.tables 
                    WHERE table_schema = DATABASE() AND table_name = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tableName]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Log to file
     */
    private function logToFile(array $data): void {
        $timestamp = date('Y-m-d H:i:s');
        $logLevel = str_pad($data['log_type'], 8);
        
        $logLine = sprintf(
            "[%s] %s: %s | File: %s:%s | User: %s | IP: %s | URL: %s %s\n",
            $timestamp,
            $logLevel,
            $data['message'],
            basename($data['source_file'] ?? 'unknown'),
            $data['source_line'] ?? '0',
            $data['user_id'] ?? 'guest',
            $data['user_ip'] ?? 'unknown',
            $data['request_method'] ?? 'unknown',
            $data['request_url'] ?? 'unknown'
        );
        
        if (!empty($data['context'])) {
            $context = json_decode($data['context'], true);
            if ($context) {
                $logLine .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
            }
        }
        
        $this->writeToFile($logLine);
    }
    
    /**
     * File-only logging (used when database fails)
     */
    private function logToFileOnly(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[$timestamp] LOGGER_ERROR: $message\n";
        $this->writeToFile($logLine);
    }
    
    /**
     * Write to log file
     */
    private function writeToFile(string $logLine): void {
        if ($this->logFile && is_writable(dirname($this->logFile))) {
            @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Rotate log file if it's too large
     */
    private function rotateLogIfNeeded(): void {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $backupFile = $this->logFile . '.' . date('Y-m-d-His');
            @rename($this->logFile, $backupFile);
            
            // Compress old backup
            if (function_exists('gzcompress')) {
                $compressedFile = $backupFile . '.gz';
                $data = file_get_contents($backupFile);
                $compressed = gzcompress($data, 9);
                file_put_contents($compressedFile, $compressed);
                @unlink($backupFile);
            }
        }
    }
    
    /**
     * Get sanitized request data
     */
    private function getRequestData(): ?string {
        $data = [];
        
        // Get GET data
        if (!empty($_GET)) {
            $getData = $_GET;
            unset($getData['password'], $getData['confirm_password'], $getData['credit_card'], $getData['cvv']);
            $data['GET'] = $getData;
        }
        
        // Get POST data (sanitize sensitive fields)
        if (!empty($_POST)) {
            $postData = $_POST;
            
            // List of sensitive fields to redact
            $sensitiveFields = [
                'password', 'confirm_password', 'credit_card', 'cvv',
                'card_number', 'card_expiry', 'card_cvc', 'ssn',
                'secret', 'api_key', 'token', 'auth_token'
            ];
            
            foreach ($sensitiveFields as $field) {
                if (isset($postData[$field])) {
                    $postData[$field] = '***REDACTED***';
                }
            }
            
            $data['POST'] = $postData;
        }
        
        // Get FILES data (just names)
        if (!empty($_FILES)) {
            $data['FILES'] = array_keys($_FILES);
        }
        
        // Get COOKIE data (excluding session)
        if (!empty($_COOKIE)) {
            $cookieData = $_COOKIE;
            unset($cookieData[session_name()]);
            $data['COOKIE'] = $cookieData;
        }
        
        return !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs(int $limit = 100, ?string $type = null): array {
        try {
            $sql = "SELECT * FROM logs";
            $params = [];
            
            if ($type) {
                $sql .= " WHERE log_type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logToFileOnly("Failed to get logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear old logs (keep only last 30 days)
     */
    public function cleanupOldLogs(int $days = 30): int {
        try {
            $sql = "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            
            $deleted = $stmt->rowCount();
            $this->info("Cleaned up $deleted old log entries");
            
            return $deleted;
        } catch (Exception $e) {
            $this->logToFileOnly("Failed to cleanup logs: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get log statistics
     */
    public function getStats(?string $date = null): array {
        try {
            if (!$date) {
                $date = date('Y-m-d');
            }
            
            $sql = "SELECT 
                log_type,
                COUNT(*) as count,
                MIN(created_at) as first_log,
                MAX(created_at) as last_log
                FROM logs 
                WHERE DATE(created_at) = ?
                GROUP BY log_type
                ORDER BY count DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logToFileOnly("Failed to get log stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export logs to CSV
     */
    public function exportToCsv(string $filename): bool {
        try {
            $logs = $this->getRecentLogs(1000);
            
            if (empty($logs)) {
                return false;
            }
            
            $fp = fopen($filename, 'w');
            
            // Write headers
            fputcsv($fp, array_keys($logs[0]));
            
            // Write data
            foreach ($logs as $log) {
                fputcsv($fp, $log);
            }
            
            fclose($fp);
            return true;
            
        } catch (Exception $e) {
            $this->logToFileOnly("Failed to export logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log API request
     */
    public function apiRequest(string $endpoint, array $request, array $response, ?int $userId = null, ?int $duration = null): void {
        $context = [
            'endpoint' => $endpoint,
            'request' => $request,
            'response' => $response,
            'duration_ms' => $duration
        ];
        
        $this->info("API Request: $endpoint", $context, $userId);
    }
    
    /**
     * Log user action
     */
    public function userAction(string $action, array $details = [], ?int $userId = null): void {
        $context = [
            'action' => $action,
            'details' => $details
        ];
        
        $this->info("User Action: $action", $context, $userId);
    }
    
    /**
     * Log performance metric
     */
    public function performance(string $operation, float $duration, array $context = []): void {
        $context['duration'] = $duration;
        $context['operation'] = $operation;
        
        $level = $duration > 1000 ? 'WARNING' : 'INFO';
        $this->log($level, 'performance', "Performance: $operation took {$duration}ms", $context);
    }
}
?>