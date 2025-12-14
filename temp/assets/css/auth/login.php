<?php
// includes/auth/login.php - COMPLETE ENHANCED VERSION
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log page access
log_info("Login page accessed", [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'referrer' => $_SERVER['HTTP_REFERER'] ?? 'direct'
]);

// Check for maintenance mode
if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) {
    $title = "Maintenance Mode - PeerCart";
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="auth-container auth-page">
        <div class="glass-card text-center">
            <div class="auth-logo mb-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="PeerCart Logo" onerror="this.style.display='none'">
                <h2>PeerCart</h2>
            </div>
            <div class="maintenance-icon mb-4">
                <i class="fas fa-tools fa-4x text-warning"></i>
            </div>
            <h3 class="auth-title mb-3">Under Maintenance</h3>
            <p class="text-muted mb-4">
                We're currently performing scheduled maintenance. We'll be back online shortly.
            </p>
            <div class="countdown mb-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <a href="<?= BASE_URL ?>" class="btn btn-outline-primary">
                <i class="fas fa-home me-2"></i>Return Home
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Redirect already logged-in users
if (isLoggedIn()) {
    log_info("User already logged in, redirecting", [
        'user_id' => $_SESSION['user_id'] ?? 'unknown',
        'user_type' => $_SESSION['user_type'] ?? 'unknown',
        'redirect_to' => $_SESSION['redirect_after_login'] ?? redirectBasedOnRole()
    ]);
    
    header("Location: " . ($_SESSION['redirect_after_login'] ?? redirectBasedOnRole()));
    exit;
}

// Initialize variables
$errors = [];
$success_message = '';
$email = '';
$remember_me = false;

// Check for successful registration or password reset
if (isset($_SESSION['registration_success'])) {
    $success_message = "Registration successful! Please login with your credentials.";
    unset($_SESSION['registration_success']);
}

if (isset($_SESSION['password_reset_success'])) {
    $success_message = "Password reset successful! Please login with your new password.";
    unset($_SESSION['password_reset_success']);
}

// Check for account verification requirement
if (isset($_SESSION['require_verification'])) {
    $errors[] = "Please verify your email address before logging in. Check your inbox for the verification link.";
    unset($_SESSION['require_verification']);
}

// Generate CSRF token
log_debug("Generating CSRF token for login form");
$csrfToken = generateCSRFToken();
log_debug("CSRF token generated", ['token_prefix' => substr($csrfToken, 0, 10) . '...']);

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log form submission (with redacted sensitive data)
    $log_data = $_POST;
    unset($log_data['password']);
    log_form_submission("login", $log_data);
    
    try {
        // Validate CSRF token
        log_debug("Validating CSRF token", [
            'token_present' => !empty($_POST['csrf_token']),
            'session_csrf' => !empty($_SESSION['csrf_tokens']['general']) ? 'present' : 'missing'
        ]);
        
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            log_security("CSRF validation failed in login", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'email_attempt' => $_POST['email'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            throw new Exception("Invalid form submission. Please refresh the page and try again.");
        }
        
        log_info("CSRF validation passed for login attempt");
        
        // Sanitize inputs
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
        
        // Input validation
        if (empty($email)) {
            log_warning("Login attempt with empty email");
            throw new Exception("Email address is required.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            log_warning("Login attempt with invalid email format", ['email' => $email]);
            throw new Exception("Please enter a valid email address.");
        }
        
        if (empty($password)) {
            log_warning("Login attempt with empty password", ['email' => $email]);
            throw new Exception("Password is required.");
        }
        
        // Check for brute force protection
        if (isLoginBlocked($email)) {
            log_security("Login blocked due to too many failed attempts", ['email' => $email]);
            throw new Exception("Too many failed login attempts. Please try again in 15 minutes.");
        }
        
        // Attempt authentication
        log_info("Login attempt", [
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'remember_me' => $remember_me
        ]);
        
        $user = authenticateUser($email, $password);
        
        if (!$user) {
            // Increment failed attempts counter
            incrementFailedAttempts($email);
            
            log_security("Failed login attempt", [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'reason' => 'Invalid credentials or inactive account',
                'failed_attempts' => getFailedAttempts($email)
            ]);
            
            // Generic error message for security
            throw new Exception("Invalid email or password. Please try again.");
        }
        
        // Check if account is active
        if (isset($user['status']) && $user['status'] !== 'active') {
            log_security("Login attempt to inactive account", [
                'email' => $email,
                'status' => $user['status']
            ]);
            
            if ($user['status'] === 'pending') {
                throw new Exception("Your account is pending approval. Please contact support.");
            } elseif ($user['status'] === 'suspended') {
                throw new Exception("Your account has been suspended. Please contact support.");
            } elseif ($user['status'] === 'banned') {
                throw new Exception("Your account has been banned. Please contact support for more information.");
            } else {
                throw new Exception("Your account is not active. Please contact support.");
            }
        }
        
        // Check if email is verified (if verification is required)
        if (EMAIL_VERIFICATION_REQUIRED && isset($user['email_verified']) && !$user['email_verified']) {
            $_SESSION['require_verification'] = true;
            log_info("Login blocked - email not verified", ['email' => $email]);
            throw new Exception("Please verify your email address before logging in.");
        }
        
        // Clear failed attempts on successful login
        clearFailedAttempts($email);
        
        // Login successful
        log_info("Login successful", [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Create user session
        createUserSession($user, $remember_me);
        
        // Set welcome message
        $greeting = !empty($user['name']) ? $user['name'] : $user['email'];
        setFlashMessage("Welcome back, {$greeting}!", "success");
        
        // Log user activity
        log_user_action("login", $user['id'], [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'remember_me' => $remember_me,
            'session_id' => session_id()
        ]);
        
        // Update last login timestamp
        updateLastLogin($user['id']);
        
        // Handle remember me functionality
        if ($remember_me) {
            setRememberMeCookie($user['id']);
        }
        
        // Prepare redirect URL
        $redirectUrl = $_SESSION['redirect_after_login'] ?? redirectBasedOnRole();
        log_info("Redirecting user after login", [
            'user_id' => $user['id'],
            'redirect_to' => $redirectUrl
        ]);
        
        // Clear redirect URL from session
        if (isset($_SESSION['redirect_after_login'])) {
            unset($_SESSION['redirect_after_login']);
        }
        
        // Redirect to appropriate page
        header("Location: " . $redirectUrl);
        exit;
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        $errors[] = $errorMessage;
        
        log_error("Login error for {$email}: " . $errorMessage, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'email' => $email
        ]);
        
        // Add small delay on failed attempts to prevent brute force
        usleep(500000); // 0.5 second delay
        
        // Set form values for re-population
        $email = sanitizeInput($_POST['email'] ?? '');
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
    }
}

$title = "Login to PeerCart";
include __DIR__ . '/../../includes/header.php';

// Log page render
log_debug("Rendering login form");
?>
<div class="auth-container auth-page">
    <div class="glass-card">
        <!-- Logo Section -->
        <div class="auth-logo mb-4">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="PeerCart Logo" 
                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/images/logo-placeholder.png'">
            <h2>PeerCart</h2>
            <p class="text-muted mt-1">Buy & Sell with Confidence</p>
        </div>

        <!-- Title -->
        <h2 class="auth-title mb-4">Welcome Back</h2>
        <p class="text-center text-muted mb-4">Sign in to continue to your account</p>

        <!-- Display Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php displayFlashMessage(); ?>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div>
                        <?php foreach($errors as $err): ?>
                            <p class="mb-1"><?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Social Login (Optional) -->
        <?php if (SOCIAL_LOGIN_ENABLED): ?>
        <div class="social-login">
            <div class="divider">
                <span class="divider-text">Or continue with</span>
            </div>
            <div class="social-buttons">
                <a href="<?= BASE_URL ?>/includes/auth/social-login.php?provider=google" class="social-btn google">
                    <i class="fab fa-google"></i>
                    <span>Google</span>
                </a>
                <a href="<?= BASE_URL ?>/includes/auth/social-login.php?provider=facebook" class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                    <span>Facebook</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" class="auth-form" novalidate id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-group mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-1"></i>Email Address
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       class="form-control <?= !empty($errors) && !empty($email) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($email) ?>" 
                       required
                       placeholder="you@example.com"
                       autocomplete="email"
                       aria-describedby="emailHelp">
                <small id="emailHelp" class="form-text text-muted">
                    Enter the email address you used during registration
                </small>
            </div>
            
            <div class="form-group mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-1"></i>Password
                </label>
                <div class="input-group">
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-control" 
                           required
                           placeholder="Enter your password"
                           autocomplete="current-password"
                           minlength="8"
                           aria-describedby="passwordHelp">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small id="passwordHelp" class="form-text text-muted">
                    <a href="<?= BASE_URL ?>/includes/auth/forgot-password.php" class="auth-link">
                        <i class="fas fa-key me-1"></i>Forgot password?
                    </a>
                </small>
            </div>
            
            <div class="form-group mb-4">
                <div class="form-check">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="rememberMe" 
                           name="remember_me" 
                           value="1"
                           <?= $remember_me ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rememberMe">
                        Keep me logged in for 30 days
                    </label>
                </div>
            </div>
            
            <button type="submit" 
                    class="btn btn-primary btn-lg w-100 mb-3" 
                    id="loginBtn"
                    data-loading-text="Signing in...">
                <span id="loginText">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </span>
                <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    By signing in, you agree to our 
                    <a href="<?= BASE_URL ?>/pages/terms.php" class="auth-link">Terms of Service</a>
                    and 
                    <a href="<?= BASE_URL ?>/pages/privacy.php" class="auth-link">Privacy Policy</a>
                </small>
            </div>
        </form>

        <!-- Registration Link -->
        <div class="auth-footer mt-4">
            <p class="mb-2">Don't have an account?</p>
            <a href="<?= BASE_URL ?>/includes/auth.php" class="btn btn-outline-primary w-100">
                <i class="fas fa-user-plus me-2"></i>Create New Account
            </a>
            <p class="mt-3 mb-0 text-muted">
                <small>
                    Need help? 
                    <a href="<?= BASE_URL ?>/pages/contact.php" class="auth-link">
                        <i class="fas fa-life-ring me-1"></i>Contact Support
                    </a>
                </small>
            </p>
        </div>
    </div>
</div>

<!-- Security Info (only for development) -->
<?php if (ENVIRONMENT === 'development'): ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Debug Information</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="toggleDebug()">Toggle</button>
        </div>
        <div class="card-body d-none" id="debugInfo">
            <div class="row">
                <div class="col-md-6">
                    <h6>Session Info:</h6>
                    <pre class="bg-light p-2 small"><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
                </div>
                <div class="col-md-6">
                    <h6>Security Info:</h6>
                    <ul class="list-unstyled small">
                        <li><strong>Session ID:</strong> <?= session_id() ?></li>
                        <li><strong>CSRF Token:</strong> <?= htmlspecialchars(substr($csrfToken, 0, 20) . '...') ?></li>
                        <li><strong>IP Address:</strong> <?= $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ?></li>
                        <li><strong>User Agent:</strong> <?= htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 50)) ?>...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Password visibility toggle
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        if (type === 'text') {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            this.setAttribute('aria-label', 'Hide password');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            this.setAttribute('aria-label', 'Show password');
        }
    });
}

// Form submission handler
const loginForm = document.getElementById('loginForm');
const loginBtn = document.getElementById('loginBtn');
const loginText = document.getElementById('loginText');
const loginSpinner = document.getElementById('loginSpinner');

if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        // Client-side validation
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!email || !password) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        
        if (!validateEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return;
        }
        
        // Show loading state
        loginBtn.disabled = true;
        loginText.classList.add('d-none');
        loginSpinner.classList.remove('d-none');
        loginBtn.setAttribute('aria-busy', 'true');
        
        // Log form submission to console for debugging
        console.log('Login form submitted - client side validation passed');
    });
}

// Email validation function
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Auto-focus email field on page load
document.addEventListener('DOMContentLoaded', function() {
    const emailField = document.getElementById('email');
    if (emailField && !emailField.value) {
        setTimeout(() => emailField.focus(), 300);
    }
    
    // Add input validation feedback
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '' && this.required) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
        });
    });
    
    // Handle "Enter" key in password field
    document.getElementById('password')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !loginBtn.disabled) {
            loginForm.submit();
        }
    });
});

// Toggle debug info
function toggleDebug() {
    const debugInfo = document.getElementById('debugInfo');
    debugInfo.classList.toggle('d-none');
}

// Check for URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('expired')) {
        showNotification('Your session has expired. Please login again.', 'warning');
    }
    
    if (urlParams.has('registered')) {
        showNotification('Registration successful! Please check your email for verification.', 'success');
    }
});

function showNotification(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.querySelector('.glass-card')?.prepend(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
}
</script>

<?php 
// Final log before page ends
log_debug("Login page rendering complete");

include __DIR__ . '/../../includes/footer.php'; 
?>