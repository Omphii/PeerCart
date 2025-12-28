<?php
// pages/auth.php - COMBINED LOGIN & REGISTER WITH 2-STEP REGISTRATION

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
$registrationStep = 1;

// South African provinces
$southAfricanProvinces = [
    '' => 'Select Province',
    'eastern_cape' => 'Eastern Cape',
    'free_state' => 'Free State',
    'gauteng' => 'Gauteng',
    'kwazulu_natal' => 'KwaZulu-Natal',
    'limpopo' => 'Limpopo',
    'mpumalanga' => 'Mpumalanga',
    'north_west' => 'North West',
    'northern_cape' => 'Northern Cape',
    'western_cape' => 'Western Cape'
];

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
                
                // Create user preferences if they don't exist
                $prefStmt = $db->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
                $prefStmt->execute([$user['id']]);
                if (!$prefStmt->fetch()) {
                    $prefInsert = $db->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
                    $prefInsert->execute([$user['id']]);
                }
                
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
    // Determine step from form
    $registrationStep = (int) ($_POST['registration_step'] ?? 1);
    
    if ($registrationStep === 1) {
        // Step 1: Basic Information
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_type = $_POST['user_type'] ?? 'buyer';
        $phone = trim($_POST['phone'] ?? '');
        $newsletter = isset($_POST['newsletter']);

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
                    // Store step 1 data in session for step 2
                    $_SESSION['registration_data'] = [
                        'step' => 1,
                        'name' => $name,
                        'surname' => $surname,
                        'email' => $email,
                        'password' => $password,
                        'user_type' => $user_type,
                        'phone' => $phone,
                        'newsletter' => $newsletter
                    ];
                    $registrationStep = 2;
                }
            } catch (PDOException $e) {
                $errors[] = "Registration failed. Please try again.";
                if (DEBUG_MODE) $errors[] = "Debug: " . $e->getMessage();
                error_log("Registration error: " . $e->getMessage());
            }
        }
    } elseif ($registrationStep === 2) {
        // Step 2: Address Information
        if (!isset($_SESSION['registration_data'])) {
            $errors[] = "Registration session expired. Please start over.";
            $registrationStep = 1;
        } else {
            $step1Data = $_SESSION['registration_data'];
            
            // Get address data
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');
            $street_address = trim($_POST['street_address'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $suburb = trim($_POST['suburb'] ?? '');
            
            // Additional seller info (OPTIONAL)
            $business_name = trim($_POST['business_name'] ?? '');
            $business_registration = trim($_POST['business_registration'] ?? '');
            
            // Validation (all fields required except business info)
            if (empty($city)) $errors[] = "City is required";
            if (empty($province) || $province === '') $errors[] = "Province is required";
            if (empty($street_address)) $errors[] = "Street address is required";
            if (empty($postal_code) || !preg_match('/^\d{4}$/', $postal_code)) $errors[] = "Valid 4-digit postal code is required";
            if (empty($suburb)) $errors[] = "Suburb is required";
            
            // REMOVED: Seller-specific validation - Business name is now optional
            
            if (empty($errors)) {
                try {
                    $db = db()->getConnection();
                    
                    // Hash password
                    $hashed_password = password_hash($step1Data['password'], PASSWORD_DEFAULT);
                    
                    // Start transaction
                    $db->beginTransaction();
                    
                    // Insert user
                    $stmt = $db->prepare("INSERT INTO users 
                        (name, surname, email, password, user_type, phone, 
                         city, province, street_address, postal_code, suburb,
                         created_at, last_login, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 1)");
                    
                    $stmt->execute([
                        $step1Data['name'],
                        $step1Data['surname'],
                        $step1Data['email'],
                        $hashed_password,
                        $step1Data['user_type'],
                        $step1Data['phone'],
                        $city,
                        $province,
                        $street_address,
                        $postal_code,
                        $suburb
                    ]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // Insert user preferences
                    $prefStmt = $db->prepare("INSERT INTO user_preferences 
                        (user_id, notifications_newsletter, notifications_promotions) 
                        VALUES (?, ?, ?)");
                    $prefStmt->execute([
                        $user_id,
                        $step1Data['newsletter'] ? 1 : 0,
                        $step1Data['newsletter'] ? 1 : 0
                    ]);
                    
                    // Insert primary address into addresses table
                    $addressStmt = $db->prepare("INSERT INTO addresses 
                        (user_id, address_type, contact_name, contact_phone, house_number, 
                         street_name, suburb, city, province, postal_code, country, is_default, created_at)
                        VALUES (?, 'both', ?, ?, ?, ?, ?, ?, ?, ?, 'South Africa', 1, NOW())");
                    
                    // Extract house number from street address (simplified)
                    $house_number = preg_match('/^(\d+)/', $street_address, $matches) ? $matches[1] : '1';
                    $street_name = preg_replace('/^\d+\s*/', '', $street_address);
                    
                    $addressStmt->execute([
                        $user_id,
                        $step1Data['name'] . ' ' . $step1Data['surname'],
                        $step1Data['phone'],
                        $house_number,
                        $street_name,
                        $suburb,
                        $city,
                        $province,
                        $postal_code
                    ]);
                    
                    // Commit transaction
                    $db->commit();
                    
                    // Get the new user
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $new_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Set session
                    $_SESSION['user_id'] = $new_user['id'];
                    $_SESSION['user_name'] = $new_user['name'];
                    $_SESSION['user_type'] = $new_user['user_type'];
                    $_SESSION['user_email'] = $new_user['email'];
                    $_SESSION['last_activity'] = time();
                    
                    // Clear registration data
                    unset($_SESSION['registration_data']);
                    
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => "Welcome to PeerCart, " . htmlspecialchars($step1Data['name']) . "!"
                    ];

                    $redirect_url = $_SESSION['redirect_url'] ?? redirectBasedOnRole($new_user['user_type']);
                    unset($_SESSION['redirect_url']);

                    header("Location: " . $redirect_url);
                    exit;
                    
                } catch (PDOException $e) {
                    $db->rollBack();
                    $errors[] = "Registration failed. Please try again.";
                    if (DEBUG_MODE) $errors[] = "Debug: " . $e->getMessage();
                    error_log("Registration error: " . $e->getMessage());
                    $registrationStep = 1;
                    unset($_SESSION['registration_data']);
                }
            } else {
                $registrationStep = 2;
            }
        }
    }
}

// Check if we're in step 2 from session
if ($mode === 'register' && isset($_SESSION['registration_data']) && $_SESSION['registration_data']['step'] == 1) {
    $registrationStep = 2;
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
                        
                        <?php if(!empty($errors) && $mode === 'login'): ?>
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
                            <p style="margin: 0; color: #666;"><strong>Demo Account:</strong> demo@peercart.com / demo123</p>
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
                        <?php if(!empty($errors) && $mode === 'register'): ?>
                            <?php foreach($errors as $err): ?>
                                <div class="form-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span><?= htmlspecialchars($err) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Progress Steps -->
                        <div class="progress-steps">
                            <div class="progress-step <?= $registrationStep >= 1 ? 'active' : '' ?>" data-step="1">
                                <div class="step-circle">1</div>
                                <span class="step-label">Account Info</span>
                            </div>
                            <div class="progress-step <?= $registrationStep >= 2 ? 'active' : '' ?>" data-step="2">
                                <div class="step-circle">2</div>
                                <span class="step-label">Address Details</span>
                            </div>
                        </div>

                        <!-- STEP 1: Basic Information -->
                        <div id="step1" class="step-content" style="<?= $registrationStep == 1 ? 'display: block;' : 'display: none;' ?>">
                            <form method="post" class="auth-form" id="registerFormStep1">
                                <input type="hidden" name="form_type" value="register">
                                <input type="hidden" name="registration_step" value="1">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
                                
                                <div class="two-column">
                                    <div class="form-group">
                                        <label for="name" class="form-label">First Name</label>
                                        <input type="text" 
                                               name="name" 
                                               id="name" 
                                               class="form-input" 
                                               value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['registration_data']['name'] ?? '')) ?>" 
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
                                               value="<?= htmlspecialchars($_POST['surname'] ?? ($_SESSION['registration_data']['surname'] ?? '')) ?>" 
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
                                           value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['registration_data']['email'] ?? '')) ?>" 
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
                                           value="<?= htmlspecialchars($_POST['phone'] ?? ($_SESSION['registration_data']['phone'] ?? '')) ?>" 
                                           required
                                           placeholder="+27 12 345 6789"
                                           pattern="^\+?[0-9\s\-\(\)]+$">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Account Type</label>
                                    <div class="account-type-grid">
                                        <div class="account-type-option <?= (($_POST['user_type'] ?? ($_SESSION['registration_data']['user_type'] ?? 'buyer'))=='buyer')?'selected':'' ?>" data-value="buyer">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>Buyer</span>
                                            <input type="radio" name="user_type" value="buyer" <?= (($_POST['user_type'] ?? ($_SESSION['registration_data']['user_type'] ?? 'buyer'))=='buyer')?'checked':'' ?> required>
                                        </div>
                                        <div class="account-type-option <?= (($_POST['user_type'] ?? ($_SESSION['registration_data']['user_type'] ?? 'buyer'))=='seller')?'selected':'' ?>" data-value="seller">
                                            <i class="fas fa-store"></i>
                                            <span>Seller</span>
                                            <input type="radio" name="user_type" value="seller" <?= (($_POST['user_type'] ?? ($_SESSION['registration_data']['user_type'] ?? 'buyer'))=='seller')?'checked':'' ?>>
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
                                           <?= isset($_POST['newsletter']) || (isset($_SESSION['registration_data']['newsletter']) && $_SESSION['registration_data']['newsletter']) ? 'checked' : '' ?>>
                                    <label for="newsletter">Subscribe to our newsletter for updates</label>
                                </div>

                                <div class="two-column" style="margin-top: 20px;">
                                    <button type="button" class="flip-button" data-flip-to="login">
                                        <i class="fas fa-arrow-left"></i> Back to Login
                                    </button>
                                    <button type="button" class="btn-submit next-step" data-next="2" id="nextStep1">
                                        <span id="step1Text">Continue to Step 2</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- STEP 2: Address Details -->
                        <div id="step2" class="step-content" style="<?= $registrationStep == 2 ? 'display: block;' : 'display: none;' ?>">
                            <form method="post" class="auth-form" id="registerFormStep2">
                                <input type="hidden" name="form_type" value="register">
                                <input type="hidden" name="registration_step" value="2">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
                                
                                <div class="form-group">
                                    <label for="street_address" class="form-label">Street Address</label>
                                    <input type="text" 
                                           name="street_address" 
                                           id="street_address" 
                                           class="form-input" 
                                           value="<?= htmlspecialchars($_POST['street_address'] ?? '') ?>" 
                                           required
                                           placeholder="123 Main Street"
                                           minlength="5">
                                    <small class="form-hint">Include house number and street name</small>
                                </div>

                                <div class="two-column">
                                    <div class="form-group">
                                        <label for="suburb" class="form-label">Suburb</label>
                                        <input type="text" 
                                               name="suburb" 
                                               id="suburb" 
                                               class="form-input" 
                                               value="<?= htmlspecialchars($_POST['suburb'] ?? '') ?>" 
                                               required
                                               placeholder="Suburb name">
                                    </div>
                                    <div class="form-group">
                                        <label for="city" class="form-label">City/Town</label>
                                        <input type="text" 
                                               name="city" 
                                               id="city" 
                                               class="form-input" 
                                               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" 
                                               required
                                               placeholder="Johannesburg">
                                    </div>
                                </div>

                                <div class="two-column">
                                    <div class="form-group">
                                        <label for="province" class="form-label">Province</label>
                                        <select name="province" id="province" class="form-input" required>
                                            <?php foreach($southAfricanProvinces as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= (($_POST['province'] ?? '') == $value) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" 
                                               name="postal_code" 
                                               id="postal_code" 
                                               class="form-input" 
                                               value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>" 
                                               required
                                               placeholder="2001"
                                               pattern="\d{4}"
                                               maxlength="4">
                                    </div>
                                </div>

                                <!-- Seller-specific fields (conditionally shown, ALL OPTIONAL) -->
                                <div id="seller-fields" style="display: <?= (isset($_SESSION['registration_data']['user_type']) && $_SESSION['registration_data']['user_type'] === 'seller') ? 'block' : 'none'; ?>;">
                                    <div class="form-group">
                                        <label for="business_name" class="form-label">Business Name (Optional)</label>
                                        <input type="text" 
                                               name="business_name" 
                                               id="business_name" 
                                               class="form-input" 
                                               value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>" 
                                               placeholder="Your Business Name (if applicable)">
                                        <small class="form-hint">Optional for individual sellers</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="business_registration" class="form-label">Business Registration Number (Optional)</label>
                                        <input type="text" 
                                               name="business_registration" 
                                               id="business_registration" 
                                               class="form-input" 
                                               value="<?= htmlspecialchars($_POST['business_registration'] ?? '') ?>" 
                                               placeholder="e.g., 2010/123456/07">
                                        <small class="form-hint">Optional - for registered businesses only</small>
                                    </div>
                                </div>

                                <div class="two-column" style="margin-top: 20px;">
                                    <button type="button" class="btn-secondary prev-step" data-prev="1">
                                        <i class="fas fa-arrow-left"></i> Back to Step 1
                                    </button>
                                    <button type="submit" class="btn-submit" id="completeRegistration">
                                        <span id="registerText">Complete Registration</span>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const flipCard = document.querySelector('.flip-card');
    const flipButtons = document.querySelectorAll('[data-flip-to]');
    let currentStep = <?= $registrationStep ?>;
    
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
    
    // Step navigation
    const nextStepBtn = document.getElementById('nextStep1');
    if (nextStepBtn) {
        nextStepBtn.addEventListener('click', function() {
            if (validateStep1()) {
                // Submit step 1 form
                const step1Form = document.getElementById('registerFormStep1');
                if (step1Form) {
                    step1Form.submit();
                }
            }
        });
    }
    
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', function() {
            const prevStep = this.getAttribute('data-prev');
            goToStep(parseInt(prevStep));
        });
    });
    
    function goToStep(step) {
        currentStep = step;
        
        // Hide all steps
        document.querySelectorAll('.step-content').forEach(content => {
            content.style.display = 'none';
        });
        
        // Show current step
        document.getElementById(`step${step}`).style.display = 'block';
        
        // Update progress steps
        document.querySelectorAll('.progress-step').forEach(stepEl => {
            const stepNumber = parseInt(stepEl.getAttribute('data-step'));
            if (stepNumber <= step) {
                stepEl.classList.add('active');
            } else {
                stepEl.classList.remove('active');
            }
        });
        
        // Focus first input
        setTimeout(() => {
            const firstInput = document.querySelector(`#step${step} input:not([type="hidden"]), #step${step} select, #step${step} textarea`);
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    }
    
    function validateStep1() {
        let isValid = true;
        const errors = [];
        
        // Required fields
        const name = document.getElementById('name');
        const surname = document.getElementById('surname');
        const email = document.getElementById('register_email');
        const password = document.getElementById('register_password');
        const confirmPassword = document.getElementById('confirm_password');
        const phone = document.getElementById('phone');
        const terms = document.getElementById('terms');
        
        // Reset error states
        [name, surname, email, password, confirmPassword, phone].forEach(field => {
            field.style.borderColor = '#ddd';
        });
        
        // Validate name
        if (!name.value.trim() || name.value.trim().length < 2) {
            name.style.borderColor = '#e74c3c';
            errors.push('First name must be at least 2 characters');
            isValid = false;
        }
        
        // Validate surname
        if (!surname.value.trim() || surname.value.trim().length < 2) {
            surname.style.borderColor = '#e74c3c';
            errors.push('Last name must be at least 2 characters');
            isValid = false;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailRegex.test(email.value)) {
            email.style.borderColor = '#e74c3c';
            errors.push('Valid email address is required');
            isValid = false;
        }
        
        // Validate password
        if (!password.value || password.value.length < 8) {
            password.style.borderColor = '#e74c3c';
            errors.push('Password must be at least 8 characters');
            isValid = false;
        }
        
        // Validate password match
        if (password.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = '#e74c3c';
            errors.push('Passwords do not match');
            isValid = false;
        }
        
        // Validate phone
        if (!phone.value.trim()) {
            phone.style.borderColor = '#e74c3c';
            errors.push('Phone number is required');
            isValid = false;
        }
        
        // Validate terms
        if (!terms.checked) {
            errors.push('You must agree to the Terms of Service and Privacy Policy');
            isValid = false;
        }
        
        // Show errors
        if (errors.length > 0) {
            showValidationErrors(errors);
        }
        
        return isValid;
    }
    
    function showValidationErrors(errors) {
        // Remove existing error messages
        const existingErrors = document.querySelectorAll('.validation-error');
        existingErrors.forEach(error => error.remove());
        
        // Add new error messages
        const stepContent = document.querySelector(`#step${currentStep}`);
        if (stepContent) {
            errors.forEach(error => {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error validation-error';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${error}</span>`;
                stepContent.insertBefore(errorDiv, stepContent.firstChild);
            });
            
            // Scroll to first error
            const firstError = stepContent.querySelector('.validation-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }
    
    // Password strength checker
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
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    // Show/hide seller fields based on account type
    document.querySelectorAll('input[name="user_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const sellerFields = document.getElementById('seller-fields');
            const businessNameInput = document.getElementById('business_name');
            
            if (this.value === 'seller') {
                sellerFields.style.display = 'block';
                // BUSINESS NAME IS NOW OPTIONAL - NO required attribute
                if (businessNameInput) {
                    businessNameInput.required = false; // Changed from true to false
                }
            } else {
                sellerFields.style.display = 'none';
                if (businessNameInput) {
                    businessNameInput.required = false;
                }
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
    const registerFormStep2 = document.getElementById('registerFormStep2');
    const completeRegistrationBtn = document.getElementById('completeRegistration');
    
    if (registerFormStep2 && completeRegistrationBtn) {
        registerFormStep2.addEventListener('submit', function(e) {
            // Validate all required fields (business fields are now optional)
            const requiredFields = registerFormStep2.querySelectorAll('[required]');
            let hasError = false;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#e74c3c';
                    hasError = true;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            // Validate postal code format
            const postalCode = document.getElementById('postal_code');
            if (postalCode && postalCode.value && !/^\d{4}$/.test(postalCode.value)) {
                postalCode.style.borderColor = '#e74c3c';
                hasError = true;
                alert('Please enter a valid 4-digit postal code.');
            }
            
            if (hasError) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
                return false;
            }
            
            // Show loading state
            const spinner = completeRegistrationBtn.querySelector('.spinner');
            const text = completeRegistrationBtn.querySelector('#registerText');
            const icon = completeRegistrationBtn.querySelector('.fa-check');
            
            if (spinner && text && icon) {
                text.style.display = 'none';
                icon.style.display = 'none';
                spinner.style.display = 'block';
                completeRegistrationBtn.disabled = true;
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
            if (nameInput && currentStep === 1) {
                nameInput.focus();
            } else if (currentStep === 2) {
                const streetAddress = document.getElementById('street_address');
                if (streetAddress) streetAddress.focus();
            }
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
    
    // Postal code formatting
    const postalCodeInput = document.getElementById('postal_code');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
    }
});
</script>

<style>
.progress-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
    gap: 20px;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.progress-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e0e0e0;
    z-index: -1;
}

.progress-step.active::after {
    background: #4361ee;
}

.step-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.progress-step.active .step-circle {
    background: #4361ee;
    color: white;
    box-shadow: 0 2px 8px rgba(67, 97, 238, 0.3);
}

.step-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.progress-step.active .step-label {
    color: #4361ee;
    font-weight: 600;
}

.step-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-hint {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #666;
    font-style: italic;
}

.btn-secondary {
    padding: 12px 20px;
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary:hover {
    background: #e9ecef;
    border-color: #ced4da;
}

.password-strength {
    margin-top: 5px;
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
}

.password-strength-meter {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.validation-error {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>