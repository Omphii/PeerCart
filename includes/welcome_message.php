<?php
// welcome_message.php
// This file handles displaying welcome/logout messages

// Function to show a temporary message
function showTemporaryMessage($message, $type = 'success') {
    // Sanitize message for security
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Map message types to colors
    $colorMap = [
        'success' => 'linear-gradient(135deg, var(--success), var(--primary))',
        'error' => 'linear-gradient(135deg, var(--danger), var(--warning))',
        'info' => 'linear-gradient(135deg, var(--primary), var(--secondary))',
        'warning' => 'linear-gradient(135deg, var(--warning), var(--danger))'
    ];
    
    $bgColor = $colorMap[$type] ?? $colorMap['success'];
    
    return <<<HTML
    <div class="temporary-message-container" style="position: fixed; top: 70px; left: 0; right: 0; z-index: 998; display: flex; justify-content: center; padding: 0 1rem; pointer-events: none; animation: slideDown 0.4s ease;">
        <div class="temporary-message" style="background: $bgColor; color: white; padding: 12px 20px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); max-width: 500px; width: 100%; text-align: center; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 10px; pointer-events: auto;">
            <span>$safeMessage</span>
            <button onclick="this.parentElement.parentElement.style.display='none'" aria-label="Close message" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s ease; flex-shrink: 0;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        // Auto-hide the message after 5 seconds
        setTimeout(() => {
            const message = document.querySelector('.temporary-message-container');
            if (message) {
                message.style.display = 'none';
            }
        }, 5000);
    </script>
    <style>
        @keyframes slideDown {
            from { 
                transform: translateY(-20px); 
                opacity: 0; 
            }
            to { 
                transform: translateY(0); 
                opacity: 1; 
            }
        }
        @media (min-width: 768px) {
            .temporary-message-container {
                top: 80px;
            }
            .temporary-message {
                font-size: 0.95rem;
                padding: 15px 25px;
            }
        }
        .temporary-message button:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
HTML;
}

// Function to get and display logout message
function getLogoutMessage() {
    $html = '';
    
    // Check for logout message in session
    if (isset($_SESSION['logout_message'])) {
        $logoutMessage = $_SESSION['logout_message'];
        $html = showTemporaryMessage($logoutMessage, 'success');
        // Clear the message so it doesn't show again
        unset($_SESSION['logout_message']);
    }
    
    return $html;
}

// Function to get and display login welcome message
function getLoginWelcomeMessage() {
    $html = '';
    
    // Check if user just logged in
    if (isset($_SESSION['login_welcome_message'])) {
        $welcomeMessage = $_SESSION['login_welcome_message'];
        $html = showTemporaryMessage($welcomeMessage, 'success');
        // Clear the message so it doesn't show again
        unset($_SESSION['login_welcome_message']);
    }
    
    return $html;
}

// Function to get and display registration welcome message
function getRegistrationWelcomeMessage() {
    $html = '';
    
    // Check if user just registered
    if (isset($_SESSION['registration_welcome_message'])) {
        $welcomeMessage = $_SESSION['registration_welcome_message'];
        $html = showTemporaryMessage($welcomeMessage, 'success');
        // Clear the message so it doesn't show again
        unset($_SESSION['registration_welcome_message']);
    }
    
    return $html;
}

// Function to get and display any session messages
function getSessionMessages() {
    $html = '';
    
    // Check for various session messages
    $messageTypes = ['success_message', 'error_message', 'info_message', 'warning_message'];
    
    foreach ($messageTypes as $type) {
        if (isset($_SESSION[$type])) {
            $messageType = str_replace('_message', '', $type);
            $html .= showTemporaryMessage($_SESSION[$type], $messageType);
            unset($_SESSION[$type]);
        }
    }
    
    return $html;
}

// Main function to display all messages
function displayAllMessages() {
    $messages = '';
    
    // Get logout message
    $messages .= getLogoutMessage();
    
    // Get login welcome message
    $messages .= getLoginWelcomeMessage();
    
    // Get registration welcome message
    $messages .= getRegistrationWelcomeMessage();
    
    // Get other session messages
    $messages .= getSessionMessages();
    
    return $messages;
}

// Helper function to set a login welcome message
function setLoginWelcomeMessage($userName = 'User') {
    $_SESSION['login_welcome_message'] = "Welcome back, $userName! You've been logged in successfully.";
}

// Helper function to set a registration welcome message
function setRegistrationWelcomeMessage($userName = 'User') {
    $_SESSION['registration_welcome_message'] = "Welcome to PeerCart, $userName! Your account has been created successfully.";
}

// Helper function to set a generic success message
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Helper function to set an error message
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// Helper function to set an info message
function setInfoMessage($message) {
    $_SESSION['info_message'] = $message;
}

// Helper function to set a warning message
function setWarningMessage($message) {
    $_SESSION['warning_message'] = $message;
}
?>