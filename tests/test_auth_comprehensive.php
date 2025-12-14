<?php
// test_auth_comprehensive.php - Comprehensive Authentication System Test WITH LOGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the base directory correctly
define('BASE_DIR', dirname(__DIR__));

// Start session for logging
session_start();

// Simple logging function for the test
function test_log($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [TEST-$type] $message\n";
    
    // Log to file
    $logFile = BASE_DIR . '/logs/test.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    
    // Also log to PHP error log
    error_log($logLine);
    
    return $logLine;
}

// Start test log
test_log("=== COMPREHENSIVE AUTH TEST STARTED ===");
test_log("Test URL: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
test_log("Session ID: " . session_id());

echo "<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Auth System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .test-section { margin: 25px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; }
        .test-title { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .log-output { background: #2d2d2d; color: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>🔍 Comprehensive Authentication System Test</h1>
    <p>Testing all components of your PeerCart authentication system</p>";

// ==================== SECTION 1: ENVIRONMENT CHECK ====================
echo "<div class='test-section'>
        <h2 class='test-title'>1. Environment & File Check</h2>";

// Check required files with correct paths
$requiredFiles = [
    BASE_DIR . '/includes/bootstrap.php',
    BASE_DIR . '/includes/database.php', 
    BASE_DIR . '/includes/functions.php',
    BASE_DIR . '/includes/auth/login.php',
    BASE_DIR . '/includes/auth/register.php'
];

foreach ($requiredFiles as $file) {
    $relativePath = str_replace(BASE_DIR . '/', '', $file);
    if (file_exists($file)) {
        echo "<p class='success'>✓ File exists: $relativePath</p>";
        test_log("File exists: $relativePath", 'SUCCESS');
    } else {
        echo "<p class='error'>✗ Missing file: $relativePath</p>";
        test_log("Missing file: $relativePath", 'ERROR');
        echo "<p><small>Full path: $file</small></p>";
    }
}

echo "</div>";

// ==================== SECTION 2: DATABASE CONNECTION ====================
echo "<div class='test-section'>
        <h2 class='test-title'>2. Database Connection</h2>";

try {
    test_log("Attempting database connection...", 'INFO');
    
    // Check if file exists before including
    $dbFile = BASE_DIR . '/includes/database.php';
    if (!file_exists($dbFile)) {
        throw new Exception("Database file not found: $dbFile");
    }
    
    require_once $dbFile;
    $db = Database::getInstance()->getConnection();
    
    echo "<p class='success'>✓ Database connection successful</p>";
    test_log("Database connection successful", 'SUCCESS');
    
    // Test query
    $testQuery = $db->query("SELECT 1 + 1 AS result")->fetch();
    echo "<p class='success'>✓ Test query executed: 1 + 1 = " . $testQuery['result'] . "</p>";
    test_log("Test query executed: 1 + 1 = " . $testQuery['result'], 'SUCCESS');
    
    // Check users table
    $tables = $db->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($tables) {
        echo "<p class='success'>✓ Users table exists</p>";
        test_log("Users table exists", 'SUCCESS');
        
        // Count users
        $userCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
        echo "<p class='info'>Total users in database: " . $userCount['count'] . "</p>";
        test_log("Total users in database: " . $userCount['count'], 'INFO');
        
        // Show users
        $users = $db->query("SELECT id, email, user_type, is_active FROM users LIMIT 10")->fetchAll();
        if ($users) {
            echo "<h4>Sample Users:</h4>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Email</th><th>Type</th><th>Active</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td><code>" . htmlspecialchars($user['email']) . "</code></td>";
                echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
                echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Log user details
            foreach ($users as $user) {
                test_log("User: ID={$user['id']}, Email={$user['email']}, Type={$user['user_type']}, Active={$user['is_active']}", 'INFO');
            }
        }
    } else {
        echo "<p class='error'>✗ Users table does not exist!</p>";
        test_log("Users table does not exist!", 'ERROR');
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    test_log("Database error: " . $e->getMessage(), 'ERROR');
}

echo "</div>";

// ==================== SECTION 3: SESSION & CSRF SYSTEM ====================
echo "<div class='test-section'>
        <h2 class='test-title'>3. Session & CSRF System</h2>";

try {
    test_log("Loading bootstrap and functions...", 'INFO');
    
    // Check if files exist
    $bootstrapFile = BASE_DIR . '/includes/bootstrap.php';
    $functionsFile = BASE_DIR . '/includes/functions.php';
    
    if (!file_exists($bootstrapFile)) {
        throw new Exception("Bootstrap file not found: $bootstrapFile");
    }
    if (!file_exists($functionsFile)) {
        throw new Exception("Functions file not found: $functionsFile");
    }
    
    require_once $bootstrapFile;
    require_once $functionsFile;
    
    echo "<p class='success'>✓ Bootstrap and functions loaded</p>";
    test_log("Bootstrap and functions loaded", 'SUCCESS');
    
    echo "<p class='info'>Session ID: " . session_id() . "</p>";
    echo "<p class='info'>Session Name: " . session_name() . "</p>";
    
    test_log("Session ID: " . session_id(), 'INFO');
    test_log("Session Name: " . session_name(), 'INFO');
    
    // Test CSRF functions
    if (function_exists('generateCSRFToken')) {
        echo "<p class='success'>✓ CSRF functions available</p>";
        test_log("CSRF functions available", 'SUCCESS');
        
        // Generate token
        $token1 = generateCSRFToken();
        echo "<p>Generated Token 1: " . substr($token1, 0, 20) . "...</p>";
        test_log("Generated CSRF Token 1: " . substr($token1, 0, 20) . "...", 'INFO');
        
        // Validate same token
        $valid1 = validateCSRFToken($token1);
        echo "<p>Token 1 Validation: " . ($valid1 ? "✓ VALID" : "✗ INVALID") . "</p>";
        test_log("Token 1 Validation: " . ($valid1 ? "VALID" : "INVALID"), $valid1 ? 'SUCCESS' : 'ERROR');
        
        // Generate second token
        $token2 = generateCSRFToken();
        echo "<p>Generated Token 2: " . substr($token2, 0, 20) . "...</p>";
        test_log("Generated CSRF Token 2: " . substr($token2, 0, 20) . "...", 'INFO');
        
        // Validate second token
        $valid2 = validateCSRFToken($token2);
        echo "<p>Token 2 Validation: " . ($valid2 ? "✓ VALID" : "✗ INVALID") . "</p>";
        test_log("Token 2 Validation: " . ($valid2 ? "VALID" : "INVALID"), $valid2 ? 'SUCCESS' : 'ERROR');
        
        // Try to validate used token (should fail)
        $valid3 = validateCSRFToken($token1);
        echo "<p>Token 1 Re-validation: " . ($valid3 ? "✓ VALID (BUG!)" : "✗ INVALID (Correct)") . "</p>";
        test_log("Token 1 Re-validation: " . ($valid3 ? "VALID (BUG!)" : "INVALID (Correct)"), $valid3 ? 'ERROR' : 'SUCCESS');
        
        echo "<h4>Current CSRF Tokens in Session:</h4>";
        echo "<pre>" . htmlspecialchars(print_r($_SESSION['csrf_tokens'] ?? [], true)) . "</pre>";
        
        // Log CSRF tokens
        test_log("CSRF tokens in session: " . json_encode($_SESSION['csrf_tokens'] ?? []), 'INFO');
        
    } else {
        echo "<p class='error'>✗ CSRF functions not found!</p>";
        test_log("CSRF functions not found!", 'ERROR');
    }
    
    // Test session functions
    echo "<h4>Session Functions Test:</h4>";
    $isLoggedIn = isLoggedIn();
    echo "<p>isLoggedIn(): " . ($isLoggedIn ? "TRUE (should be FALSE if not logged in)" : "FALSE (Correct)") . "</p>";
    test_log("isLoggedIn(): " . ($isLoggedIn ? "TRUE" : "FALSE"), $isLoggedIn ? 'WARNING' : 'INFO');
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Session/CSRF error: " . htmlspecialchars($e->getMessage()) . "</p>";
    test_log("Session/CSRF error: " . $e->getMessage(), 'ERROR');
}

echo "</div>";

// ==================== SECTION 4: AUTHENTICATION FUNCTIONS ====================
echo "<div class='test-section'>
        <h2 class='test-title'>4. Authentication Functions</h2>";

// Only proceed if functions are loaded
if (!function_exists('authenticateUser')) {
    echo "<p class='error'>✗ Authentication functions not loaded. Cannot proceed with auth tests.</p>";
    test_log("Authentication functions not loaded", 'ERROR');
} else {
    // Test credentials
    $testCredentials = [
        ['buyer@example.com', 'Buyer@123', 'buyer'],
        ['seller@example.com', 'Seller@123', 'seller'],
        ['test378@test.com', 'Buyer@123', 'buyer'],
        ['test573@test.com', 'Seller@123', 'seller']
    ];

    echo "<h4>Testing authenticateUser() function:</h4>";

    $authResults = [];

    foreach ($testCredentials as $cred) {
        list($email, $password, $expectedType) = $cred;
        
        echo "<div style='margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;'>";
        echo "<strong>Testing:</strong> <code>$email</code> / <code>$password</code><br>";
        
        test_log("Testing authentication for: $email", 'INFO');
        
        $user = authenticateUser($email, $password);
        
        if ($user) {
            echo "<span class='success'>✓ Authentication SUCCESS</span><br>";
            test_log("Authentication SUCCESS for: $email", 'SUCCESS');
            echo "<small>ID: {$user['id']} | Type: {$user['user_type']} | Name: {$user['name']}</small>";
            
            // Log user details
            test_log("User details - ID: {$user['id']}, Type: {$user['user_type']}, Name: {$user['name']}", 'INFO');
            
            // Verify user type matches expected
            if ($user['user_type'] === $expectedType) {
                echo "<br><span class='success'>✓ User type matches expected: $expectedType</span>";
                test_log("User type matches expected: $expectedType", 'SUCCESS');
            } else {
                echo "<br><span class='error'>✗ User type mismatch. Expected: $expectedType, Got: {$user['user_type']}</span>";
                test_log("User type mismatch. Expected: $expectedType, Got: {$user['user_type']}", 'ERROR');
            }
            
            // Test createUserSession
            echo "<h5>Testing createUserSession():</h5>";
            createUserSession($user);
            
            $isLoggedIn = isLoggedIn();
            echo "Session created. Checking isLoggedIn(): " . ($isLoggedIn ? "✓ TRUE" : "✗ FALSE") . "<br>";
            test_log("createUserSession() - isLoggedIn(): " . ($isLoggedIn ? "TRUE" : "FALSE"), $isLoggedIn ? 'SUCCESS' : 'ERROR');
            
            echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
            echo "Session user_name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
            
            // Test role functions
            if ($isLoggedIn) {
                $isBuyer = isBuyer();
                $isSeller = isSeller();
                $isAdmin = isAdmin();
                
                echo "isBuyer(): " . ($isBuyer ? "TRUE" : "FALSE") . " | ";
                echo "isSeller(): " . ($isSeller ? "TRUE" : "FALSE") . " | ";
                echo "isAdmin(): " . ($isAdmin ? "TRUE" : "FALSE") . "<br>";
                
                test_log("Role checks - isBuyer: $isBuyer, isSeller: $isSeller, isAdmin: $isAdmin", 'INFO');
            }
            
            // Logout for next test
            secureLogout();
            echo "<span class='info'>✓ Logged out</span>";
            test_log("Logged out user: $email", 'INFO');
            
            $authResults[$email] = true;
            
        } else {
            echo "<span class='error'>✗ Authentication FAILED</span><br>";
            test_log("Authentication FAILED for: $email", 'ERROR');
            
            // Debug why
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id, email, password, is_active FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $dbUser = $stmt->fetch();
                
                if (!$dbUser) {
                    echo "<small>✗ User not found in database</small>";
                    test_log("User not found in database: $email", 'ERROR');
                } elseif (!$dbUser['is_active']) {
                    echo "<small>✗ User account is inactive</small>";
                    test_log("User account is inactive: $email", 'ERROR');
                } else {
                    // Test password directly
                    $passwordValid = password_verify($password, $dbUser['password']);
                    echo "<small>Password verify: " . ($passwordValid ? "✓ Valid" : "✗ Invalid") . "</small>";
                    test_log("Password verify for $email: " . ($passwordValid ? "Valid" : "Invalid"), $passwordValid ? 'SUCCESS' : 'ERROR');
                    
                    if (!$passwordValid) {
                        echo "<br><small>Hash in DB: " . substr($dbUser['password'], 0, 30) . "...</small>";
                        test_log("Password hash mismatch for $email", 'ERROR');
                    }
                }
            } catch (Exception $e) {
                echo "<small>Database error: " . $e->getMessage() . "</small>";
                test_log("Database error checking user $email: " . $e->getMessage(), 'ERROR');
            }
            
            $authResults[$email] = false;
        }
        
        echo "</div>";
    }
}

echo "</div>";

// ==================== SECTION 5: VALIDATION FUNCTIONS ====================
echo "<div class='test-section'>
        <h2 class='test-title'>5. Validation Functions</h2>";

// Only proceed if functions are loaded
if (!function_exists('sanitizeInput')) {
    echo "<p class='error'>✗ Validation functions not loaded</p>";
    test_log("Validation functions not loaded", 'ERROR');
} else {
    // Test sanitizeInput
    echo "<h4>Test sanitizeInput():</h4>";
    $testInput = "<script>alert('xss')</script>Hello World";
    $sanitized = sanitizeInput($testInput);
    echo "Original: " . htmlspecialchars($testInput) . "<br>";
    echo "Sanitized: " . htmlspecialchars($sanitized) . "<br>";
    $isSafe = strpos($sanitized, '<script>') === false;
    echo "Result: " . ($isSafe ? "✓ Safe" : "✗ Unsafe") . "<br>";
    test_log("sanitizeInput test - Original: $testInput, Sanitized: $sanitized, Safe: " . ($isSafe ? 'Yes' : 'No'), $isSafe ? 'SUCCESS' : 'ERROR');

    // Test password validation
    echo "<h4>Test validatePassword():</h4>";
    $testPasswords = [
        'weak' => 'abc',
        'noUpper' => 'password123!',
        'noLower' => 'PASSWORD123!',
        'noNumber' => 'Password!',
        'noSpecial' => 'Password123',
        'valid' => 'ValidPass123!'
    ];

    foreach ($testPasswords as $name => $password) {
        $errors = validatePassword($password);
        echo "$name: <code>$password</code> - ";
        if (empty($errors)) {
            echo "<span class='success'>✓ Valid</span>";
            test_log("Password validation for '$name': VALID", 'SUCCESS');
        } else {
            echo "<span class='error'>✗ " . implode(', ', $errors) . "</span>";
            test_log("Password validation for '$name': INVALID - " . implode(', ', $errors), 'INFO');
        }
        echo "<br>";
    }

    // Test registration validation
    echo "<h4>Test validateRegistrationInput():</h4>";
    $testRegistrationData = [
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'ValidPass123!',
        'confirm_password' => 'ValidPass123!',
        'user_type' => 'buyer'
    ];

    $regErrors = validateRegistrationInput($testRegistrationData);
    if (empty($regErrors)) {
        echo "<span class='success'>✓ Registration data valid</span>";
        test_log("Registration validation: VALID", 'SUCCESS');
    } else {
        echo "<span class='error'>✗ Registration errors: " . implode(', ', $regErrors) . "</span>";
        test_log("Registration validation: INVALID - " . implode(', ', $regErrors), 'ERROR');
    }
}

echo "</div>";

// ==================== SECTION 6: FORM SUBMISSION TEST ====================
echo "<div class='test-section'>
        <h2 class='test-title'>6. Form Submission Test</h2>";

// Generate CSRF token for form if function exists
if (function_exists('generateCSRFToken')) {
    $formToken = generateCSRFToken();
    test_log("Generated form CSRF token: " . substr($formToken, 0, 20) . "...", 'INFO');

    echo "<h4>Test Login Form (with real CSRF):</h4>";
    echo "<form method='post' action='" . BASE_DIR . "/includes/auth/login.php' style='background: #f8f9fa; padding: 20px; border-radius: 4px;'>
            <input type='hidden' name='csrf_token' value='$formToken'>
            <div style='margin: 10px 0;'>
                <label>Email:</label><br>
                <input type='email' name='email' value='seller@example.com' style='width: 100%; padding: 8px;'>
            </div>
            <div style='margin: 10px 0;'>
                <label>Password:</label><br>
                <input type='password' name='password' value='Seller@123' style='width: 100%; padding: 8px;'>
            </div>
            <button type='submit' class='btn btn-primary'>Test Real Login</button>
          </form>";
} else {
    echo "<p class='warning'>CSRF functions not available. Cannot generate secure form.</p>";
}

echo "<h4>Quick Test Links:</h4>";
echo "<p>
        <a href='" . BASE_DIR . "/includes/auth/login.php' class='btn btn-success'>Go to Login Page</a>
        <a href='" . BASE_DIR . "/includes/auth/register.php' class='btn btn-primary'>Go to Register Page</a>
        <a href='debug_sessions.php' class='btn'>Debug Session</a>
        <a href='debug_logs.php' class='btn'>View Logs</a>
      </p>";

echo "</div>";

// ==================== SECTION 7: SUMMARY ====================
echo "<div class='test-section'>
        <h2 class='test-title'>7. Test Summary</h2>";

// Collect all test results
$allTests = [];
$currentSession = session_id();

// Database test
try {
    $db = Database::getInstance()->getConnection();
    $allTests['database'] = true;
    test_log("Database test: PASSED", 'SUCCESS');
} catch (Exception $e) {
    $allTests['database'] = false;
    test_log("Database test: FAILED - " . $e->getMessage(), 'ERROR');
}

// CSRF test
$allTests['csrf'] = function_exists('generateCSRFToken');
test_log("CSRF test: " . ($allTests['csrf'] ? "PASSED" : "FAILED"), $allTests['csrf'] ? 'SUCCESS' : 'ERROR');

// Authentication test (test with one user)
$allTests['auth'] = false;
if (function_exists('authenticateUser')) {
    $testUser = authenticateUser('seller@example.com', 'Seller@123');
    $allTests['auth'] = !empty($testUser);
    test_log("Authentication test: " . ($allTests['auth'] ? "PASSED" : "FAILED"), $allTests['auth'] ? 'SUCCESS' : 'ERROR');
} else {
    test_log("Authentication test: FAILED - authenticateUser function not found", 'ERROR');
}

// Session test
$allTests['session'] = !empty($currentSession);
test_log("Session test: " . ($allTests['session'] ? "PASSED" : "FAILED"), $allTests['session'] ? 'SUCCESS' : 'ERROR');

// Functions test
$allTests['functions'] = function_exists('isLoggedIn') && 
                         function_exists('createUserSession') && 
                         function_exists('secureLogout');
test_log("Functions test: " . ($allTests['functions'] ? "PASSED" : "FAILED"), $allTests['functions'] ? 'SUCCESS' : 'ERROR');

echo "<h4>System Status:</h4>";
echo "<table>";
echo "<tr><th>Component</th><th>Status</th><th>Details</th></tr>";

foreach ($allTests as $component => $status) {
    echo "<tr>";
    echo "<td>" . ucfirst($component) . "</td>";
    echo "<td>" . ($status ? "✅ Operational" : "❌ Failed") . "</td>";
    echo "<td>";
    switch ($component) {
        case 'database':
            echo $status ? "Connection established" : "Connection failed";
            break;
        case 'csrf':
            echo $status ? "CSRF functions available" : "CSRF functions missing";
            break;
        case 'auth':
            echo $status ? "seller@example.com authentication works" : "Authentication failed";
            break;
        case 'session':
            echo $status ? "Session active: $currentSession" : "No active session";
            break;
        case 'functions':
            echo $status ? "Core functions available" : "Missing core functions";
            break;
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Overall status
$allPassed = array_reduce($allTests, function($carry, $item) {
    return $carry && $item;
}, true);

if ($allPassed) {
    echo "<div class='success' style='font-size: 18px; padding: 20px;'>
            <strong>✅ ALL SYSTEMS OPERATIONAL!</strong><br>
            Your authentication system is working correctly. You can proceed with login.
          </div>";
    echo "<p><a href='" . BASE_DIR . "/includes/auth/login.php' class='btn btn-success' style='font-size: 16px; padding: 12px 24px;'>
            🚀 Proceed to Login Page
          </a></p>";
    test_log("OVERALL TEST RESULT: ALL SYSTEMS OPERATIONAL", 'SUCCESS');
} else {
    $failedTests = array_filter($allTests, function($item) { return !$item; });
    $failedCount = count($failedTests);
    echo "<div class='error' style='font-size: 18px; padding: 20px;'>
            <strong>⚠️ SYSTEM ISSUES DETECTED</strong><br>
            $failedCount component(s) are not working. Check the details above.
          </div>";
    test_log("OVERALL TEST RESULT: SYSTEM ISSUES DETECTED - Failed: " . implode(', ', array_keys($failedTests)), 'ERROR');
}

echo "</div>";

// ==================== SECTION 8: LOG OUTPUT ====================
echo "<div class='test-section'>
        <h2 class='test-title'>8. Test Log Output</h2>";

// Display recent log entries
$logFile = BASE_DIR . '/logs/test.log';
if (file_exists($logFile)) {
    echo "<h4>Recent Test Logs:</h4>";
    echo "<div class='log-output'>";
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50); // Last 50 lines
    foreach ($recentLines as $line) {
        // Color code based on log level
        if (strpos($line, '[TEST-ERROR]') !== false) {
            echo "<span style='color: #ff6b6b;'>" . htmlspecialchars($line) . "</span>";
        } elseif (strpos($line, '[TEST-WARNING]') !== false) {
            echo "<span style='color: #ffd93d;'>" . htmlspecialchars($line) . "</span>";
        } elseif (strpos($line, '[TEST-SUCCESS]') !== false) {
            echo "<span style='color: #6bcf7f;'>" . htmlspecialchars($line) . "</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</div>";
    
    echo "<p><a href='view_test_logs.php' target='_blank'>View Full Test Log</a></p>";
} else {
    echo "<p class='warning'>Test log file not created yet. It will be created after first run.</p>";
    echo "<p><small>Expected location: $logFile</small></p>";
}

echo "</div>";

// ==================== SECTION 9: QUICK FIXES ====================
echo "<div class='test-section'>
        <h2 class='test-title'>9. Quick Fixes</h2>";

echo "<h4>If Authentication Fails:</h4>";
echo "<ol>
        <li><a href='reset_all_passwords.php'>Reset all passwords</a> - Sets all users to known passwords</li>
        <li><a href='create_fresh_test_users.php'>Create fresh test users</a> - Deletes and recreates test users</li>
        <li><a href='fix_csrf_session.php'>Fix CSRF session</a> - Cleans up CSRF token storage</li>
      </ol>";

echo "<h4>View Logs:</h4>";
echo "<ul>
        <li><a href='../logs/test.log' target='_blank'>View Test Log</a></li>
        <li><a href='../logs/app.log' target='_blank'>View Application Log</a></li>
        <li><a href='debug_logs.php'>Debug Logs Page</a></li>
      </ul>";

echo "</div>";

// ==================== FOOTER ====================
echo "<div style='margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;'>
        <h3>Next Steps:</h3>
        <ol>
            <li>Run this test to check system status</li>
            <li>Check the <strong>Test Log Output</strong> section above</li>
            <li>If all tests pass, use the login page</li>
            <li>If tests fail, use the quick fixes above</li>
            <li>Check <a href='debug_logs.php'>application logs</a> for detailed errors</li>
        </ol>
        
        <h4>Current Session State:</h4>
        <details>
            <summary>Show Session Data</summary>
            <pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>
        </details>
        
        <h4>Test Log File Location:</h4>
        <p><code>" . realpath(dirname($logFile)) . DIRECTORY_SEPARATOR . "test.log</code></p>
      </div>";

// Log test completion
test_log("=== COMPREHENSIVE AUTH TEST COMPLETED ===");
test_log("Overall result: " . ($allPassed ? "ALL PASSED" : "SOME FAILED"));
test_log("Session ID at end: " . session_id());

echo "</body></html>";