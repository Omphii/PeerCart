<?php
// fix_csrf_session.php
require_once '../includes/bootstrap.php';

echo "<h2>Fixing CSRF Session Data</h2>";

if (isset($_SESSION['csrf_tokens'])) {
    echo "<h3>Before Fix:</h3>";
    echo "<pre>" . print_r($_SESSION['csrf_tokens'], true) . "</pre>";
    
    // Fix the structure
    if (isset($_SESSION['csrf_tokens']['general']) && is_array($_SESSION['csrf_tokens']['general'])) {
        $general = $_SESSION['csrf_tokens']['general'];
        
        // Check if it has old format tokens (flat array keys)
        $hasOldFormat = false;
        foreach ($general as $key => $value) {
            if ($key === 'token' || $key === 'created' || $key === 'expires' || $key === 'used') {
                $hasOldFormat = true;
                break;
            }
        }
        
        if ($hasOldFormat) {
            echo "<p>Found old format CSRF tokens. Converting...</p>";
            
            // Create new structure
            $newGeneral = [];
            
            // Check if we have a valid token in old format
            if (isset($general['token']) && isset($general['created'])) {
                $tokenId = uniqid('converted_', true);
                $newGeneral[$tokenId] = [
                    'token' => $general['token'],
                    'created' => $general['created'],
                    'expires' => $general['expires'] ?? ($general['created'] + 3600),
                    'used' => $general['used'] ?? false
                ];
                
                if (isset($general['used_at'])) {
                    $newGeneral[$tokenId]['used_at'] = $general['used_at'];
                }
            }
            
            // Add any other tokens that are already in new format
            foreach ($general as $key => $value) {
                if (is_array($value) && isset($value['token']) && isset($value['created'])) {
                    $newGeneral[$key] = $value;
                }
            }
            
            $_SESSION['csrf_tokens']['general'] = $newGeneral;
            echo "<p>✓ Converted to new format</p>";
        }
    }
    
    echo "<h3>After Fix:</h3>";
    echo "<pre>" . print_r($_SESSION['csrf_tokens'], true) . "</pre>";
    
    echo "<p><a href='debug_csrf.php'>Test CSRF Again</a></p>";
} else {
    echo "<p>No CSRF tokens in session.</p>";
}

// Clear all CSRF tokens and start fresh
echo "<h3>Option: Clear All CSRF Tokens</h3>";
echo "<form method='post'>";
echo "<button type='submit' name='clear_csrf'>Clear CSRF Tokens</button>";
echo "</form>";

if (isset($_POST['clear_csrf'])) {
    unset($_SESSION['csrf_tokens']);
    echo "<p>✓ CSRF tokens cleared. Session will be regenerated on next page load.</p>";
}
?>