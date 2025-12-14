<?php
// pages/auth.php - COMBINED LOGIN & REGISTER WITH CARD FLIP
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect already logged-in users
if (isLoggedIn()) {
    header("Location: " . redirectBasedOnRole());
    exit;
}

// Determine mode (login or register)
$mode = $_GET['mode'] ?? 'login';
$errors = [];
$success_msg = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
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
            $db = Database::getInstance()->getConnection();
            
            // Check if user exists
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Create user session
                createUserSession($user);
                
                // Set success message
                $success_msg = "Login successful! Redirecting...";
                
                // Set flash message for next page
                setFlashMessage("Welcome back, " . htmlspecialchars($user['name']) . "!", "success");
                
                // Redirect based on role
                $redirect_url = redirectBasedOnRole();
                
                // Add JavaScript redirect
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "' . $redirect_url . '";
                    }, 1500);
                </script>';
                
            } else {
                $errors[] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'buyer';
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate inputs
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "First name must be at least 2 characters";
    }
    
    if (empty($surname) || strlen($surname) < 2) {
        $errors[] = "Last name must be at least 2 characters";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!in_array($user_type, ['buyer', 'seller', 'both'])) {
        $errors[] = "Invalid user type";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email address is already registered";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate referral code
                $referral_code = strtoupper(substr(md5(uniqid($email, true)), 0, 8));
                
                // Insert new user
                $stmt = $db->prepare("INSERT INTO users 
                    (name, surname, email, password, user_type, phone, referral_code, created_at, last_login)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                
                $stmt->execute([
                    $name, $surname, $email, $hashed_password, $user_type, $phone, $referral_code
                ]);
                
                $user_id = $db->lastInsertId();
                
                // Log the user in automatically
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $new_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                createUserSession($new_user);
                
                $success_msg = "Registration successful! Welcome to PeerCart.";
                setFlashMessage("Welcome to PeerCart, $name!", "success");
                
                // Redirect to appropriate page
                $redirect_url = redirectBasedOnRole();
                
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "' . $redirect_url . '";
                    }, 1500);
                </script>';
            }
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Set page title
$title = ($mode === 'login' ? "Login to PeerCart" : "Join PeerCart - Register");

// Include header with auth.css
$css_files = ['auth.css'];
include __DIR__ . '/header.php';
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
                                    <a href="forgot-password.php" style="color: #4361ee; text-decoration: none; font-size: 13px;">
                                        Forgot password?
                                    </a>
                                </div>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       name="remember_me" 
                                       id="remember_me" 
                                       value="1">
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

                        <form method="post" class="auth-form" id="step1Form">
                            <input type="hidden" name="form_type" value="register">
                            <input type="hidden" name="step" value="1">
                            
                            <div class="two-column">
                                <div class="form-group">
                                    <label for="name" class="form-label">First Name</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           class="form-input" 
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                           required
                                           placeholder="John">
                                </div>
                                <div class="form-group">
                                    <label for="surname" class="form-label">Last Name</label>
                                    <input type="text" 
                                           name="surname" 
                                           id="surname" 
                                           class="form-input" 
                                           value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" 
                                           required
                                           placeholder="Doe">
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
                                           placeholder="••••••••">
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
                                       placeholder="+27 12 345 6789">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Account Type</label>
                                <div class="account-type-grid">
                                    <div class="account-type-option <?= (($_POST['user_type'] ?? 'buyer')=='buyer')?'selected':'' ?>" data-value="buyer">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Buyer</span>
                                        <input type="radio" name="user_type" value="buyer" <?= (($_POST['user_type'] ?? 'buyer')=='buyer')?'checked':'' ?>>
                                    </div>
                                    <div class="account-type-option <?= (($_POST['user_type'] ?? 'buyer')=='seller')?'selected':'' ?>" data-value="seller">
                                        <i class="fas fa-store"></i>
                                        <span>Seller</span>
                                        <input type="radio" name="user_type" value="seller" <?= (($_POST['user_type'] ?? 'buyer')=='seller')?'checked':'' ?>>
                                    </div>
                                    <div class="account-type-option <?= (($_POST['user_type'] ?? 'buyer')=='both')?'selected':'' ?>" data-value="both">
                                        <i class="fas fa-exchange-alt"></i>
                                        <span>Both</span>
                                        <input type="radio" name="user_type" value="both" <?= (($_POST['user_type'] ?? 'buyer')=='both')?'checked':'' ?>>
                                    </div>
                                </div>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       name="terms" 
                                       id="terms" 
                                       value="1"
                                       required>
                                <label for="terms">I agree to Terms of Service and Privacy Policy</label>
                            </div>

                            <div class="two-column" style="margin-top: 20px;">
                                <button type="button" class="flip-button" data-flip-to="login">
                                    <i class="fas fa-arrow-left"></i> Back to Login
                                </button>
                                <button type="submit" class="btn-submit">
                                    Create Account <i class="fas fa-check"></i>
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
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('mode', flipTo);
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
        });
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
});
</script>

<?php 
include __DIR__ . '/../includes/footer.php'; 
?>