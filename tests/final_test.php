<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "<h1>PeerCart Connection Tests</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p style='color:green;'>✓ Database connection successful</p>";
    
    // Test query
    $stmt = $conn->query("SELECT VERSION() as version");
    $version = $stmt->fetchColumn();
    echo "<p>MySQL Version: $version</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 2: Session
echo "<h2>2. Session</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

// Test 3: Constants
echo "<h2>3. Constants</h2>";
echo "<p>BASE_URL: " . BASE_URL . "</p>";
echo "<p>ROOT_PATH: " . ROOT_PATH . "</p>";

// Test 4: Functions
echo "<h2>4. Functions</h2>";
echo "<p>asset() test: " . asset('css/main.css') . "</p>";
echo "<p>url() test: " . url('pages/listings.php') . "</p>";

// Test 5: File Permissions
echo "<h2>5. File Permissions</h2>";
$dirs = ['uploads', 'uploads/listings', 'uploads/profile', 'logs', 'temp'];
foreach ($dirs as $dir) {
    $path = ROOT_PATH . '/' . $dir;
    if (file_exists($path)) {
        echo "<p>✓ $dir exists</p>";
        echo "<p>  - Writable: " . (is_writable($path) ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color:orange;'>⚠ $dir doesn't exist</p>";
    }
}
?>