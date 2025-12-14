<?php
// includes/auth/register.php - COMPLETE ENHANCED VERSION
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
log_info("Registration page accessed", [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Redirect logged-in users
if (isLoggedIn()) {
    log_info("User already logged in, redirecting from registration", [
        'user_id' => $_SESSION['user_id'] ?? 'unknown',
        'user_type' => $_SESSION['user_type'] ?? 'unknown'
    ]);
    header("Location: " . redirectBasedOnRole());
    exit;
}

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
            <h3 class="auth-title mb-3">Registration Temporarily Unavailable</h3>
            <p class="text-muted mb-4">
                We're currently performing scheduled maintenance. Registration will be available shortly.
            </p>
            <a href="<?= BASE_URL ?>/includes/auth/login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Go to Login
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Check if registration is open
if (defined('REGISTRATION_OPEN') && REGISTRATION_OPEN === false) {
    $title = "Registration Closed - PeerCart";
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="auth-container auth-page">
        <div class="glass-card text-center">
            <div class="auth-logo mb-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="PeerCart Logo" onerror="this.style.display='none'">
                <h2>PeerCart</h2>
            </div>
            <div class="mb-4">
                <i class="fas fa-user-slash fa-4x text-danger"></i>
            </div>
            <h3 class="auth-title mb-3">Registration Closed</h3>
            <p class="text-muted mb-4">
                New user registrations are currently closed. Please check back later or contact support for more information.
            </p>
            <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-primary">
                <i class="fas fa-envelope me-2"></i>Contact Support
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Initialize variables
$errors = [];
$success_message = '';
$step = isset($_SESSION['register_step1']) ? 2 : 1;
$step1Data = $_SESSION['register_step1'] ?? [];
$step2Data = [];

// Generate CSRF token
log_debug("Generating CSRF token for registration form");
$csrfToken = generateCSRFToken();
log_debug("CSRF token generated", ['token_prefix' => substr($csrfToken, 0, 10) . '...']);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Log form submission (without sensitive data)
        $log_data = $_POST;
        unset($log_data['password'], $log_data['confirm_password']);
        log_form_submission("register_step_" . ($_POST['step'] ?? 'unknown'), $log_data);
        
        // Validate CSRF token
        log_debug("Validating CSRF token for registration step " . ($_POST['step'] ?? 'unknown'));
        
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            log_security("CSRF validation failed in registration", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            throw new Exception("Invalid form submission. Please refresh the page and try again.");
        }
        
        log_info("CSRF validation passed for registration step " . ($_POST['step'] ?? 'unknown'));

        // ===== Step 1: Basic Information =====
        if (($_POST['step'] ?? '') == 1) {
            log_info("Processing registration step 1");
            
            $step1Data = [
                'name' => sanitizeInput($_POST['name'] ?? ''),
                'surname' => sanitizeInput($_POST['surname'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'user_type' => $_POST['user_type'] ?? 'buyer',
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
                'terms_accepted' => isset($_POST['terms']) && $_POST['terms'] == '1'
            ];

            // Validate inputs
            $step1Errors = validateRegistrationInput($step1Data);
            
            // Check if email already exists
            if (emailExists($step1Data['email'])) {
                $step1Errors[] = "This email address is already registered. Please use a different email or try logging in.";
            }
            
            // Check if terms are accepted
            if (!$step1Data['terms_accepted']) {
                $step1Errors[] = "You must accept the Terms of Service and Privacy Policy to register.";
            }
            
            // Check password strength
            if (!empty($step1Data['password'])) {
                $passwordStrength = checkPasswordStrength($step1Data['password']);
                if ($passwordStrength['score'] < 3) {
                    $step1Errors[] = "Password is too weak. " . $passwordStrength['feedback'];
                }
            }
            
            // Validate user type
            $allowedUserTypes = ['buyer', 'seller', 'both'];
            if (!in_array($step1Data['user_type'], $allowedUserTypes)) {
                $step1Errors[] = "Invalid user type selected.";
            }
            
            if (empty($step1Errors)) {
                // Store step 1 data in session
                $_SESSION['register_step1'] = $step1Data;
                
                // Set step to 2
                $step = 2;
                
                log_info("Registration step 1 completed successfully", [
                    'email' => $step1Data['email'],
                    'user_type' => $step1Data['user_type']
                ]);
                
                // Redirect to clear POST data and prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $errors = $step1Errors;
                log_warning("Registration step 1 validation failed", [
                    'errors' => $errors,
                    'email' => $step1Data['email']
                ]);
            }
        }

        // ===== Step 2: Additional Information =====
        if (($_POST['step'] ?? '') == 2) {
            log_info("Processing registration step 2");
            
            if (!isset($_SESSION['register_step1'])) {
                throw new Exception("Session expired. Please start registration again.");
            }
            
            $step1Data = $_SESSION['register_step1'];
            $step2Data = [
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'street_address' => sanitizeInput($_POST['street_address'] ?? ''),
                'city' => sanitizeInput($_POST['city'] ?? ''),
                'province' => sanitizeInput($_POST['province'] ?? ''),
                'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
                'country' => sanitizeInput($_POST['country'] ?? 'ZA'), // Default to South Africa
                'profile_image' => $_FILES['profile_image'] ?? null,
                'bio' => sanitizeInput($_POST['bio'] ?? '')
            ];

            $step2Errors = [];
            
            // Validate required fields
            $requiredFields = ['phone', 'street_address', 'city', 'province', 'postal_code'];
            foreach ($requiredFields as $field) {
                if (empty($step2Data[$field])) {
                    $step2Errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
                }
            }
            
            // Validate phone number format
            if (!empty($step2Data['phone']) && !preg_match('/^[0-9\s\-\+\(\)]{10,20}$/', $step2Data['phone'])) {
                $step2Errors[] = "Please enter a valid phone number.";
            }
            
            // Handle profile image upload
            $profilePhotoPath = null;
            if (!empty($step2Data['profile_image']['name']) && $step2Data['profile_image']['error'] === UPLOAD_ERR_OK) {
                // Validate file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $step2Data['profile_image']['tmp_name']);
                finfo_close($finfo);
                
                // Check file type
                if (!in_array($mimeType, $allowedTypes)) {
                    $step2Errors[] = "Profile photo must be JPG, PNG, GIF, or WebP.";
                }
                
                // Check file size
                if ($step2Data['profile_image']['size'] > $maxFileSize) {
                    $step2Errors[] = "Profile photo must be less than 5MB.";
                }
                
                // Check image dimensions
                $imageInfo = getimagesize($step2Data['profile_image']['tmp_name']);
                if ($imageInfo && ($imageInfo[0] > 5000 || $imageInfo[1] > 5000)) {
                    $step2Errors[] = "Profile photo dimensions are too large. Maximum size is 5000x5000 pixels.";
                }
                
                if (empty($step2Errors)) {
                    $uploadDir = __DIR__ . '/../../uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = strtolower(pathinfo($step2Data['profile_image']['name'], PATHINFO_EXTENSION));
                    $filename = 'profile_' . uniqid() . '_' . time() . '.' . $extension;
                    $target = $uploadDir . $filename;
                    
                    if (move_uploaded_file($step2Data['profile_image']['tmp_name'], $target)) {
                        $profilePhotoPath = 'uploads/profiles/' . $filename;
                        
                        // Create thumbnail if needed
                        createProfileThumbnail($target, $uploadDir . 'thumb_' . $filename, 150, 150);
                    } else {
                        $step2Errors[] = "Failed to upload profile photo. Please try again.";
                    }
                }
            }
            
            if (empty($step2Errors)) {
                // Generate email verification token if required
                $emailVerificationToken = null;
                $emailVerified = 0;
                
                if (EMAIL_VERIFICATION_REQUIRED) {
                    $emailVerificationToken = bin2hex(random_bytes(32));
                    $emailVerified = 0;
                } else {
                    $emailVerified = 1;
                }
                
                // Hash password
                $hashedPassword = password_hash($step1Data['password'], PASSWORD_DEFAULT);
                
                // Generate referral code if needed
                $referralCode = generateReferralCode($step1Data['name'], $step1Data['surname']);
                
                // Prepare user data
                $userData = [
                    'name' => $step1Data['name'],
                    'surname' => $step1Data['surname'],
                    'email' => $step1Data['email'],
                    'password' => $hashedPassword,
                    'user_type' => $step1Data['user_type'],
                    'phone' => $step2Data['phone'],
                    'street_address' => $step2Data['street_address'],
                    'city' => $step2Data['city'],
                    'province' => $step2Data['province'],
                    'postal_code' => $step2Data['postal_code'],
                    'country' => $step2Data['country'],
                    'profile_image' => $profilePhotoPath,
                    'bio' => $step2Data['bio'],
                    'referral_code' => $referralCode,
                    'email_verification_token' => $emailVerificationToken,
                    'email_verified' => $emailVerified,
                    'status' => 'active', // or 'pending' if admin approval required
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s')
                ];
                
                // Insert into database
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("INSERT INTO users 
                    (name, surname, email, password, user_type, phone, street_address, city, province, 
                     postal_code, country, profile_image, bio, referral_code, email_verification_token, 
                     email_verified, status, created_at, last_login)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $userData['name'],
                    $userData['surname'],
                    $userData['email'],
                    $userData['password'],
                    $userData['user_type'],
                    $userData['phone'],
                    $userData['street_address'],
                    $userData['city'],
                    $userData['province'],
                    $userData['postal_code'],
                    $userData['country'],
                    $userData['profile_image'],
                    $userData['bio'],
                    $userData['referral_code'],
                    $userData['email_verification_token'],
                    $userData['email_verified'],
                    $userData['status'],
                    $userData['created_at'],
                    $userData['last_login']
                ]);
                
                $userId = $db->lastInsertId();
                
                // Log successful registration
                log_info("User registration successful", [
                    'user_id' => $userId,
                    'email' => $userData['email'],
                    'user_type' => $userData['user_type'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                // Send welcome email
                if (SEND_WELCOME_EMAIL) {
                    sendWelcomeEmail($userData['email'], $userData['name'], $userData['surname']);
                }
                
                // Send email verification if required
                if (EMAIL_VERIFICATION_REQUIRED && $emailVerificationToken) {
                    sendVerificationEmail($userData['email'], $userData['name'], $emailVerificationToken);
                    $_SESSION['require_verification'] = true;
                }
                
                // Create referral record if referral code was provided
                if (!empty($_POST['referral_code'])) {
                    createReferralRecord($userId, $_POST['referral_code']);
                }
                
                // Clear session data
                unset($_SESSION['register_step1']);
                
                // Create user session (if email verification is not required or auto-login is enabled)
                if (!$emailVerificationToken || AUTO_LOGIN_AFTER_REGISTRATION) {
                    createUserSession([
                        'id' => $userId,
                        'name' => $userData['name'],
                        'surname' => $userData['surname'],
                        'email' => $userData['email'],
                        'user_type' => $userData['user_type'],
                        'profile_image' => $userData['profile_image']
                    ]);
                    
                    setFlashMessage("Welcome to PeerCart, {$userData['name']}! 🎉", "success");
                    
                    // Log user activity
                    log_user_action("registration_complete", $userId, [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    // Redirect to appropriate page
                    $redirectUrl = redirectBasedOnRole();
                    if ($emailVerificationToken) {
                        $redirectUrl = BASE_URL . '/includes/auth/verify-email.php?pending=true';
                    }
                    
                    header("Location: " . $redirectUrl);
                    exit;
                } else {
                    // Redirect to verification page
                    $_SESSION['registration_success'] = true;
                    header("Location: " . BASE_URL . "/includes/auth/verify-email.php?email=" . urlencode($userData['email']));
                    exit;
                }
            } else {
                $errors = $step2Errors;
                log_warning("Registration step 2 validation failed", [
                    'errors' => $errors,
                    'email' => $step1Data['email']
                ]);
            }
        }

    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    log_error("Registration error: " . $e->getMessage(), [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'step' => $step
    ]);
}

$title = "Join PeerCart - Register";
include __DIR__ . '/../../includes/header.php';
?>
<div class="auth-container auth-page">
    <div class="glass-card">
        <!-- Logo Section -->
        <div class="auth-logo mb-4">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="PeerCart Logo" 
                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/images/logo-placeholder.png'">
            <h2>PeerCart</h2>
            <p class="text-muted mt-1">Join our community today</p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps mb-5">
            <div class="progress-step <?= $step >= 1 ? 'active' : '' ?>">
                <div class="step-circle"></div>
                <span class="step-label">Basic Info</span>
            </div>
            <div class="progress-step <?= $step == 2 ? 'active' : '' ?>">
                <div class="step-circle"></div>
                <span class="step-label">Details</span>
            </div>
        </div>

        <!-- Title -->
        <h2 class="auth-title mb-4">
            <?= $step == 1 ? 'Create Your Account' : 'Complete Your Profile' ?>
        </h2>
        <p class="text-center text-muted mb-4">
            <?= $step == 1 ? 'Step 1 of 2: Basic information' : 'Step 2 of 2: Additional details' ?>
        </p>

        <!-- Display Errors -->
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php displayFlashMessage(); ?>

        <!-- Step 1 Form -->
        <?php if ($step == 1): ?>
        <form method="post" class="auth-form" novalidate id="step1Form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="step" value="1">
            
            <!-- Personal Information -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-user me-1"></i>First Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               class="form-control" 
                               value="<?= htmlspecialchars($step1Data['name'] ?? '') ?>" 
                               required
                               placeholder="John"
                               autocomplete="given-name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="surname" class="form-label">
                            <i class="fas fa-user me-1"></i>Last Name *
                        </label>
                        <input type="text" 
                               name="surname" 
                               id="surname" 
                               class="form-control" 
                               value="<?= htmlspecialchars($step1Data['surname'] ?? '') ?>" 
                               required
                               placeholder="Doe"
                               autocomplete="family-name">
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-1"></i>Email Address *
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       class="form-control" 
                       value="<?= htmlspecialchars($step1Data['email'] ?? '') ?>" 
                       required
                       placeholder="you@example.com"
                       autocomplete="email">
                <small class="form-text text-muted">
                    We'll never share your email with anyone else.
                </small>
            </div>

            <!-- Password -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Password *
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   required
                                   placeholder="••••••••"
                                   minlength="8"
                                   autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            Minimum 8 characters with letters and numbers
                        </small>
                        <div class="password-strength-container mt-2">
                            <div class="password-strength-label">
                                <span>Password strength:</span>
                                <span id="passwordStrengthText">None</span>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Confirm Password *
                        </label>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password" 
                               class="form-control" 
                               required
                               placeholder="••••••••"
                               autocomplete="new-password">
                        <div id="passwordMatch" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <!-- Account Type -->
            <div class="form-group mb-4">
                <label for="user_type" class="form-label">
                    <i class="fas fa-user-tag me-1"></i>Account Type *
                </label>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="form-check card account-type-card">
                            <input class="form-check-input" type="radio" name="user_type" id="buyer" 
                                   value="buyer" <?= (($step1Data['user_type'] ?? 'buyer')=='buyer')?'checked':'' ?>>
                            <label class="form-check-label" for="buyer">
                                <div class="text-center p-3">
                                    <i class="fas fa-shopping-cart fa-2x mb-2 text-primary"></i>
                                    <h6>Buyer</h6>
                                    <small class="text-muted">Browse and purchase items</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check card account-type-card">
                            <input class="form-check-input" type="radio" name="user_type" id="seller" 
                                   value="seller" <?= (($step1Data['user_type'] ?? 'buyer')=='seller')?'checked':'' ?>>
                            <label class="form-check-label" for="seller">
                                <div class="text-center p-3">
                                    <i class="fas fa-store fa-2x mb-2 text-success"></i>
                                    <h6>Seller</h6>
                                    <small class="text-muted">Sell products and services</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check card account-type-card">
                            <input class="form-check-input" type="radio" name="user_type" id="both" 
                                   value="both" <?= (($step1Data['user_type'] ?? 'buyer')=='both')?'checked':'' ?>>
                            <label class="form-check-label" for="both">
                                <div class="text-center p-3">
                                    <i class="fas fa-exchange-alt fa-2x mb-2 text-warning"></i>
                                    <h6>Both</h6>
                                    <small class="text-muted">Buy and sell on the platform</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referral Code (Optional) -->
            <div class="form-group mb-3">
                <label for="referral_code" class="form-label">
                    <i class="fas fa-user-friends me-1"></i>Referral Code (Optional)
                </label>
                <input type="text" 
                       name="referral_code" 
                       id="referral_code" 
                       class="form-control" 
                       value="<?= htmlspecialchars($_POST['referral_code'] ?? '') ?>" 
                       placeholder="Enter referral code if you have one"
                       maxlength="20">
            </div>

            <!-- Terms and Conditions -->
            <div class="form-group mb-4">
                <div class="form-check">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="terms" 
                           name="terms" 
                           value="1"
                           required>
                    <label class="form-check-label" for="terms">
                        I agree to the 
                        <a href="<?= BASE_URL ?>/pages/terms.php" target="_blank" class="auth-link">Terms of Service</a>
                        and 
                        <a href="<?= BASE_URL ?>/pages/privacy.php" target="_blank" class="auth-link">Privacy Policy</a> *
                    </label>
                </div>
                <div class="form-check mt-2">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="newsletter" 
                           name="newsletter" 
                           value="1"
                           checked>
                    <label class="form-check-label" for="newsletter">
                        Subscribe to our newsletter for updates and promotions
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="step1Btn">
                <span id="step1Text">
                    Continue <i class="fas fa-arrow-right ms-2"></i>
                </span>
                <span id="step1Spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>

            <!-- Login Link -->
            <div class="text-center mt-3">
                <small class="text-muted">
                    Already have an account? 
                    <a href="<?= BASE_URL ?>/includes/auth/login.php" class="auth-link">
                        Sign in here
                    </a>
                </small>
            </div>
        </form>

        <!-- Step 2 Form -->
        <?php else: ?>
        <form method="post" class="auth-form" enctype="multipart/form-data" id="step2Form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="step" value="2">
            
            <!-- Contact Information -->
            <div class="form-group mb-3">
                <label for="phone" class="form-label">
                    <i class="fas fa-phone me-1"></i>Phone Number *
                </label>
                <input type="tel" 
                       name="phone" 
                       id="phone" 
                       class="form-control" 
                       value="<?= htmlspecialchars($step2Data['phone'] ?? '') ?>" 
                       required
                       placeholder="+27 12 345 6789"
                       autocomplete="tel">
                <small class="form-text text-muted">
                    We'll use this for order updates and important notifications
                </small>
            </div>

            <!-- Address -->
            <div class="form-group mb-3">
                <label for="street_address" class="form-label">
                    <i class="fas fa-home me-1"></i>Street Address *
                </label>
                <input type="text" 
                       name="street_address" 
                       id="street_address" 
                       class="form-control" 
                       value="<?= htmlspecialchars($step2Data['street_address'] ?? '') ?>" 
                       required
                       placeholder="123 Main Street"
                       autocomplete="street-address">
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="city" class="form-label">
                            <i class="fas fa-city me-1"></i>City *
                        </label>
                        <input type="text" 
                               name="city" 
                               id="city" 
                               class="form-control" 
                               value="<?= htmlspecialchars($step2Data['city'] ?? '') ?>" 
                               required
                               placeholder="Johannesburg"
                               autocomplete="address-level2">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="province" class="form-label">
                            <i class="fas fa-map me-1"></i>Province *
                        </label>
                        <select name="province" id="province" class="form-control" required>
                            <option value="">Select Province</option>
                            <option value="gauteng" <?= ($step2Data['province'] ?? '')=='gauteng'?'selected':'' ?>>Gauteng</option>
                            <option value="western_cape" <?= ($step2Data['province'] ?? '')=='western_cape'?'selected':'' ?>>Western Cape</option>
                            <option value="eastern_cape" <?= ($step2Data['province'] ?? '')=='eastern_cape'?'selected':'' ?>>Eastern Cape</option>
                            <option value="kzn" <?= ($step2Data['province'] ?? '')=='kzn'?'selected':'' ?>>KwaZulu-Natal</option>
                            <option value="free_state" <?= ($step2Data['province'] ?? '')=='free_state'?'selected':'' ?>>Free State</option>
                            <option value="north_west" <?= ($step2Data['province'] ?? '')=='north_west'?'selected':'' ?>>North West</option>
                            <option value="limpopo" <?= ($step2Data['province'] ?? '')=='limpopo'?'selected':'' ?>>Limpopo</option>
                            <option value="mpumalanga" <?= ($step2Data['province'] ?? '')=='mpumalanga'?'selected':'' ?>>Mpumalanga</option>
                            <option value="northern_cape" <?= ($step2Data['province'] ?? '')=='northern_cape'?'selected':'' ?>>Northern Cape</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="postal_code" class="form-label">
                            <i class="fas fa-mail-bulk me-1"></i>Postal Code *
                        </label>
                        <input type="text" 
                               name="postal_code" 
                               id="postal_code" 
                               class="form-control" 
                               value="<?= htmlspecialchars($step2Data['postal_code'] ?? '') ?>" 
                               required
                               placeholder="2000"
                               autocomplete="postal-code">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="country" class="form-label">
                            <i class="fas fa-globe me-1"></i>Country *
                        </label>
                        <select name="country" id="country" class="form-control" required>
                            <option value="ZA" <?= ($step2Data['country'] ?? 'ZA')=='ZA'?'selected':'' ?>>South Africa</option>
                            <option value="US">United States</option>
                            <option value="GB">United Kingdom</option>
                            <option value="CA">Canada</option>
                            <option value="AU">Australia</option>
                            <option value="DE">Germany</option>
                            <option value="FR">France</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Profile Image -->
            <div class="form-group mb-3">
                <label for="profile_image" class="form-label">
                    <i class="fas fa-camera me-1"></i>Profile Photo (Optional)
                </label>
                <div class="file-upload-area">
                    <input type="file" 
                           name="profile_image" 
                           id="profile_image" 
                           class="form-control" 
                           accept="image/png,image/jpeg,image/gif,image/webp"
                           onchange="previewImage(this)">
                    <small class="form-text text-muted">
                        JPG, PNG, GIF or WebP. Max 5MB. Recommended size: 500x500px
                    </small>
                    <div id="imagePreview" class="mt-2 text-center"></div>
                </div>
            </div>

            <!-- Bio -->
            <div class="form-group mb-4">
                <label for="bio" class="form-label">
                    <i class="fas fa-edit me-1"></i>Bio (Optional)
                </label>
                <textarea name="bio" 
                          id="bio" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Tell us a little about yourself..."
                          maxlength="500"><?= htmlspecialchars($step2Data['bio'] ?? '') ?></textarea>
                <small class="form-text text-muted">
                    Maximum 500 characters. <span id="bioCounter">0/500</span>
                </small>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex gap-3">
                <a href="?reset=1" class="btn btn-outline-secondary flex-grow-1">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <button type="submit" class="btn btn-success btn-lg flex-grow-1" id="step2Btn">
                    <span id="step2Text">
                        Complete Registration <i class="fas fa-check ms-2"></i>
                    </span>
                    <span id="step2Spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>

            <!-- Review Info -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-user-check me-2"></i>Account Summary
                    </h6>
                    <div class="row small">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($step1Data['name'] ?? '') ?> <?= htmlspecialchars($step1Data['surname'] ?? '') ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($step1Data['email'] ?? '') ?></p>
                            <p><strong>Account Type:</strong> <?= ucfirst($step1Data['user_type'] ?? 'buyer') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Step 1 Completed:</strong> <i class="fas fa-check text-success"></i></p>
                            <p><strong>Step 2:</strong> In progress</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Login Link (for step 2) -->
        <?php if ($step == 2): ?>
        <div class="auth-footer mt-4 text-center">
            <small class="text-muted">
                Already have an account? 
                <a href="<?= BASE_URL ?>/includes/auth/login.php" class="auth-link">
                    Sign in here
                </a>
            </small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Password toggle functionality
const togglePassword = document.getElementById('togglePassword');
if (togglePassword) {
    togglePassword.addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
}

// Password strength checker
const passwordInput = document.getElementById('password');
if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthMeter = document.getElementById('passwordStrengthMeter');
        const strengthText = document.getElementById('passwordStrengthText');
        
        if (!password) {
            strengthMeter.className = 'password-strength-meter';
            strengthMeter.style.width = '0%';
            strengthText.textContent = 'None';
            return;
        }
        
        let score = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        
        // Complexity checks
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        // Prevent over-scoring
        score = Math.min(score, 4);
        
        // Update strength meter
        strengthMeter.className = 'password-strength-meter strength-' + score;
        
        // Update text
        const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        strengthText.textContent = strengthLabels[score];
        strengthText.className = '';
        if (score <= 1) strengthText.className = 'text-danger';
        else if (score <= 2) strengthText.className = 'text-warning';
        else if (score <= 3) strengthText.className = 'text-info';
        else strengthText.className = 'text-success';
        
        // Check password match
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword && confirmPassword.value) {
            checkPasswordMatch();
        }
    });
}

// Password match checker
const confirmPasswordInput = document.getElementById('confirm_password');
if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
}

function checkPasswordMatch() {
    const password = document.getElementById('password')?.value;
    const confirmPassword = document.getElementById('confirm_password')?.value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (!matchDiv) return;
    
    if (!password || !confirmPassword) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (password === confirmPassword) {
        matchDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Passwords match';
        matchDiv.className = 'text-success small';
    } else {
        matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Passwords do not match';
        matchDiv.className = 'text-danger small';
    }
}

// Image preview
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (!preview) return;
    
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-thumbnail';
            img.style.maxWidth = '150px';
            img.style.maxHeight = '150px';
            preview.appendChild(img);
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Bio character counter
const bioInput = document.getElementById('bio');
if (bioInput) {
    const bioCounter = document.getElementById('bioCounter');
    
    bioInput.addEventListener('input', function() {
        const length = this.value.length;
        if (bioCounter) {
            bioCounter.textContent = length + '/500';
            if (length > 450) {
                bioCounter.className = 'text-warning';
            } else if (length >= 500) {
                bioCounter.className = 'text-danger';
            } else {
                bioCounter.className = 'text-muted';
            }
        }
        
        // Limit to 500 characters
        if (length > 500) {
            this.value = this.value.substring(0, 500);
        }
    });
    
    // Initialize counter
    bioInput.dispatchEvent(new Event('input'));
}

// Form validation and submission
const step1Form = document.getElementById('step1Form');
const step2Form = document.getElementById('step2Form');

if (step1Form) {
    step1Form.addEventListener('submit', function(e) {
        // Validate required fields
        const requiredFields = ['name', 'surname', 'email', 'password', 'confirm_password'];
        let isValid = true;
        
        for (const field of requiredFields) {
            const input = document.getElementById(field);
            if (input && !input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else if (input) {
                input.classList.remove('is-invalid');
            }
        }
        
        // Validate email format
        const emailInput = document.getElementById('email');
        if (emailInput && emailInput.value && !validateEmail(emailInput.value)) {
            emailInput.classList.add('is-invalid');
            isValid = false;
        }
        
        // Check terms acceptance
        const termsCheckbox = document.getElementById('terms');
        if (termsCheckbox && !termsCheckbox.checked) {
            termsCheckbox.classList.add('is-invalid');
            isValid = false;
        }
        
        // Check password match
        const password = document.getElementById('password')?.value;
        const confirmPassword = document.getElementById('confirm_password')?.value;
        if (password && confirmPassword && password !== confirmPassword) {
            document.getElementById('confirm_password').classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields correctly.', 'danger');
            return;
        }
        
        // Show loading state
        const step1Btn = document.getElementById('step1Btn');
        const step1Text = document.getElementById('step1Text');
        const step1Spinner = document.getElementById('step1Spinner');
        
        if (step1Btn && step1Text && step1Spinner) {
            step1Btn.disabled = true;
            step1Text.classList.add('d-none');
            step1Spinner.classList.remove('d-none');
        }
    });
}

if (step2Form) {
    step2Form.addEventListener('submit', function(e) {
        // Validate required fields
        const requiredFields = ['phone', 'street_address', 'city', 'province', 'postal_code', 'country'];
        let isValid = true;
        
        for (const field of requiredFields) {
            const input = document.getElementById(field);
            if (input && !input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else if (input) {
                input.classList.remove('is-invalid');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields.', 'danger');
            return;
        }
        
        // Show loading state
        const step2Btn = document.getElementById('step2Btn');
        const step2Text = document.getElementById('step2Text');
        const step2Spinner = document.getElementById('step2Spinner');
        
        if (step2Btn && step2Text && step2Spinner) {
            step2Btn.disabled = true;
            step2Text.classList.add('d-none');
            step2Spinner.classList.remove('d-none');
        }
    });
}

// Email validation function
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Show notification
function showNotification(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const glassCard = document.querySelector('.glass-card');
    if (glassCard) {
        glassCard.insertBefore(alert, glassCard.firstChild);
        
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }
}

// Auto-focus first field
document.addEventListener('DOMContentLoaded', function() {
    // Focus first input field
    const firstInput = document.querySelector('input:not([type="hidden"]), select, textarea');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 300);
    }
    
    // Handle account type card selection
    const accountTypeCards = document.querySelectorAll('.account-type-card');
    accountTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Update card styles
                accountTypeCards.forEach(c => {
                    c.classList.remove('border-primary', 'shadow-sm');
                });
                this.classList.add('border-primary', 'shadow-sm');
            }
        });
        
        // Initialize selected card style
        const radio = card.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            card.classList.add('border-primary', 'shadow-sm');
        }
    });
    
    // Initialize password strength if on step 1
    if (passwordInput && passwordInput.value) {
        passwordInput.dispatchEvent(new Event('input'));
    }
});
</script>

<?php 
// Final log before page ends
log_debug("Registration page rendering complete, step: $step");

include __DIR__ . '/../../includes/footer.php'; 
?>