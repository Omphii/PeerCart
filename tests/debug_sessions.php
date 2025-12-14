<?php
// debug_sessions.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/bootstrap.php';
require_once '../includes/functions.php';

echo "<h2>Session Debug</h2>";

echo "<h3>Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test CSRF Functions:</h3>";
if (function_exists('generateCSRFToken')) {
    $token = generateCSRFToken();
    echo "Generated Token: " . $token . "<br>";
    echo "Session after generation: <pre>";
    print_r($_SESSION['csrf_tokens'] ?? 'No csrf_tokens');
    echo "</pre>";
    
    // Validate it
    echo "Validation result: " . (validateCSRFToken($token) ? "VALID" : "INVALID") . "<br>";
} else {
    echo "CSRF functions not found!<br>";
}

echo "<h3>POST Data from previous request:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>