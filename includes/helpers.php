<?php
/**
 * PeerCart - Helper Functions
 * ADDITIONAL helper functions (not in functions.php)
 */

// ============================================================================
// IMAGE HELPER FUNCTIONS
// ============================================================================

if (!function_exists('getImageUrl')) {
    /**
     * Get image URL with fallback
     */
    function getImageUrl(?string $imagePath, string $type = 'listing'): string {
        if (empty($imagePath)) {
            switch ($type) {
                case 'profile':
                    return asset('images/users/default-user.png');
                case 'product':
                case 'listing':
                default:
                    return asset('images/products/default-product.png');
            }
        }
        
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        
        $possiblePaths = [
            'uploads/listings/' . basename($imagePath),
            'uploads/profile/' . basename($imagePath),
            'assets/uploads/' . basename($imagePath),
            'uploads/' . basename($imagePath),
            $imagePath
        ];
        
        foreach ($possiblePaths as $path) {
            $fullPath = ROOT_PATH . '/' . ltrim($path, '/');
            if (file_exists($fullPath) && is_file($fullPath)) {
                return asset($path);
            }
        }
        
        return asset('images/products/default-product.png');
    }
}

// ============================================================================
// TIME AND DATE HELPERS
// ============================================================================

if (!function_exists('time_elapsed_string')) {
    /**
     * Format time elapsed string
     */
    function time_elapsed_string($datetime, $full = false): string {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

// ============================================================================
// USER HELPER FUNCTIONS
// ============================================================================

if (!function_exists('getUserFullName')) {
    /**
     * Get user's full name
     */
    function getUserFullName(?int $userId = null): string {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $firstName = $_SESSION['user_name'] ?? '';
            $lastName = $_SESSION['user_surname'] ?? '';
            return trim($firstName . ' ' . $lastName) ?: 'User';
        }
        
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                return trim($user['name'] . ' ' . $user['surname']) ?: 'User';
            }
        } catch (Exception $e) {
            error_log("Failed to get user name: " . $e->getMessage());
        }
        
        return 'User';
    }
}

if (!function_exists('getUserProfileImage')) {
    /**
     * Get user's profile image URL
     */
    function getUserProfileImage(?int $userId = null): string {
        $defaultImage = asset('images/users/default-user.png');
        
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if (!$userId) {
            return $defaultImage;
        }
        
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT profile_image FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user && !empty($user['profile_image'])) {
                return getImageUrl($user['profile_image'], 'profile');
            }
        } catch (Exception $e) {
            error_log("Failed to get user profile image: " . $e->getMessage());
        }
        
        return $defaultImage;
    }
}

// ============================================================================
// STRING AND TEXT HELPERS
// ============================================================================

if (!function_exists('truncate')) {
    /**
     * Truncate text with ellipsis
     */
    function truncate(string $text, int $length = 100, string $ellipsis = '...'): string {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        return $text . $ellipsis;
    }
}

if (!function_exists('slugify')) {
    /**
     * Generate