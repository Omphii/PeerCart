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
$category_id = $_GET['category_id'] ?? '';
$condition = $_GET['condition'] ?? '';
$city = $_GET['city'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// NEW: Add filter parameter
$filter = $_GET['filter'] ?? '';
$listings = [];
$totalListings = 0;
$categories = [];

try {
    $db = Database::getInstance();

    // Base query
    $query = "SELECT 
                l.id, l.name, l.price, l.image, l.item_condition, l.created_at, l.featured,
                l.discount_price, l.discount_expiry, l.is_urgent, l.views,
                u.name as seller_name, u.city as seller_city,
                c.name as category_name
              FROM listings l
              JOIN users u ON l.seller_id = u.id
              LEFT JOIN categories c ON l.category_id = c.id
              WHERE l.is_active = 1";
    
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

    // NEW: Apply special filters
    if(!empty($filter)){
        switch($filter){
            case 'discount':
                $whereConditions[] = "l.discount_price IS NOT NULL AND l.discount_price > 0 AND (l.discount_expiry IS NULL OR l.discount_expiry >= CURDATE())";
                break;
            case 'featured':
                $whereConditions[] = "l.featured = 1";
                break;
            case 'new':
                $whereConditions[] = "l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'urgent':
                $whereConditions[] = "l.is_urgent = 1";
                break;
            // Add more filter cases as needed
        }
    }

    if(!empty($whereConditions)){
        $query .= " AND " . implode(" AND ", $whereConditions);
    }

    // Get total for pagination
    $countQuery = "SELECT COUNT(*) as total FROM listings l JOIN users u ON l.seller_id = u.id WHERE l.is_active = 1";
    if(!empty($whereConditions)){
        $countQuery .= " AND " . implode(" AND ", $whereConditions);
    }
    $totalListings = $db->getValue($countQuery, $params);

    // Sorting
    switch($sort){
        case 'price_low': $query .= " ORDER BY l.price ASC"; break;
        case 'price_high': $query .= " ORDER BY l.price DESC"; break;
        case 'popular': $query .= " ORDER BY l.views DESC"; break;
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
}

?>
<div class="listings-container">

    <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    

    <div class="search-container">
        <form method="get">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="What are you looking for?" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>
    </div>

    <div class="filter-section">
        <div class="filter-header">
            <h3><i class="fas fa-sliders-h"></i> Filter Listings</h3>
            <button type="button" class="filter-toggle-btn" id="filterToggleBtn">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        
        <form method="get" class="filter-controls" id="filterControls">
            <input type="hidden" name="page" value="1">
            <div class="filter-grid">
                <!-- Column 2: Quick Links - UPDATED TO REMOVE URGENT -->
<div class="dropdown-section">
    <h4 class="section-title">
        <i class="fas fa-bolt"></i> Quick Links
    </h4>
    <div class="section-items quick-links-grid">
        <a href="<?= BASE_URL ?>/pages/listings.php?filter=discount" class="quick-link">
            <i class="fas fa-percentage"></i>
            <span>Discounted</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/listings.php?filter=featured" class="quick-link">
            <i class="fas fa-star"></i>
            <span>Featured</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/listings.php?filter=new" class="quick-link">
            <i class="fas fa-certificate"></i>
            <span>New</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/trending.php" class="quick-link">
            <i class="fas fa-chart-line"></i>
            <span>Trending</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/clearance.php" class="quick-link">
            <i class="fas fa-fire"></i>
            <span>Clearance</span>
        </a>
        <!-- You could add another useful filter here instead of urgent -->
        <a href="<?= BASE_URL ?>/pages/listings.php?filter=top_rated" class="quick-link">
            <i class="fas fa-thumbs-up"></i>
            <span>Top Rated</span>
        </a>
    </div>
</div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Category</label>
                    <select name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($category_id==$cat['id'])?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-certificate"></i> Condition</label>
                    <select name="condition">
                        <option value="">Any</option>
                        <option value="new" <?= ($condition=='new')?'selected':'' ?>>New</option>
                        <option value="used_like_new" <?= ($condition=='used_like_new')?'selected':'' ?>>Used - Like New</option>
                        <option value="used_good" <?= ($condition=='used_good')?'selected':'' ?>>Used - Good</option>
                        <option value="used_fair" <?= ($condition=='used_fair')?'selected':'' ?>>Used - Fair</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> City</label>
                    <select name="city">
                        <option value="">All Cities</option>
                        <?php foreach(getCities() as $cityOption): ?>
                        <option value="<?= htmlspecialchars($cityOption) ?>" <?= ($city==$cityOption)?'selected':'' ?>><?= htmlspecialchars($cityOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sort">
                        <option value="newest" <?= ($sort=='newest')?'selected':'' ?>>Newest</option>
                        <option value="price_low" <?= ($sort=='price_low')?'selected':'' ?>>Price: Low to High</option>
                        <option value="price_high" <?= ($sort=='price_high')?'selected':'' ?>>Price: High to Low</option>
                        <option value="popular" <?= ($sort=='popular')?'selected':'' ?>>Most Popular</option>
                    </select>
                </div>
                <div class="filter-group price-range">
                    <label><i class="fas fa-rand-sign"></i> Price Range (R)</label>
                    <div class="price-inputs">
                        <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>" min="0" step="0.01">
                        <span>to</span>
                        <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>" min="0" step="0.01">
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
            <a href="<?= url('pages/listings.php') ?>" class="btn btn-outline">Clear all filters</a>
        </div>
        <?php else: foreach($listings as $listing): ?>
        <div class="listing-card">
            <a href="<?= url('pages/listing.php?id='.$listing['id']) ?>">
                <div class="listing-image">
                    <img src="<?= !empty($listing['image']) ? BASE_URL . '/assets/uploads/' . $listing['image'] : BASE_URL . '/assets/images/products/default-product.png' ?>" alt="<?= htmlspecialchars($listing['name']) ?>" onerror="this.src='https://via.placeholder.com/300x200/4361ee/ffffff?text=No+Image'">
                    <span class="price-tag">R<?= number_format($listing['price'],2) ?></span>
                    <?php if($listing['created_at'] > date('Y-m-d H:i:s', strtotime('-3 days'))): ?>
                        <span class="badge-new">New</span>
                    <?php endif; ?>
                    <?php if($listing['featured']): ?>
                        <span class="badge-featured">Featured</span>
                    <?php endif; ?>
                </div>
                <div class="listing-details">
                    <h3><?= htmlspecialchars($listing['name']) ?></h3>
                    <?php if(!empty($listing['category_name'])): ?>
                        <p class="category"><i class="fas fa-tag"></i> <?= htmlspecialchars($listing['category_name']) ?></p>
                    <?php endif; ?>
                    <p class="condition"><i class="fas fa-certificate"></i> <?= ucfirst(str_replace('_',' ',$listing['item_condition'])) ?></p>
                    <p class="city"><i class="fas fa-map-marker-alt"></i> <?= !empty($listing['seller_city'])?htmlspecialchars($listing['seller_city']):'Not specified' ?></p>
                    <div class="listing-footer">
                        <span class="seller"><i class="fas fa-user"></i> <?= htmlspecialchars($listing['seller_name']) ?></span>
                        <span class="time"><?= timeAgo($listing['created_at']) ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <?php if($totalListings>$perPage): ?>
    <div class="pagination-container">
        <nav class="pagination">
            <?php if($page>1): ?>
            <a href="<?= buildPaginationUrl($page-1) ?>" class="page-link prev"><i class="fas fa-chevron-left"></i> Previous</a>
            <?php endif; ?>
            <?php 
            $totalPages = ceil($totalListings/$perPage);
            $startPage = max(1,$page-2);
            $endPage = min($totalPages,$page+2);
            if($startPage>1) echo '<span>...</span>';
            for($i=$startPage;$i<=$endPage;$i++):
            ?>
            <a href="<?= buildPaginationUrl($i) ?>" class="page-link <?= ($i==$page)?'active':'' ?>"><?= $i ?></a>
            <?php endfor; if($endPage<$totalPages) echo '<span>...</span>'; ?>
            <?php if($page<$totalPages): ?>
            <a href="<?= buildPaginationUrl($page+1) ?>" class="page-link next">Next <i class="fas fa-chevron-right"></i></a>
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
        // Toggle function
        const toggleFilters = () => {
            filterControls.classList.toggle('expanded');
            filterToggleBtn.classList.toggle('collapsed');
        };
        
        // Make entire header clickable
        filterHeader.addEventListener('click', (e) => {
            // Only toggle if click wasn't on the button itself
            // (the button has its own click handler)
            if (e.target !== filterToggleBtn && !filterToggleBtn.contains(e.target)) {
                toggleFilters();
            }
        });
        
        // Button click handler
        filterToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent header click from also triggering
            toggleFilters();
        });
    }
});
</script>

<?php includePartial('footer'); ?>