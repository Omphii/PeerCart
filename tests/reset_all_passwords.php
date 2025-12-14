<?php
// reset_all_passwords.php
require_once '../includes/database.php';

echo "<h2>Reset All User Passwords</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all users
    $users = $db->query("SELECT id, email, user_type FROM users")->fetchAll();
    
    echo "<p>Found " . count($users) . " users</p>";
    
    foreach ($users as $user) {
        // Determine password based on user type
        if ($user['user_type'] === 'admin') {
            $password = 'Admin@123';
        } elseif ($user['user_type'] === 'seller') {
            $password = 'Seller@123';
        } else {
            $password = 'Buyer@123';
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update database
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        echo "<p>";
        echo "<strong>User {$user['id']}:</strong> " . htmlspecialchars($user['email']) . "<br>";
        echo "<strong>Type:</strong> " . htmlspecialchars($user['user_type']) . "<br>";
        echo "<strong>New Password:</strong> <code>" . htmlspecialchars($password) . "</code><br>";
        echo "<strong>Status:</strong> <span style='color: green;'>✓ Password Reset</span>";
        echo "</p><hr>";
    }
    
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>Buyers:</strong> Use password: <code>Buyer@123</code></li>";
    echo "<li><strong>Sellers:</strong> Use password: <code>Seller@123</code></li>";
    echo "<li><strong>Admins:</strong> Use password: <code>Admin@123</code></li>";
    echo "</ul>";
    
    echo "<p><a href='../includes/auth/login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>