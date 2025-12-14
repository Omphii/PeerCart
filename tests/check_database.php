<?php
// check_database.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Database Check</h2>";
    
    // Check users table
    $usersTable = $db->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($usersTable) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
        
        // Show structure
        echo "<h3>Users Table Structure:</h3>";
        $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count users
        $count = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
        echo "<p>Total users: " . $count['count'] . "</p>";
        
        // Show sample users
        $users = $db->query("SELECT id, name, email, user_type FROM users LIMIT 5")->fetchAll();
        if ($users) {
            echo "<h3>Sample Users:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . $user['user_type'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Users table does NOT exist!</p>";
        echo "<p><a href='setup_database.php'>Click here to setup database</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>