<?php
// debug_logs.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/bootstrap.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Log Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 500px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h2>Log Viewer</h2>";

// Check log file
$logFile = defined('ROOT_PATH') ? ROOT_PATH . '/logs/app.log' : __DIR__ . '/../logs/app.log';

if (file_exists($logFile)) {
    echo "<h3>File Logs:</h3>";
    echo "<pre>";
    $content = file_get_contents($logFile);
    echo htmlspecialchars($content);
    echo "</pre>";
} else {
    echo "<p>Log file not found at: $logFile</p>";
    
    // Try to create it
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    if (touch($logFile)) {
        echo "<p>Created new log file at: $logFile</p>";
    } else {
        echo "<p>Failed to create log file. Check permissions.</p>";
    }
}

echo "</body></html>";