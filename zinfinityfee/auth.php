<?php
// pages/auth.php - COMBINED LOGIN & REGISTER WITH CARD FLIP

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

$additionalStyles = [
    'pages/auth' // This points to assets/css/pages/auth.css
];

// Store current page for redirect after login/register
if (!isset($_SESSION['redirect_url'])) {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

        // Only store internal pages except auth.php, logout, home.php
        if (
            strpos($referer, $site_url) === 0 &&
            !str_contains($referer, 'auth.php') &&
            !str_contains($referer, 'logout') &&
            !str_contains($referer, 'home.php')
        ) {
            $_SESSION['redirect_url'] = $referer;
        }
    }
}

// Redirect from GET 'redirect' param
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect_url = urldecode($_GET['redirect']);
    if (strpos($redirect_url, '/') === 0 || strpos($redirect_url, 'pages/') === 0 || strpos($redirect_url, BASE_URL) === 0) {
        $_SESSION['redirect_url'] = $redirect_url;
    }
}

// Redirect from POST 'redirect'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redirect']) && !empty($_POST['redirect'])) {
    $redirect_param = $_POST['redirect'];
    if (strpos($redirect_param, '/') === 0 || strpos($redirect_param, 'pages/') === 0 || strpos($redirect_param, BASE_URL) === 0) {
        $_SESSION['redirect_url'] = $redirect_param;
    }
}

// Redirect already logged-in users
if (isLoggedIn()) {
    header("Location: " . redirectBasedOnRole());
    exit;
}

// Determine mode
$mode = $_GET['mode'] ?? 'login';
$errors = [];
$success_msg = '';

// --- LOGIN HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        try {
            $db = db()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['last_activity'] = time();
                
                $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => "Welcome back, " . htmlspecialchars($user['name']) . "!"
                ];

                $redirect_url = $_SESSION['redirect_url'] ?? redirectBasedOnRole($user['user_type']);
                unset($_SESSION['redirect_url']);

                header("Location: " . $redirect_url);
                exit;

            } else {
                $errors[] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// --- REGISTER HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'register') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'buyer';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || strlen($name) < 2) $errors[] = "First name must be at least 2 characters";
    if (empty($surname) || strlen($surname) < 2) $errors[] = "Last name must be at least 2 characters";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email address is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    elseif ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (!isset($_POST['terms'])) $errors[] = "You must agree to the Terms of Service and Privacy Policy";

    $user_type = in_array($user_type, ['buyer', 'seller']) ? $user_type : 'buyer';

    if (empty($errors)) {
        try {
            $db = db()->getConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email address is already registered";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users 
                    (name, surname, email, password, user_type, phone, created_at, last_login, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), 1)");
                $stmt->execute([$name, $surname, $email, $hashed_password, $user_type, $phone]);

                $user_id = $db->lastInsertId();
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $new_user = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['user_id'] = $new_user['id'];
                $_SESSION['user_name'] = $new_user['name'];
                $_SESSION['user_type'] = $new_user['user_type'];
                $_SESSION['user_email'] = $new_user['email'];
                $_SESSION['last_activity'] = time();

                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => "Welcome to PeerCart, $name!"
                ];

                $redirect_url = $_SESSION['redirect_url'] ?? redirectBasedOnRole($new_user['user_type']);
                unset($_SESSION['redirect_url']);

                header("Location: " . $redirect_url);
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
            if (DEBUG_MODE) $errors[] = "Debug: " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Page title
$title = ($mode === 'login' ? "Login to PeerCart" : "Join PeerCart - Register");
$additionalStyles = ['pages/auth'];

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-main-container">
        <div class="flip-card-container">
            <div class="flip-card <?= $mode === 'register' ? 'flipped' : '' ?>">
                <!-- LOGIN SIDE (Front) -->
                <div class="card-side front">
                    <div class="card-header">
                        <div class="card-logo">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h2 class="card-title">Welcome Back</h2>
                        <p class="card-subtitle">Sign in to your account</p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Display Messages -->
                        <?php if ($success_msg): ?>
                            <div class="form-success">
                                <i class="fas fa-check-circle"></i>
                                <span><?= htmlspecialchars($success_msg) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($errors)): ?>
                            <?php foreach($errors as $err): ?>
                                <div class="form-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span><?= htmlspecialchars($err) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="post" class="auth-form" id="loginForm">
                            <input type="hidden" name="form_type" value="login">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
                            
                            <div class="form-group">
                                <label for="login_email" class="form-label">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       id="login_email" 
                                       class="form-input" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                       required
                                       placeholder="you@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="login_password" class="form-label">Password</label>
                                <input type="password" 
                                       name="password" 
                                       id="login_password" 
                                       class="form-input" 
                                       required
                                       placeholder="••••••••">
                                <button type="button" class="password-toggle" data-target="login_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div style="margin-top: 5px;">
                                    <a href="<?= BASE_URL ?>/pages/reset-password.php" style="color: #4361ee; text-decoration: none; font-size: 13px;">
                                        Forgot password?
                                    </a>
                                </div>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       name="remember_me" 
                                       id="remember_me" 
                                       value="1"
                                       <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                                <label for="remember_me">Remember me for 30 days</label>
                            </div>
                            
                            <button type="submit" class="btn-submit" id="loginBtn">
                                <span id="loginText">Sign In</span>
                                <div class="spinner" style="display: none;"></div>
                            </button>
                        </form>

                        <!-- Flip to Register -->
                        <button type="button" class="flip-button" data-flip-to="register">
                            <i class="fas fa-user-plus"></i>
                            Don't have an account? Sign up
                        </button>
                        
                        <!-- Demo Account Info -->
                        <div class="demo-info" style="margin-top: 15px; padding: 10px; background: rgba(67, 97, 238, 0.05); border-radius: 8px; font-size: 12px;">
                            <p style="margin: 0; color: #666;"><strong>Demo Account:</strong> demo@peercart.com / demo123!</p>
                        </div>
                    </div>
                </div>

                <!-- REGISTER SIDE (Back) -->
                <div class="card-side back">
                    <div class="card-header">
                        <div class="card-logo">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h2 class="card-title">Create Account</h2>
                        <p class="card-subtitle">Join our community today</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if(!empty($errors)): ?>
                            <?php foreach($errors as $err): ?>
                                <div class="form-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span><?= htmlspecialchars($err) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Step 1 Form -->
                        <div class="progress-steps">
                            <div class="progress-step active">
                                <div class="step-circle">1</div>
                                <span class="step-label">Account Info</span>
                            </div>
                        </div>

                        <form method="post" class="auth-form" id="registerForm">
                            <input type="hidden" name="form_type" value="register">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
                            
                            <div class="two-column">
                                <div class="form-group">
                                    <label for="name" class="form-label">First Name</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           class="form-input" 
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                           required
                                           placeholder="John"
                                           minlength="2"
                                           maxlength="50">
                                </div>
                                <div class="form-group">
                                    <label for="surname" class="form-label">Last Name</label>
                                    <input type="text" 
                                           name="surname" 
                                           id="surname" 
                                           class="form-input" 
                                           value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" 
                                           required
                                           placeholder="Doe"
                                           minlength="2"
                                           maxlength="50">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="register_email" class="form-label">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       id="register_email" 
                                       class="form-input" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                       required
                                       placeholder="you@example.com">
                            </div>

                            <div class="two-column">
                                <div class="form-group">
                                    <label for="register_password" class="form-label">Password</label>
                                    <input type="password" 
                                           name="password" 
                                           id="register_password" 
                                           class="form-input" 
                                           required
                                           placeholder="••••••••"
                                           minlength="8">
                                    <button type="button" class="password-toggle" data-target="register_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="password-strength">
                                        <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" 
                                           name="confirm_password" 
                                           id="confirm_password" 
                                           class="form-input" 
                                           required
                                           placeholder="••••••••">
                                    <button type="button" class="password-toggle" data-target="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" 
                                       name="phone" 
                                       id="phone" 
                                       class="form-input" 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                                       required
                                       placeholder="+27 12 345 6789"
                                       pattern="^\+?[0-9\s\-\(\)]+$">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Account Type</label>
                                <div class="account-type-grid">
                                    <div class="account-type-option <?= (($_POST['user_type'] ?? 'buyer')=='buyer')?'selected':'' ?>" data-value="buyer">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Buyer</span>
                                        <input type="radio" name="user_type" value="buyer" <?= (($_POST['user_type'] ?? 'buyer')=='buyer')?'checked':'' ?> required>
                                    </div>
                                    <div class="account-type-option <?= (($_POST['user_type'] ?? 'buyer')=='seller')?'selected':'' ?>" data-value="seller">
                                        <i class="fas fa-store"></i>
                                        <span>Seller</span>
                                        <input type="radio" name="user_type" value="seller" <?= (($_POST['user_type'] ?? 'buyer')=='seller')?'checked':'' ?>>
                                    </div>
                                </div>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       name="terms" 
                                       id="terms" 
                                       value="1"
                                       <?= isset($_POST['terms']) ? 'checked' : '' ?>
                                       required>
                                <label for="terms">I agree to <a href="<?= BASE_URL ?>/pages/terms.php" target="_blank">Terms of Service</a> and <a href="<?= BASE_URL ?>/pages/privacy.php" target="_blank">Privacy Policy</a></label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       name="newsletter" 
                                       id="newsletter" 
                                       value="1"
                                       <?= isset($_POST['newsletter']) ? 'checked' : '' ?>>
                                <label for="newsletter">Subscribe to our newsletter for updates</label>
                            </div>

                            <div class="two-column" style="margin-top: 20px;">
                                <button type="button" class="flip-button" data-flip-to="login">
                                    <i class="fas fa-arrow-left"></i> Back to Login
                                </button>
                                <button type="submit" class="btn-submit" id="registerBtn">
                                    <span id="registerText">Create Account</span>
                                    <i class="fas fa-check"></i>
                                    <div class="spinner" style="display: none;"></div>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const flipCard = document.querySelector('.flip-card');
    const flipButtons = document.querySelectorAll('[data-flip-to]');
    
    // Handle flip button clicks
    flipButtons.forEach(button => {
        button.addEventListener('click', function() {
            const flipTo = this.getAttribute('data-flip-to');
            const urlParams = new URLSearchParams(window.location.search);
            const redirect = urlParams.get('redirect');
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('mode', flipTo);
            if (redirect) {
                url.searchParams.set('redirect', redirect);
            }
            window.history.pushState({}, '', url);
            
            // Flip the card
            if (flipTo === 'register') {
                flipCard.classList.add('flipped');
            } else {
                flipCard.classList.remove('flipped');
            }
            
            // Focus first input after flip
            setTimeout(() => {
                const side = flipTo === 'register' ? '.back' : '.front';
                const firstInput = document.querySelector(`${side} input:not([type="hidden"]), ${side} select, ${side} textarea`);
                if (firstInput) {
                    firstInput.focus();
                }
            }, 400);
        });
    });
    
    // Password toggle functionality
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            if (passwordInput) {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    });
    
    // Password strength checker for registration
    const passwordInput = document.getElementById('register_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('passwordStrengthMeter');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (!password) {
                strengthMeter.style.width = '0%';
                strengthMeter.style.backgroundColor = '#e0e0e0';
                return;
            }
            
            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            score = Math.min(score, 4);
            
            // Set width based on score
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            const colors = ['#ff6b6b', '#ffa94d', '#ffd43b', '#51cf66', '#40c057'];
            
            strengthMeter.style.width = widths[score];
            strengthMeter.style.backgroundColor = colors[score];
            
            // Real-time password match validation
            if (confirmPasswordInput && confirmPasswordInput.value) {
                validatePasswordMatch();
            }
        });
        
        // Password match validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        }
        
        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword && password !== confirmPassword) {
                confirmPasswordInput.style.borderColor = '#e74c3c';
                confirmPasswordInput.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
            } else if (confirmPassword) {
                confirmPasswordInput.style.borderColor = '#51cf66';
                confirmPasswordInput.style.boxShadow = '0 0 0 3px rgba(81, 207, 102, 0.1)';
            }
        }
    }
    
    // Account type selection
    document.querySelectorAll('.account-type-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            
            // Remove selected class from all options
            document.querySelectorAll('.account-type-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });
    
    // Form submission loading states
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    
    if (loginForm && loginBtn) {
        loginForm.addEventListener('submit', function() {
            const spinner = loginBtn.querySelector('.spinner');
            const text = loginBtn.querySelector('#loginText');
            
            if (spinner && text) {
                text.style.display = 'none';
                spinner.style.display = 'block';
                loginBtn.disabled = true;
            }
        });
    }
    
    // Registration form validation
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    
    if (registerForm && registerBtn) {
        registerForm.addEventListener('submit', function(e) {
            // Validate passwords match
            const password = document.getElementById('register_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return false;
            }
            
            // Validate terms agreement
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy.');
                return false;
            }
            
            // Show loading state
            const spinner = registerBtn.querySelector('.spinner');
            const text = registerBtn.querySelector('#registerText');
            const icon = registerBtn.querySelector('.fa-check');
            
            if (spinner && text && icon) {
                text.style.display = 'none';
                icon.style.display = 'none';
                spinner.style.display = 'block';
                registerBtn.disabled = true;
            }
        });
    }
    
    // Auto-focus appropriate field
    setTimeout(() => {
        if (<?= $mode === 'login' ? 'true' : 'false' ?>) {
            const emailInput = document.getElementById('login_email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        } else {
            const nameInput = document.getElementById('name');
            if (nameInput) nameInput.focus();
        }
    }, 300);
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const mode = urlParams.get('mode') || 'login';
        
        if (mode === 'register' && !flipCard.classList.contains('flipped')) {
            flipCard.classList.add('flipped');
        } else if (mode === 'login' && flipCard.classList.contains('flipped')) {
            flipCard.classList.remove('flipped');
        }
    });
    
    // Add initial form data if there were errors
    const urlParams = new URLSearchParams(window.location.search);
    const currentMode = urlParams.get('mode') || 'login';
    if (currentMode === 'register' && !flipCard.classList.contains('flipped')) {
        flipCard.classList.add('flipped');
    }
    
    // Phone number formatting
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (!value.startsWith('27')) {
                    value = '27' + value;
                }
                // Format: +27 12 345 6789
                if (value.length > 2) {
                    value = '+27 ' + value.substr(2);
                }
                if (value.length > 6) {
                    value = value.substr(0, 7) + ' ' + value.substr(7);
                }
                if (value.length > 10) {
                    value = value.substr(0, 10) + ' ' + value.substr(10);
                }
            }
            e.target.value = value;
        });
    }
    
    // Input validation for character limits
    document.querySelectorAll('input[maxlength]').forEach(input => {
        input.addEventListener('input', function() {
            const maxLength = parseInt(this.getAttribute('maxlength'));
            if (this.value.length > maxLength) {
                this.value = this.value.substr(0, maxLength);
            }
        });
    });
});
</script>