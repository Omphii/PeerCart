<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/bootstrap.php';

// Check if user is logged in (session already started by bootstrap.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit();
}

$title = 'PeerCart - Settings';
$currentPage = 'settings';

// Set additional styles BEFORE including header
$additionalStyles = ['settings'];

// Check if includePartial exists, otherwise include header directly
if (function_exists('includePartial')) {
    includePartial('header', compact('title', 'currentPage', 'additionalStyles'));
} else {
    // Fallback: include header directly with all required variables
    $pageHead = '';
    require_once __DIR__ . '/../includes/header.php';
}

// Get current user data
$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

try {
    $user = $db->getRow("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    if (!$user) {
        // User not found, log them out
        session_destroy();
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit();
    }
    
    // Check if user_preferences table exists, if not create it
    $tableExists = $db->getRow("SHOW TABLES LIKE 'user_preferences'");
    
    if (!$tableExists) {
        // Create user_preferences table
        $db->query("
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                theme VARCHAR(20) DEFAULT 'light',
                notifications_email TINYINT(1) DEFAULT 1,
                notifications_push TINYINT(1) DEFAULT 1,
                email_digest VARCHAR(20) DEFAULT 'weekly',
                language VARCHAR(10) DEFAULT 'en',
                timezone VARCHAR(50) DEFAULT 'Africa/Johannesburg',
                profile_visibility VARCHAR(20) DEFAULT 'public',
                show_email TINYINT(1) DEFAULT 0,
                show_phone TINYINT(1) DEFAULT 0,
                show_location TINYINT(1) DEFAULT 1,
                allow_messages TINYINT(1) DEFAULT 1,
                allow_following TINYINT(1) DEFAULT 1,
                search_index TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    // Get user preferences if they exist
    $preferences = $db->getRow("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
    
} catch(Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Settings error: " . $e->getMessage());
}

// Handle form submissions
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
            case 'update_profile':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $province = trim($_POST['province'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                
                // Validate
                if (empty($name)) {
                    $errors['name'] = 'Name is required';
                }
                if (empty($email)) {
                    $errors['email'] = 'Email is required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format';
                }
                
                // Check if email is already taken by another user
                $existingUser = $db->getRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
                if ($existingUser) {
                    $errors['email'] = 'Email is already taken';
                }
                
                if (empty($errors)) {
                    // Update user profile
                    $db->update(
                        'users',
                        [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'city' => $city,
                            'province' => $province,
                            'bio' => $bio,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        ['id' => $user_id]
                    );
                    
                    // Update session user name
                    $_SESSION['user_name'] = $name;
                    
                    $success = 'Profile updated successfully!';
                    // Refresh user data
                    $user = $db->getRow("SELECT * FROM users WHERE id = ?", [$user_id]);
                }
                break;
                
            case 'update_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Validate
                if (empty($current_password)) {
                    $errors['current_password'] = 'Current password is required';
                } else {
                    // Verify current password
                    if (!password_verify($current_password, $user['password'])) {
                        $errors['current_password'] = 'Current password is incorrect';
                    }
                }
                
                if (empty($new_password)) {
                    $errors['new_password'] = 'New password is required';
                } elseif (strlen($new_password) < 6) {
                    $errors['new_password'] = 'Password must be at least 6 characters';
                }
                
                if ($new_password !== $confirm_password) {
                    $errors['confirm_password'] = 'Passwords do not match';
                }
                
                if (empty($errors)) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $db->update(
                        'users',
                        [
                            'password' => $hashed_password,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        ['id' => $user_id]
                    );
                    
                    $success = 'Password updated successfully!';
                }
                break;
                
            case 'update_preferences':
                $theme = $_POST['theme'] ?? 'light';
                $notifications_email = isset($_POST['notifications_email']) ? 1 : 0;
                $notifications_push = isset($_POST['notifications_push']) ? 1 : 0;
                $email_digest = $_POST['email_digest'] ?? 'never';
                $language = $_POST['language'] ?? 'en';
                $timezone = $_POST['timezone'] ?? 'UTC';
                
                // Check if preferences exist
                $existingPrefs = $db->getRow("SELECT id FROM user_preferences WHERE user_id = ?", [$user_id]);
                if ($existingPrefs) {
                    $db->update(
                        'user_preferences',
                        [
                            'theme' => $theme,
                            'notifications_email' => $notifications_email,
                            'notifications_push' => $notifications_push,
                            'email_digest' => $email_digest,
                            'language' => $language,
                            'timezone' => $timezone,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        ['user_id' => $user_id]
                    );
                } else {
                    $db->insert(
                        'user_preferences',
                        [
                            'user_id' => $user_id,
                            'theme' => $theme,
                            'notifications_email' => $notifications_email,
                            'notifications_push' => $notifications_push,
                            'email_digest' => $email_digest,
                            'language' => $language,
                            'timezone' => $timezone,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                // Update preferences data
                $preferences = $db->getRow("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
                $success = 'Preferences updated successfully!';
                break;
                
            case 'update_privacy':
                $profile_visibility = $_POST['profile_visibility'] ?? 'public';
                $show_email = isset($_POST['show_email']) ? 1 : 0;
                $show_phone = isset($_POST['show_phone']) ? 1 : 0;
                $show_location = isset($_POST['show_location']) ? 1 : 0;
                $allow_messages = isset($_POST['allow_messages']) ? 1 : 0;
                $allow_following = isset($_POST['allow_following']) ? 1 : 0;
                $search_index = isset($_POST['search_index']) ? 1 : 0;
                
                $existingPrefs = $db->getRow("SELECT id FROM user_preferences WHERE user_id = ?", [$user_id]);
                if ($existingPrefs) {
                    $db->update(
                        'user_preferences',
                        [
                            'profile_visibility' => $profile_visibility,
                            'show_email' => $show_email,
                            'show_phone' => $show_phone,
                            'show_location' => $show_location,
                            'allow_messages' => $allow_messages,
                            'allow_following' => $allow_following,
                            'search_index' => $search_index,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        ['user_id' => $user_id]
                    );
                } else {
                    $db->insert(
                        'user_preferences',
                        [
                            'user_id' => $user_id,
                            'profile_visibility' => $profile_visibility,
                            'show_email' => $show_email,
                            'show_phone' => $show_phone,
                            'show_location' => $show_location,
                            'allow_messages' => $allow_messages,
                            'allow_following' => $allow_following,
                            'search_index' => $search_index,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                // Update preferences data
                $preferences = $db->getRow("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
                $success = 'Privacy settings updated successfully!';
                break;
        }
    } catch(Exception $e) {
        $error = "Update failed: " . $e->getMessage();
        error_log("Settings update error: " . $e->getMessage());
    }
}

// Get provinces for dropdown
$provinces = [
    'Eastern Cape',
    'Free State',
    'Gauteng',
    'KwaZulu-Natal',
    'Limpopo',
    'Mpumalanga',
    'North West',
    'Northern Cape',
    'Western Cape'
];

// Get timezones
$timezones = [
    'UTC',
    'Africa/Johannesburg',
    'Africa/Cairo',
    'Europe/London',
    'Europe/Paris',
    'America/New_York',
    'America/Los_Angeles',
    'Asia/Tokyo',
    'Australia/Sydney'
];

// Default preferences if none exist
$defaultPreferences = [
    'theme' => 'light',
    'notifications_email' => 1,
    'notifications_push' => 1,
    'email_digest' => 'weekly',
    'language' => 'en',
    'timezone' => 'Africa/Johannesburg',
    'profile_visibility' => 'public',
    'show_email' => 0,
    'show_phone' => 0,
    'show_location' => 1,
    'allow_messages' => 1,
    'allow_following' => 1,
    'search_index' => 1
];

// Merge with actual preferences (use empty array if preferences is not set)
$preferences = isset($preferences) ? array_merge($defaultPreferences, $preferences ?: []) : $defaultPreferences;
?>

<!-- END OF PHP SECTION -->
<div class="settings-container">
    <?php if(isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    
    
    <div class="settings-grid">
        <!-- Left Sidebar - Navigation -->
        <div class="settings-sidebar">
            <div class="sidebar-header">
                <div class="user-avatar">
                    <?php if(!empty($user['avatar'])): ?>
                        <img src="<?php echo BASE_URL . '/assets/uploads/avatars/' . $user['avatar']; ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="user-joined">Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <nav class="settings-nav">
                <a href="#profile" class="nav-item active" data-target="profile">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="#security" class="nav-item" data-target="security">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>
                <a href="#preferences" class="nav-item" data-target="preferences">
                    <i class="fas fa-sliders-h"></i>
                    <span>Preferences</span>
                </a>
                <a href="#privacy" class="nav-item" data-target="privacy">
                    <i class="fas fa-lock"></i>
                    <span>Privacy</span>
                </a>
                <a href="#notifications" class="nav-item" data-target="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <div class="nav-divider"></div>
                <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Right Content Area -->
        <div class="settings-content">
            <!-- Profile Settings -->
            <div class="settings-section active" id="profile-section">
                <div class="section-header">
                    <h2><i class="fas fa-user"></i> Profile Settings</h2>
                    <p>Update your personal information and profile details</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user-circle"></i> Full Name *
                            </label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" 
                                   required>
                            <?php if(isset($errors['name'])): ?>
                                <span class="error-message"><?php echo $errors['name']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address *
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                            <?php if(isset($errors['email'])): ?>
                                <span class="error-message"><?php echo $errors['email']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   placeholder="+27 12 345 6789">
                        </div>
                        
                        <div class="form-group">
                            <label for="city">
                                <i class="fas fa-map-marker-alt"></i> City
                            </label>
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" 
                                   placeholder="Johannesburg">
                        </div>
                        
                        <div class="form-group">
                            <label for="province">
                                <i class="fas fa-map"></i> Province
                            </label>
                            <select id="province" name="province">
                                <option value="">Select Province</option>
                                <?php foreach($provinces as $prov): ?>
                                    <option value="<?php echo $prov; ?>" 
                                        <?php echo ($user['province'] ?? '') == $prov ? 'selected' : ''; ?>>
                                        <?php echo $prov; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">
                            <i class="fas fa-edit"></i> Bio / Description
                        </label>
                        <textarea id="bio" name="bio" rows="4" 
                                  placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <small class="hint">Share some information about yourself (optional)</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Security Settings -->
            <div class="settings-section" id="security-section">
                <div class="section-header">
                    <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                    <p>Manage your password and security preferences</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-key"></i> Current Password *
                            </label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" required>
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['current_password'])): ?>
                                <span class="error-message"><?php echo $errors['current_password']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-lock"></i> New Password *
                            </label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" required>
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['new_password'])): ?>
                                <span class="error-message"><?php echo $errors['new_password']; ?></span>
                            <?php endif; ?>
                            <small class="hint">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirm New Password *
                            </label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['confirm_password'])): ?>
                                <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="security-tips">
                        <h4><i class="fas fa-lightbulb"></i> Password Tips</h4>
                        <ul>
                            <li>Use at least 6 characters</li>
                            <li>Include numbers and special characters</li>
                            <li>Avoid common words or phrases</li>
                            <li>Don't reuse passwords from other sites</li>
                        </ul>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Preferences Settings -->
            <div class="settings-section" id="preferences-section">
                <div class="section-header">
                    <h2><i class="fas fa-sliders-h"></i> Preferences</h2>
                    <p>Customize your experience on PeerCart</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_preferences">
                    
                    <div class="form-group">
                        <label><i class="fas fa-palette"></i> Theme</label>
                        <div class="theme-options">
                            <label class="theme-option">
                                <input type="radio" name="theme" value="light" 
                                    <?php echo $preferences['theme'] == 'light' ? 'checked' : ''; ?>>
                                <div class="theme-preview light-theme">
                                    <i class="fas fa-sun"></i>
                                    <span>Light</span>
                                </div>
                            </label>
                            <label class="theme-option">
                                <input type="radio" name="theme" value="dark" 
                                    <?php echo $preferences['theme'] == 'dark' ? 'checked' : ''; ?>>
                                <div class="theme-preview dark-theme">
                                    <i class="fas fa-moon"></i>
                                    <span>Dark</span>
                                </div>
                            </label>
                            <label class="theme-option">
                                <input type="radio" name="theme" value="auto" 
                                    <?php echo $preferences['theme'] == 'auto' ? 'checked' : ''; ?>>
                                <div class="theme-preview auto-theme">
                                    <i class="fas fa-adjust"></i>
                                    <span>Auto</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="language">
                                <i class="fas fa-language"></i> Language
                            </label>
                            <select id="language" name="language">
                                <option value="en" <?php echo $preferences['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="af" <?php echo $preferences['language'] == 'af' ? 'selected' : ''; ?>>Afrikaans</option>
                                <option value="zu" <?php echo $preferences['language'] == 'zu' ? 'selected' : ''; ?>>Zulu</option>
                                <option value="xh" <?php echo $preferences['language'] == 'xh' ? 'selected' : ''; ?>>Xhosa</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">
                                <i class="fas fa-clock"></i> Timezone
                            </label>
                            <select id="timezone" name="timezone">
                                <?php foreach($timezones as $tz): ?>
                                    <option value="<?php echo $tz; ?>" 
                                        <?php echo $preferences['timezone'] == $tz ? 'selected' : ''; ?>>
                                        <?php echo $tz; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_digest">
                            <i class="fas fa-newspaper"></i> Email Digest
                        </label>
                        <select id="email_digest" name="email_digest">
                            <option value="never" <?php echo $preferences['email_digest'] == 'never' ? 'selected' : ''; ?>>Never</option>
                            <option value="daily" <?php echo $preferences['email_digest'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $preferences['email_digest'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo $preferences['email_digest'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                        <small class="hint">Receive summary emails about your activity</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Privacy Settings -->
            <div class="settings-section" id="privacy-section">
                <div class="section-header">
                    <h2><i class="fas fa-lock"></i> Privacy Settings</h2>
                    <p>Control who can see your information and contact you</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_privacy">
                    
                    <div class="form-group">
                        <label for="profile_visibility">
                            <i class="fas fa-globe"></i> Profile Visibility
                        </label>
                        <select id="profile_visibility" name="profile_visibility">
                            <option value="public" <?php echo $preferences['profile_visibility'] == 'public' ? 'selected' : ''; ?>>Public - Anyone can view</option>
                            <option value="members" <?php echo $preferences['profile_visibility'] == 'members' ? 'selected' : ''; ?>>Members Only - Logged in users only</option>
                            <option value="private" <?php echo $preferences['profile_visibility'] == 'private' ? 'selected' : ''; ?>>Private - Only you can view</option>
                        </select>
                    </div>
                    
                    <div class="privacy-options">
                        <h4><i class="fas fa-eye"></i> Information Visibility</h4>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_email" value="1" 
                                    <?php echo $preferences['show_email'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="label-text">Show email address on profile</span>
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_phone" value="1" 
                                    <?php echo $preferences['show_phone'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="label-text">Show phone number on profile</span>
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_location" value="1" 
                                    <?php echo $preferences['show_location'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="label-text">Show city and province</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="privacy-options">
                        <h4><i class="fas fa-comments"></i> Interactions</h4>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_messages" value="1" 
                                    <?php echo $preferences['allow_messages'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="label-text">Allow other users to message me</span>
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_following" value="1" 
                                    <?php echo $preferences['allow_following'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="label-text">Allow users to follow my activity</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="privacy-options">
                        <h4><i class="fas fa-search"></i> Search & Discovery</h4>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="search_index" value="1" 
                                    <?php echo $preferences['search_index'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="label-text">Include my profile in search results</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Privacy Settings
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Notifications Settings -->
            <div class="settings-section" id="notifications-section">
                <div class="section-header">
                    <h2><i class="fas fa-bell"></i> Notification Settings</h2>
                    <p>Choose what notifications you want to receive</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_preferences">
                    
                    <div class="notification-categories">
                        <div class="notification-category">
                            <h4><i class="fas fa-shopping-cart"></i> Marketplace Notifications</h4>
                            
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_email" value="1" 
                                        <?php echo $preferences['notifications_email'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">New messages from buyers/sellers</span>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_push" value="1" 
                                        <?php echo $preferences['notifications_push'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">Order updates and status changes</span>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_reviews" value="1" 
                                        <?php echo isset($preferences['notifications_reviews']) && $preferences['notifications_reviews'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">New reviews on your listings</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="notification-category">
                            <h4><i class="fas fa-users"></i> Community Notifications</h4>
                            
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_follows" value="1" 
                                        <?php echo isset($preferences['notifications_follows']) && $preferences['notifications_follows'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">New followers</span>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_comments" value="1" 
                                        <?php echo isset($preferences['notifications_comments']) && $preferences['notifications_comments'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">Comments on your activity</span>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_mentions" value="1" 
                                        <?php echo isset($preferences['notifications_mentions']) && $preferences['notifications_mentions'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">When you're mentioned</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="notification-category">
                            <h4><i class="fas fa-bullhorn"></i> Marketing & Updates</h4>
                            
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_newsletter" value="1" 
                                        <?php echo isset($preferences['notifications_newsletter']) && $preferences['notifications_newsletter'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">Newsletter and updates</span>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_promotions" value="1" 
                                        <?php echo isset($preferences['notifications_promotions']) && $preferences['notifications_promotions'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">Special offers and promotions</span>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="notifications_system" value="1" 
                                        <?php echo isset($preferences['notifications_system']) && $preferences['notifications_system'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="label-text">System announcements</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Notification Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation tabs
    const navItems = document.querySelectorAll('.settings-nav .nav-item');
    const sections = document.querySelectorAll('.settings-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (this.classList.contains('logout')) return;
            
            e.preventDefault();
            
            // Remove active class from all
            navItems.forEach(nav => nav.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked
            this.classList.add('active');
            
            const targetId = this.getAttribute('data-target');
            const targetSection = document.getElementById(targetId + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
    
    // Password toggle visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Theme preview
    document.querySelectorAll('.theme-option input').forEach(input => {
        input.addEventListener('change', function() {
            const theme = this.value;
            const preview = this.parentElement.querySelector('.theme-preview');
            
            // Remove active class from all previews
            document.querySelectorAll('.theme-preview').forEach(p => {
                p.classList.remove('active');
            });
            
            // Add active class to selected preview
            preview.classList.add('active');
        });
    });
    
    // Initialize theme previews
    document.querySelectorAll('.theme-preview').forEach((preview, index) => {
        const input = preview.parentElement.querySelector('input');
        if (input && input.checked) {
            preview.classList.add('active');
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('.settings-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');
                    
                    // Create error message if it doesn't exist
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                        const errorMsg = document.createElement('span');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                } else {
                    field.classList.remove('error');
                    
                    // Remove error message if it exists
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
    
    // Real-time password validation
    const passwordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput && confirmPasswordInput) {
        function validatePassword() {
            const password = passwordInput.value;
            const confirm = confirmPasswordInput.value;
            
            if (password.length > 0 && password.length < 6) {
                showPasswordError('Password must be at least 6 characters');
            } else if (confirm.length > 0 && password !== confirm) {
                showPasswordError('Passwords do not match');
            } else {
                clearPasswordError();
            }
        }
        
        function showPasswordError(message) {
            let errorDiv = document.getElementById('password-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'password-error';
                errorDiv.className = 'password-error';
                confirmPasswordInput.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
        }
        
        function clearPasswordError() {
            const errorDiv = document.getElementById('password-error');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
        
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePassword);
    }
    
    // Auto-save indicator
    const saveButtons = document.querySelectorAll('.btn-primary');
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            this.disabled = true;
            
            // Reset after 3 seconds (in case form submission fails)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
});
</script>

<style>
/* ============================================
   PEERCART SETTINGS PAGE - MODERN GLASSMORPHISM
   ============================================ */

:root {
    /* Modern Color Palette */
    --primary: #4361ee;
    --primary-light: #4895ef;
    --primary-dark: #3a0ca3;
    --secondary: #7209b7;
    --accent: #f72585;
    --success: #4cc9f0;
    --warning: #f8961e;
    --danger: #e63946;
    
    /* Neutrals */
    --dark: #1a1a2e;
    --dark-gray: #2d3047;
    --gray: #6c757d;
    --light-gray: #f8f9fa;
    --white: #ffffff;
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --text-primary: #1a1a2e;
    --text-secondary: #6c757d;
    --border-color: rgba(0, 0, 0, 0.1);
    
    /* Glassmorphism */
    --glass-bg: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.2);
    --glass-shadow: rgba(0, 0, 0, 0.1);
    
    /* Effects */
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
    --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
    --shadow-lg: 0 15px 35px rgba(0,0,0,0.15);
    --shadow-xl: 0 25px 50px rgba(0,0,0,0.2);
    --glow-primary: 0 0 20px rgba(67, 97, 238, 0.3);
    
    /* Spacing */
    --space-xs: 0.5rem;
    --space-sm: 1rem;
    --space-md: 1.5rem;
    --space-lg: 2rem;
    --space-xl: 3rem;
    
    /* Border Radius */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --radius-full: 100px;
    
    /* Transitions */
    --transition-fast: 0.2s ease;
    --transition-normal: 0.3s ease;
    --transition-slow: 0.5s ease;
}

/* Dark Mode Variables */
[data-theme="dark"] {
    --primary: #5a76ff;
    --primary-light: #6d8aff;
    --primary-dark: #4a5fcc;
    --secondary: #8d2bd4;
    --accent: #ff2b8c;
    --success: #5cd3f7;
    --warning: #ffaa47;
    --danger: #ff4d5c;
    
    --dark: #ffffff;
    --dark-gray: #e0e0e0;
    --gray: #a0a0a0;
    --light-gray: #2a2a3e;
    --white: #1a1a2e;
    --bg-primary: #121225;
    --bg-secondary: #1a1a2e;
    --text-primary: #ffffff;
    --text-secondary: #b0b0c0;
    --border-color: rgba(255, 255, 255, 0.1);
    
    --glass-bg: rgba(26, 26, 46, 0.95);
    --glass-border: rgba(255, 255, 255, 0.1);
    --glass-shadow: rgba(0, 0, 0, 0.3);
    
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
    --shadow-md: 0 8px 25px rgba(0,0,0,0.3);
    --shadow-lg: 0 15px 35px rgba(0,0,0,0.4);
    --shadow-xl: 0 25px 50px rgba(0,0,0,0.5);
    --glow-primary: 0 0 20px rgba(90, 118, 255, 0.4);
}

/* ============ BASE STYLES ============ */
.settings-container {
    position: relative;
    z-index: 1;
    padding: var(--space-md);
    max-width: 1400px;
    margin: 0 auto;
    min-height: calc(100vh - 200px);
}

.settings-header {
    text-align: center;
    margin-bottom: var(--space-xl);
    padding: var(--space-lg);
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--glass-border);
}

.settings-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: var(--space-sm);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
}

.settings-header .subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* ===== SETTINGS GRID ===== */
.settings-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: var(--space-lg);
}

@media (max-width: 992px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== SIDEBAR ===== */
.settings-sidebar {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--glass-border);
    overflow: hidden;
    height: fit-content;
    position: sticky;
    top: var(--space-md);
}

.sidebar-header {
    padding: var(--space-lg);
    text-align: center;
    background: linear-gradient(135deg, 
        rgba(67, 97, 238, 0.1) 0%, 
        rgba(114, 9, 183, 0.1) 100%);
    border-bottom: 1px solid var(--border-color);
}

.user-avatar {
    width: 100px;
    height: 100px;
    margin: 0 auto var(--space-md);
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--primary);
    box-shadow: var(--shadow-md);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
}

.user-info h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--space-xs);
    color: var(--text-primary);
}

.user-email {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: var(--space-xs);
}

.user-joined {
    color: var(--gray);
    font-size: 0.85rem;
}

/* Settings Navigation */
.settings-nav {
    padding: var(--space-md);
}

.nav-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-md);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-md);
    transition: all var(--transition-normal);
    margin-bottom: var(--space-xs);
    cursor: pointer;
}

.nav-item:hover {
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    transform: translateX(5px);
}

.nav-item.active {
    background: linear-gradient(135deg, 
        rgba(67, 97, 238, 0.15) 0%, 
        rgba(114, 9, 183, 0.15) 100%);
    color: var(--primary);
    font-weight: 600;
    border-left: 3px solid var(--primary);
}

.nav-item i {
    width: 20px;
    font-size: 1.1rem;
}

.nav-divider {
    height: 1px;
    background: var(--border-color);
    margin: var(--space-md) 0;
}

.nav-item.logout {
    color: var(--danger);
}

.nav-item.logout:hover {
    background: rgba(230, 57, 70, 0.1);
    color: var(--danger);
}

/* ===== SETTINGS CONTENT ===== */
.settings-content {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--glass-border);
    overflow: hidden;
}

.settings-section {
    display: none;
    opacity: 0;
    transform: translateY(20px);
    transition: all var(--transition-normal);
}

.settings-section.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(135deg, 
        rgba(67, 97, 238, 0.05) 0%, 
        rgba(114, 9, 183, 0.05) 100%);
}

.section-header h2 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: var(--space-xs);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.section-header p {
    color: var(--text-secondary);
    font-size: 1rem;
    line-height: 1.5;
}

/* ===== FORMS ===== */
.settings-form {
    padding: var(--space-lg);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-md);
    margin-bottom: var(--space-md);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: var(--space-md);
}

.form-group label {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-xs);
    font-size: 0.95rem;
}

.form-group label i {
    color: var(--primary);
    width: 20px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: var(--space-md);
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: all var(--transition-normal);
    background: var(--glass-bg);
    color: var(--text-primary);
    backdrop-filter: blur(10px);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
    transform: translateY(-2px);
}

.form-group input.error,
.form-group select.error,
.form-group textarea.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.15);
}

.error-message {
    display: block;
    color: var(--danger);
    font-size: 0.85rem;
    margin-top: var(--space-xs);
    font-weight: 500;
}

.hint {
    display: block;
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-top: var(--space-xs);
    font-style: italic;
}

/* Password Input */
.password-input {
    position: relative;
}

.password-input input {
    padding-right: 50px;
}

.toggle-password {
    position: absolute;
    right: var(--space-sm);
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: var(--space-xs);
    font-size: 1rem;
    transition: var(--transition-normal);
}

.toggle-password:hover {
    color: var(--primary);
}

/* Theme Options */
.theme-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-sm);
    margin-top: var(--space-xs);
}

@media (max-width: 480px) {
    .theme-options {
        grid-template-columns: 1fr;
    }
}

.theme-option {
    cursor: pointer;
}

.theme-option input {
    display: none;
}

.theme-preview {
    padding: var(--space-md);
    border-radius: var(--radius-md);
    border: 2px solid var(--border-color);
    text-align: center;
    transition: all var(--transition-normal);
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
}

.theme-preview:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.theme-preview.active {
    border-color: var(--primary);
    background: rgba(67, 97, 238, 0.1);
    box-shadow: var(--shadow-md);
}

.theme-preview i {
    font-size: 1.5rem;
    margin-bottom: var(--space-xs);
    display: block;
}

.theme-preview.light-theme {
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    color: #1a1a2e;
}

.theme-preview.dark-theme {
    background: linear-gradient(135deg, #1a1a2e, #121225);
    color: #ffffff;
}

.theme-preview.auto-theme {
    background: linear-gradient(135deg, #ffffff 50%, #1a1a2e 50%);
    color: #1a1a2e;
    position: relative;
}

.theme-preview.auto-theme i {
    position: relative;
    z-index: 1;
}

/* Checkbox Groups */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
    margin-top: var(--space-sm);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    cursor: pointer;
    padding: var(--space-sm);
    border-radius: var(--radius-md);
    transition: all var(--transition-normal);
}

.checkbox-label:hover {
    background: rgba(67, 97, 238, 0.05);
}

.checkbox-label input {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    position: relative;
    transition: all var(--transition-normal);
}

.checkbox-label input:checked + .checkmark {
    background: var(--primary);
    border-color: var(--primary);
}

.checkbox-label input:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 0.8rem;
    font-weight: bold;
}

.label-text {
    flex: 1;
    color: var(--text-primary);
    font-size: 0.95rem;
}

/* Privacy Options */
.privacy-options {
    margin-bottom: var(--space-lg);
    padding: var(--space-md);
    background: rgba(67, 97, 238, 0.05);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.privacy-options h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: var(--space-sm);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.privacy-options h4 i {
    color: var(--primary);
}

/* Notification Categories */
.notification-categories {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.notification-category {
    padding: var(--space-md);
    background: rgba(67, 97, 238, 0.05);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.notification-category h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: var(--space-md);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.notification-category h4 i {
    color: var(--primary);
}

/* Security Tips */
.security-tips {
    margin-top: var(--space-lg);
    padding: var(--space-md);
    background: linear-gradient(135deg, 
        rgba(76, 201, 240, 0.1) 0%, 
        rgba(67, 97, 238, 0.1) 100%);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--success);
}

.security-tips h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: var(--space-sm);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.security-tips h4 i {
    color: var(--success);
}

.security-tips ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.security-tips li {
    padding: var(--space-xs) 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
    position: relative;
    padding-left: var(--space-md);
}

.security-tips li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--success);
    font-weight: bold;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: var(--space-sm);
    margin-top: var(--space-xl);
    padding-top: var(--space-lg);
    border-top: 1px solid var(--border-color);
}

.btn {
    padding: var(--space-md) var(--space-xl);
    border-radius: var(--radius-full);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all var(--transition-normal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    border: 2px solid transparent;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.btn-outline {
    background: transparent;
    border-color: var(--border-color);
    color: var(--text-primary);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-3px);
}

/* Alerts */
.alert {
    padding: var(--space-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-lg);
    font-size: 0.95rem;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: flex-start;
    gap: var(--space-sm);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert i {
    font-size: 1.2rem;
    margin-top: 2px;
}

.alert-danger {
    border-left: 4px solid var(--danger);
    background: linear-gradient(135deg, 
        rgba(230, 57, 70, 0.1), 
        rgba(230, 57, 70, 0.05));
    color: var(--text-primary);
}

.alert-danger i {
    color: var(--danger);
}

.alert-success {
    border-left: 4px solid var(--success);
    background: linear-gradient(135deg, 
        rgba(76, 201, 240, 0.1), 
        rgba(76, 201, 240, 0.05));
    color: var(--text-primary);
}

.alert-success i {
    color: var(--success);
}

/* Password Error */
.password-error {
    color: var(--danger);
    font-size: 0.85rem;
    margin-top: var(--space-xs);
    padding: var(--space-xs) var(--space-sm);
    background: rgba(230, 57, 70, 0.1);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--danger);
}

/* Responsive Design */
@media (max-width: 768px) {
    .settings-container {
        padding: var(--space-sm);
    }
    
    .settings-header {
        padding: var(--space-md);
    }
    
    .settings-header h1 {
        font-size: 2rem;
    }
    
    .settings-form {
        padding: var(--space-md);
    }
    
    .section-header {
        padding: var(--space-md);
    }
    
    .section-header h2 {
        font-size: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .settings-header h1 {
        font-size: 1.75rem;
        flex-direction: column;
        gap: var(--space-xs);
    }
    
    .user-avatar {
        width: 80px;
        height: 80px;
    }
    
    .nav-item {
        padding: var(--space-sm);
        font-size: 0.9rem;
    }
}
</style>

<?php 
if (function_exists('includePartial')) {
    includePartial('footer');
} else {
    require_once __DIR__ . '/../includes/footer.php';
}