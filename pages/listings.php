<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/bootstrap.php';

$title = 'PeerCart - Discover Amazing Deals';
$currentPage = 'listings';

// Set additional styles BEFORE including header
$additionalStyles = ['listings'];

// Check if includePartial exists, otherwise include header directly
if (function_exists('includePartial')) {
    includePartial('header', compact('title', 'currentPage', 'additionalStyles'));
} else {
    // Fallback: include header directly with all required variables
    $pageHead = '';
    require_once __DIR__ . '/../includes/header.php';
}

// --- Filter variables ---
$search = $_GET['search'] ?? '';
// Accept both 'category' and 'category_id' parameters
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : (isset($_GET['category']) ? $_GET['category'] : '');
$condition = $_GET['condition'] ?? '';
$city = $_GET['city'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$filter = $_GET['filter'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

$listings = [];
$totalListings = 0;
$categories = [];

// NEW: Add filter parameter
$filter = $_GET['filter'] ?? '';
$listings = [];
$totalListings = 0;
$categories = [];

try {
    $db = Database::getInstance();

    // Base query - USING ONLY COLUMNS THAT EXIST IN YOUR DATABASE
    // Based on your database dump, listings table has these columns:
    // id, seller_id, category_id, name, slug, description, price, original_price, 
    // quantity, image, images, item_condition, status, featured, views, is_active, 
    // province, city, created_at, updated_at
    $query = "SELECT 
                l.id, l.name, l.price, l.original_price, l.image, 
                l.item_condition, l.created_at, l.featured, l.views,
                l.quantity, l.status, l.is_active, l.city as listing_city, l.province,
                u.name as seller_name, u.city as seller_city
                -- REMOVED: c.name as category_name
              FROM listings l
              JOIN users u ON l.seller_id = u.id
              -- REMOVED: LEFT JOIN categories c ON l.category_id = c.id
              WHERE l.is_active = 1 AND l.status = 'active'";
    
    $params = [];
    $whereConditions = [];

    // Apply filters
    if(!empty($search)){
        $whereConditions[] = "(l.name LIKE ? OR l.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if(!empty($category_id) && is_numeric($category_id)){
        $whereConditions[] = "l.category_id = ?";
        $params[] = $category_id;
    }
    if(!empty($condition)){
        $whereConditions[] = "l.item_condition = ?";
        $params[] = $condition;
    }
    if(!empty($city)){
        $whereConditions[] = "u.city = ?";
        $params[] = $city;
    }
    if(!empty($min_price) && is_numeric($min_price)){
        $whereConditions[] = "l.price >= ?";
        $params[] = $min_price;
    }
    if(!empty($max_price) && is_numeric($max_price)){
        $whereConditions[] = "l.price <= ?";
        $params[] = $max_price;
    }

    // NEW: Apply special filters - UPDATED: removed urgent filter (no is_urgent column)
    if(!empty($filter)){
        switch($filter){
            case 'discount':
                // Check only original_price for discounts
                $whereConditions[] = "(l.original_price IS NOT NULL AND l.original_price > l.price)";
                break;
            case 'featured':
                $whereConditions[] = "l.featured = 1";
                break;
            case 'new':
                $whereConditions[] = "l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'urgent':
                // REMOVED: is_urgent column doesn't exist
                // Alternative: show items with low quantity
                $whereConditions[] = "l.quantity <= 5";
                break;
            case 'top_rated':
                // Show items with most views
                $whereConditions[] = "l.views > 50";
                break;
            case 'ending_soon':
                // Show items with low quantity
                $whereConditions[] = "l.quantity <= 3";
                break;
        }
    }

    if(!empty($whereConditions)){
        $query .= " AND " . implode(" AND ", $whereConditions);
    }

    // Get total for pagination
    $countQuery = "SELECT COUNT(*) as total FROM listings l 
                   JOIN users u ON l.seller_id = u.id 
                   WHERE l.is_active = 1 AND l.status = 'active'";
    if(!empty($whereConditions)){
        $countQuery .= " AND " . implode(" AND ", $whereConditions);
    }
    $totalResult = $db->getRow($countQuery, $params);
    $totalListings = $totalResult['total'] ?? 0;

    // Sorting
    switch($sort){
        case 'price_low': $query .= " ORDER BY l.price ASC"; break;
        case 'price_high': $query .= " ORDER BY l.price DESC"; break;
        case 'popular': $query .= " ORDER BY l.views DESC"; break;
        case 'ending_soon': $query .= " ORDER BY l.quantity ASC"; break;
        default: $query .= " ORDER BY l.created_at DESC";
    }

    // Pagination
    $offset = ($page-1)*$perPage;
    $query .= " LIMIT $offset, $perPage";

    $listings = $db->getRows($query, $params);

    // Get categories for filter dropdown
    $categories = $db->getRows("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

} catch(Exception $e){
    $error = "Database error: " . $e->getMessage();
    error_log("Listings error: " . $e->getMessage());
}

// Helper functions
if (!function_exists('getCities')) {
    function getCities($limit = null) {
        try {
            $db = Database::getInstance();
            $query = "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city";
            if ($limit) {
                $query .= " LIMIT " . intval($limit);
            }
            $results = $db->getRows($query);
            return array_column($results, 'city');
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}

if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl($pageNum) {
        $params = $_GET;
        $params['page'] = $pageNum;
        return '?' . http_build_query($params);
    }
}

// Function to get filter display names
function getFilterDisplayName($key, $value) {
    switch($key) {
        case 'filter':
            $names = [
                'discount' => 'Discounted Items',
                'featured' => 'Featured Items',
                'new' => 'New Items',
                'urgent' => 'Urgent Deals',
                'top_rated' => 'Top Rated',
                'ending_soon' => 'Ending Soon'
            ];
            return $names[$value] ?? ucfirst(str_replace('_', ' ', $value));
        case 'condition':
            $names = [
                'new' => 'New',
                'used_like_new' => 'Used - Like New',
                'used_good' => 'Used - Good',
                'used_fair' => 'Used - Fair'
            ];
            return $names[$value] ?? $value;
        case 'sort':
            $names = [
                'newest' => 'Newest First',
                'price_low' => 'Price: Low to High',
                'price_high' => 'Price: High to Low',
                'popular' => 'Most Popular',
                'ending_soon' => 'Ending Soon'
            ];
            return $names[$value] ?? ucfirst($value);
        case 'category_id':
            // This would need database lookup - returning simplified version
            return "Category: $value";
        default:
            return ucfirst(str_replace('_', ' ', $value));
    }
}

// Function to build URL without specific filter
function buildUrlWithoutFilter($key, $value = null) {
    $params = $_GET;
    if ($value !== null) {
        // Remove specific value from array if key is an array
        if (isset($params[$key]) && is_array($params[$key])) {
            $params[$key] = array_diff($params[$key], [$value]);
            if (empty($params[$key])) {
                unset($params[$key]);
            }
        } else {
            unset($params[$key]);
        }
    } else {
        unset($params[$key]);
    }
    unset($params['page']); // Reset to page 1 when removing filter
    return '?' . http_build_query($params);
}
?>

<link rel="stylesheet" href="<?= asset('css/pages/listings.css') ?>?v=<?= time() ?>">

<!-- END OF PHP SECTION -->
<div class="listings-container">

    <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="search-container">
        <form method="get">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="What are you looking for?" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>
    </div>

    <!-- Applied Filters Section -->
    <?php
    $activeFilters = [];
    if (!empty($search)) $activeFilters[] = ['key' => 'search', 'value' => $search, 'display' => "Search: \"$search\""];
    if (!empty($category_id) && !empty($categories)) {
        foreach($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $activeFilters[] = ['key' => 'category_id', 'value' => $category_id, 'display' => "Category: " . htmlspecialchars($cat['name'])];
                break;
            }
        }
    }
    if (!empty($condition)) $activeFilters[] = ['key' => 'condition', 'value' => $condition, 'display' => getFilterDisplayName('condition', $condition)];
    if (!empty($city)) $activeFilters[] = ['key' => 'city', 'value' => $city, 'display' => "City: " . htmlspecialchars($city)];
    if (!empty($min_price)) $activeFilters[] = ['key' => 'min_price', 'value' => $min_price, 'display' => "Min Price: R" . number_format($min_price, 2)];
    if (!empty($max_price)) $activeFilters[] = ['key' => 'max_price', 'value' => $max_price, 'display' => "Max Price: R" . number_format($max_price, 2)];
    if (!empty($filter)) $activeFilters[] = ['key' => 'filter', 'value' => $filter, 'display' => getFilterDisplayName('filter', $filter)];
    if ($sort !== 'newest') $activeFilters[] = ['key' => 'sort', 'value' => $sort, 'display' => getFilterDisplayName('sort', $sort)];
    
    if (!empty($activeFilters)): ?>
    <div class="applied-filters-section">
        <div class="applied-filters-header">
            <h4><i class="fas fa-filter"></i> Applied Filters</h4>
            <a href="?" class="clear-all-filters">
                <i class="fas fa-times-circle"></i> Clear All
            </a>
        </div>
        <div class="active-filters">
            <?php foreach($activeFilters as $filterItem): ?>
            <a href="<?php echo buildUrlWithoutFilter($filterItem['key'], $filterItem['value']); ?>" class="filter-tag">
                <?php echo htmlspecialchars($filterItem['display']); ?>
                <i class="fas fa-times"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="filter-section">
        <div class="filter-header">
            <h3><i class="fas fa-sliders-h"></i> Filter Listings</h3>
            <button type="button" class="filter-toggle-btn" id="filterToggleBtn">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        
        <form method="get" class="filter-controls" id="filterControls">
            <input type="hidden" name="page" value="1">
            
            <!-- Quick Links Section - Compact -->
            <div class="quick-links-section">
                <h4 class="section-title">
                    <i class="fas fa-bolt"></i> Quick Filters
                </h4>
                <div class="quick-links-grid">
                    <button type="submit" name="filter" value="discount" class="quick-link">
                        <i class="fas fa-percentage"></i>
                        <span>Discounted</span>
                    </button>
                    <button type="submit" name="filter" value="featured" class="quick-link">
                        <i class="fas fa-star"></i>
                        <span>Featured</span>
                    </button>
                    <button type="submit" name="filter" value="new" class="quick-link">
                        <i class="fas fa-certificate"></i>
                        <span>New Arrivals</span>
                    </button>
                    <button type="submit" name="filter" value="ending_soon" class="quick-link">
                        <i class="fas fa-clock"></i>
                        <span>Ending Soon</span>
                    </button>
                    <button type="submit" name="filter" value="top_rated" class="quick-link">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Top Rated</span>
                    </button>
                    <button type="submit" name="filter" value="urgent" class="quick-link">
                        <i class="fas fa-fire"></i>
                        <span>Low Stock</span>
                    </button>
                </div>
            </div>
            
            <div class="filter-grid">
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Category</label>
                    <select name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id==$cat['id'])?'selected':''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-certificate"></i> Condition</label>
                    <select name="condition">
                        <option value="">Any Condition</option>
                        <option value="new" <?php echo ($condition=='new')?'selected':''; ?>>New</option>
                        <option value="used_like_new" <?php echo ($condition=='used_like_new')?'selected':''; ?>>Used - Like New</option>
                        <option value="used_good" <?php echo ($condition=='used_good')?'selected':''; ?>>Used - Good</option>
                        <option value="used_fair" <?php echo ($condition=='used_fair')?'selected':''; ?>>Used - Fair</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> City</label>
                    <select name="city">
                        <option value="">All Cities</option>
                        <?php 
                        $allCities = getCities();
                        foreach($allCities as $cityOption): 
                        ?>
                        <option value="<?php echo htmlspecialchars($cityOption); ?>" <?php echo ($city==$cityOption)?'selected':''; ?>><?php echo htmlspecialchars($cityOption); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sort">
                        <option value="newest" <?php echo ($sort=='newest')?'selected':''; ?>>Newest First</option>
                        <option value="price_low" <?php echo ($sort=='price_low')?'selected':''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo ($sort=='price_high')?'selected':''; ?>>Price: High to Low</option>
                        <option value="popular" <?php echo ($sort=='popular')?'selected':''; ?>>Most Popular</option>
                        <option value="ending_soon" <?php echo ($sort=='ending_soon')?'selected':''; ?>>Ending Soon</option>
                    </select>
                </div>
                <div class="filter-group price-range">
                    <label><i class="fas fa-rand-sign"></i> Price Range (R)</label>
                    <div class="price-inputs">
                        <input type="number" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>" min="0" step="0.01">
                        <input type="number" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>" min="0" step="0.01">
                        <button type="submit" class="apply-price">Apply</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="listings-grid">
        <?php if(empty($listings)): ?>
        <div class="no-results">
            <i class="fas fa-search fa-2x"></i>
            <h3>No listings found</h3>
            <p>Try adjusting your search filters</p>
            <a href="?" class="btn btn-outline">Clear all filters</a>
        </div>
        <?php else: 
        foreach($listings as $listing): 
            // Use getListingImage function if it exists, otherwise use fallback
            if (function_exists('getListingImage')) {
                $imageUrl = getListingImage($listing['image']);
            } else {
                $imageUrl = !empty($listing['image']) ? 
                    BASE_URL . '/assets/uploads/' . $listing['image'] : 
                    BASE_URL . '/../assets/images/products/default-product.png';
            }
            
            $conditionText = ucfirst(str_replace('_', ' ', $listing['item_condition']));
            $isNew = strtotime($listing['created_at']) > strtotime('-3 days');
            
            // Check if item has discount using original_price
            $hasDiscount = false;
            $savingsAmount = 0;
            $discountPercent = 0;
            
            if (!empty($listing['original_price']) && $listing['original_price'] > $listing['price']) {
                $hasDiscount = true;
                $savingsAmount = $listing['original_price'] - $listing['price'];
                $discountPercent = round(($savingsAmount / $listing['original_price']) * 100);
            }
            
            // Check if item is ending soon (low quantity)
            $isEndingSoon = ($listing['quantity'] <= 3);
            
            // Check if item is sold out (quantity is 0)
            $isSoldOut = ($listing['quantity'] == 0);
            
            // Determine which city to show
            $displayCity = !empty($listing['seller_city']) ? $listing['seller_city'] : 
                          (!empty($listing['listing_city']) ? $listing['listing_city'] : 'Not specified');
        ?>
        <div class="listing-card">
            <a href="<?php echo BASE_URL; ?>/pages/listing.php?id=<?php echo $listing['id']; ?>">
                <div class="listing-image">
                    <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($listing['name']); ?>" onerror="this.src='<?php echo BASE_URL; ?>/../assets/images/products/default-product.png'">
                    <span class="price-tag">R<?php echo number_format($listing['price'],2); ?></span>
                    <?php if($isNew): ?>
                        <span class="badge-new">New</span>
                    <?php endif; ?>
                    <?php if($listing['featured']): ?>
                        <span class="badge-featured">Featured</span>
                    <?php endif; ?>
                    <?php if($isEndingSoon && !$isSoldOut): ?>
                        <span class="badge-urgent">Low Stock</span>
                    <?php endif; ?>
                    <?php if($isSoldOut): ?>
                        <span class="badge-sold-out">Sold Out</span>
                    <?php endif; ?>
                    <?php if($hasDiscount): ?>
                        <span class="badge-discount">-<?php echo $discountPercent; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="listing-details">
                    <h3><?php echo htmlspecialchars($listing['name']); ?></h3>
                    <!-- REMOVED: Category display -->
                    <p class="condition"><i class="fas fa-certificate"></i> <?php echo $conditionText; ?></p>
                    <?php if($hasDiscount): ?>
                        <p class="original-price">
                            <s>R<?php echo number_format($listing['original_price'], 2); ?></s>
                            <span class="discount-text">Save R<?php echo number_format($savingsAmount, 2); ?></span>
                        </p>
                    <?php endif; ?>
                    <?php if($isEndingSoon && !$isSoldOut): ?>
                        <p class="quantity-warning"><i class="fas fa-exclamation-triangle"></i> Only <?php echo $listing['quantity']; ?> left!</p>
                    <?php endif; ?>
                    <?php if($isSoldOut): ?>
                        <p class="quantity-sold-out"><i class="fas fa-times-circle"></i> Sold Out</p>
                    <?php endif; ?>
                    <p class="city"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($displayCity); ?></p>
                    <div class="listing-footer">
                        <span class="seller"><i class="fas fa-user"></i> <?php echo htmlspecialchars($listing['seller_name']); ?></span>
                        <span class="time"><?php echo timeAgo($listing['created_at']); ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if($totalListings > $perPage): 
    $totalPages = ceil($totalListings/$perPage);
    ?>
    <div class="pagination-container">
        <nav class="pagination">
            <?php if($page > 1): ?>
            <a href="<?php echo buildPaginationUrl($page-1); ?>" class="page-link prev"><i class="fas fa-chevron-left"></i> Previous</a>
            <?php endif; ?>
            
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if($startPage > 1) echo '<span>...</span>';
            
            for($i = $startPage; $i <= $endPage; $i++):
            ?>
            <a href="<?php echo buildPaginationUrl($i); ?>" class="page-link <?php echo ($i==$page)?'active':''; ?>"><?php echo $i; ?></a>
            <?php 
            endfor; 
            
            if($endPage < $totalPages) echo '<span>...</span>'; 
            ?>
            
            <?php if($page < $totalPages): ?>
            <a href="<?php echo buildPaginationUrl($page+1); ?>" class="page-link next">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    // Auto-submit selects
    document.querySelectorAll('select').forEach(sel=>{
        sel.addEventListener('change',()=>{ sel.form.submit(); });
    });
    
    // Collapsible filter functionality
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterControls = document.getElementById('filterControls');
    const filterHeader = document.querySelector('.filter-header');
    
    if (filterToggleBtn && filterControls && filterHeader) {
        // Check if filters are applied to determine initial state
        const urlParams = new URLSearchParams(window.location.search);
        const hasFilters = Array.from(urlParams.keys()).some(key => 
            ['search', 'category_id', 'condition', 'city', 'min_price', 'max_price', 'filter'].includes(key)
        );
        
        // Expand if filters are applied
        if (hasFilters) {
            filterControls.classList.add('expanded');
            filterToggleBtn.classList.add('collapsed');
        }
        
        // Toggle function
        const toggleFilters = () => {
            filterControls.classList.toggle('expanded');
            filterToggleBtn.classList.toggle('collapsed');
        };
        
        // Make entire header clickable
        filterHeader.addEventListener('click', (e) => {
            if (e.target !== filterToggleBtn && !filterToggleBtn.contains(e.target)) {
                toggleFilters();
            }
        });
        
        // Button click handler
        filterToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleFilters();
        });
    }
    
    // Quick links - submit forms properly
    document.querySelectorAll('.quick-link[type="submit"]').forEach(button => {
        button.addEventListener('click', function(e) {
            const form = this.closest('form');
            // Clear other filter values when quick link is clicked
            const inputs = form.querySelectorAll('input[name], select[name]');
            inputs.forEach(input => {
                if (input.name !== 'filter' && input.name !== 'page') {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                }
            });
            form.submit();
        });
    });
});
</script>

<?php 
if (function_exists('includePartial')) {
    includePartial('footer');
} else {
    require_once __DIR__ . '/../includes/footer.php';
}