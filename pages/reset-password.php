<?php
// pages/reset-password.php - RESET PASSWORD HANDLER

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect already logged-in users
if (isLoggedIn()) {
    header("Location: " . redirectBasedOnRole());
    exit;
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($token)) {
        $errors[] = "Invalid reset token";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }
    
    if (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $db = db()->getConnection();
            
            // Verify token
            $stmt = $db->prepare("SELECT id, reset_token, reset_token_expires FROM users WHERE email = ? AND reset_token = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email, $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if token is expired
                $expires = new DateTime($user['reset_token_expires']);
                $now = new DateTime();
                
                if ($expires > $now) {
                    // Update password and clear reset token
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                    $stmt->execute([$hashed_password, $user['id']]);
                    
                    $success = true;
                    
                    // Clear demo session data
                    unset($_SESSION['demo_reset_link']);
                    unset($_SESSION['reset_email']);
                } else {
                    $errors[] = "Reset link has expired. Please request a new one.";
                }
            } else {
                $errors[] = "Invalid or expired reset link.";
            }
        } catch (PDOException $e) {
            $errors[] = "An error occurred. Please try again.";
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}

// If success, redirect to login
if ($success) {
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => "Your password has been reset successfully. You can now login with your new password."
    ];
    header("Location: " . BASE_URL . "/pages/auth.php");
    exit;
}

// If there are errors, redirect back to forgot password
if (!empty($errors) && empty($_POST)) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => $errors[0]
    ];
    header("Location: " . BASE_URL . "/pages/forgot-password.php");
    exit;
}

// If we get here with errors from POST, show the errors
$title = "Reset Password";
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-main-container">
        <div class="auth-card">
            <div class="card-header">
                <div class="card-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="card-title">Reset Password</h2>
                <p class="card-subtitle">Complete your password reset</p>
            </div>
            
            <div class="card-body">
                <?php if(!empty($errors)): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($errors[0]) ?></span>
                    </div>
                    
                    <div class="auth-links" style="text-align: center; margin-top: 20px;">
                        <a href="<?= BASE_URL ?>/pages/forgot-password.php" class="btn-submit" style="display: inline-block;">
                            Request New Reset Link
                        </a>
                    </div>
                <?php else: ?>
                    <div class="form-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Your password has been reset successfully!</span>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p>You will be redirected to login in <span id="countdown">5</span> seconds...</p>
                        <a href="<?= BASE_URL ?>/pages/auth.php" class="btn-submit" style="display: inline-block; margin-top: 10px;">
                            Go to Login Now
                        </a>
                    </div>
                    
                    <script>
                    // Countdown redirect
                    let seconds = 5;
                    const countdownElement = document.getElementById('countdown');
                    const countdownInterval = setInterval(function() {
                        seconds--;
                        countdownElement.textContent = seconds;
                        
                        if (seconds <= 0) {
                            clearInterval(countdownInterval);
                            window.location.href = "<?= BASE_URL ?>/pages/auth.php";
                        }
                    }, 1000);
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>