<?php
// Helper functions

// Returns the full path for a listing image with fallback


// Returns the full path for a user profile image with fallback
function getUserImage($imagePath = null) {
    $uploadsPath = __DIR__ . '/../uploads/profile/';
    $defaultImage = 'assets/images/users/default-user.png';

    if (!empty($imagePath) && file_exists($uploadsPath . basename($imagePath))) {
        return BASE_URL . '/uploads/profile/' . basename($imagePath);
    }
    return asset($defaultImage);
}

// Time ago formatting
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff/60) . " min ago";
    if ($diff < 86400) return floor($diff/3600) . " hours ago";
    if ($diff < 604800) return floor($diff/86400) . " days ago";
    return date('M j, Y', $time);
}

// Build pagination URL
function buildPaginationUrl($page, $params = []) {
    $params['page'] = $page;
    return url('pages/listings.php?' . http_build_query(array_filter($params)));
}

// Fetch promotions
function getPromotions($limit = 8) {
    try {
        $db = Database::getInstance();
        return $db->getRows("
            SELECT p.id, p.name, p.price, p.original_price,
            ROUND((1 - p.price / NULLIF(p.original_price,0))*100) AS discount_percent,
            u.name AS seller_name, p.image
            FROM listings p
            JOIN users u ON p.seller_id = u.id
            WHERE p.original_price IS NOT NULL 
              AND p.original_price > p.price
              AND p.is_active = 1
            ORDER BY discount_percent DESC, p.created_at DESC
            LIMIT $limit
        ");
    } catch (PDOException $e) {
        error_log("Promotions load error: " . $e->getMessage());
        return [];
    }
}

// Fetch recent listings
function getRecentListings($limit = 20) {
    try {
        $db = Database::getInstance();
        return $db->getRows("
            SELECT l.id, l.name AS title, l.price, l.image, l.created_at,
            u.name AS seller_name, u.city AS seller_city
            FROM listings l
            LEFT JOIN users u ON l.seller_id = u.id
            WHERE l.is_active = 1
            ORDER BY l.created_at DESC
            LIMIT $limit
        ");
    } catch (PDOException $e) {
        error_log("Recent listings error: " . $e->getMessage());
        return [];
    }
}

// Fetch testimonials
function getTestimonials($limit = 6) {
    try {
        $db = Database::getInstance();
        return $db->getRows("
            SELECT t.id, u.name AS user_name, t.testimonial_text, t.rating, t.created_at
            FROM testimonials t
            INNER JOIN users u ON t.user_id = u.id
            WHERE t.status='approved'
            ORDER BY t.created_at DESC
            LIMIT $limit
        ");
    } catch (PDOException $e) {
        error_log("Testimonials error: " . $e->getMessage());
        return [];
    }
}

// Fetch categories
?>
