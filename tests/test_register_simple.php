<?php
// test_register_simple.php - Minimal test version
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/bootstrap.php';
require_once '../includes/functions.php';

// Simple session test
echo "<h2>Simple Registration Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Received</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check if user exists
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        $exists = emailExists($email);
        echo "Email exists check: " . ($exists ? "YES" : "NO") . "<br>";
    }
    
    // Try to create user directly
    if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
        try {
            $db = Database::getInstance()->getConnection();
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users 
                (name, surname, email, password, user_type, phone, address, city, province, postal_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['surname'] ?? '',
                $_POST['email'],
                $hashedPassword,
                $_POST['user_type'] ?? 'buyer',
                $_POST['phone'] ?? '',
                $_POST['address'] ?? '',
                $_POST['city'] ?? '',
                $_POST['province'] ?? '',
                $_POST['postal_code'] ?? ''
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✓ User created successfully! ID: " . $db->lastInsertId() . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr><a href='test_register_simple.php'>Try Again</a>";
    
} else {
    // Display form
    ?>
    <form method="post" style="max-width: 500px;">
        <h3>Simple Registration Test</h3>
        
        <div style="margin: 10px 0;">
            <label>First Name:</label><br>
            <input type="text" name="name" value="Test" required>
        </div>
        
        <div style="margin: 10px 0;">
            <label>Last Name:</label><br>
            <input type="text" name="surname" value="User">
        </div>
        
        <div style="margin: 10px 0;">
            <label>Email:</label><br>
            <input type="email" name="email" value="test<?= rand(100,999) ?>@test.com" required>
        </div>
        
        <div style="margin: 10px 0;">
            <label>Password:</label><br>
            <input type="password" name="password" value="Test@123" required>
        </div>
        
        <div style="margin: 10px 0;">
            <label>User Type:</label><br>
            <select name="user_type">
                <option value="buyer">Buyer</option>
                <option value="seller">Seller</option>
            </select>
        </div>
        
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none;">
            Register Test User
        </button>
    </form>
    
    <hr>
    <h3>Debug Info:</h3>
    <?php
    echo "Session ID: " . session_id() . "<br>";
    echo "Session Data: <pre>";
    print_r($_SESSION);
    echo "</pre>";
    ?>
    <?php
}
?>