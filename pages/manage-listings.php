<?php
// pages/manage-listings.php - FIXED VERSION
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/auth.php?mode=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get user type from session
$userType = $_SESSION['user_type'] ?? 'buyer';

// Check if user is a seller
if ($userType !== 'seller') {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');

// DEBUG: Show user info
error_log("DEBUG: User ID: $userId, Name: $userName");

// Initialize variables
$message = '';
$error = '';
$listings = [];
$stats = [
    'total_listings' => 0,
    'active_listings' => 0,
    'inactive_listings' => 0,
    'sold_listings' => 0,
    'featured_listings' => 0,
    'avg_price' => 0,
    'total_views' => 0
];
$totalCount = 0;
$totalPages = 1;

// Handle actions
$action = $_GET['action'] ?? '';
$listingId = $_GET['id'] ?? 0;

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // DEBUG: Check what listings exist in database
    $debugAllStmt = $db->query("SELECT COUNT(*) as total FROM listings");
    $allCount = $debugAllStmt->fetch()['total'];
    error_log("DEBUG: Total listings in database: $allCount");
    
    $debugUserStmt = $db->prepare("SELECT COUNT(*) as user_total FROM listings WHERE seller_id = ?");
    $debugUserStmt->execute([$userId]);
    $userCount = $debugUserStmt->fetch()['total'] ?? 0;
    error_log("DEBUG: Listings for user $userId: $userCount");
    
    // Handle actions
    if ($action === 'delete' && $listingId > 0) {
        $checkStmt = $db->prepare("SELECT id, name FROM listings WHERE id = ? AND seller_id = ?");
        $checkStmt->execute([$listingId, $userId]);
        $listing = $checkStmt->fetch();
        
        if ($listing) {
            // Soft delete (set is_active to 0)
            $deleteStmt = $db->prepare("UPDATE listings SET is_active = 0, status = 'inactive' WHERE id = ?");
            if ($deleteStmt->execute([$listingId])) {
                $message = "Listing '{$listing['name']}' deleted successfully!";
            } else {
                $error = 'Failed to delete listing.';
            }
        } else {
            $error = 'Listing not found or you do not have permission.';
        }
    }
    
    // Handle status toggle
    if ($action === 'toggle-status' && $listingId > 0) {
        $checkStmt = $db->prepare("SELECT id, name, status FROM listings WHERE id = ? AND seller_id = ?");
        $checkStmt->execute([$listingId, $userId]);
        $listing = $checkStmt->fetch();
        
        if ($listing) {
            $newStatus = $listing['status'] === 'active' ? 'inactive' : 'active';
            $updateStmt = $db->prepare("UPDATE listings SET status = ?, updated_at = NOW() WHERE id = ?");
            if ($updateStmt->execute([$newStatus, $listingId])) {
                $message = "Listing '{$listing['name']}' status changed to {$newStatus}.";
            } else {
                $error = 'Failed to update listing status.';
            }
        } else {
            $error = 'Listing not found or you do not have permission.';
        }
    }
    
    // Handle featured toggle
    if ($action === 'toggle-featured' && $listingId > 0) {
        $checkStmt = $db->prepare("SELECT id, name, featured FROM listings WHERE id = ? AND seller_id = ?");
        $checkStmt->execute([$listingId, $userId]);
        $listing = $checkStmt->fetch();
        
        if ($listing) {
            $newFeatured = $listing['featured'] ? 0 : 1;
            $updateStmt = $db->prepare("UPDATE listings SET featured = ?, updated_at = NOW() WHERE id = ?");
            if ($updateStmt->execute([$newFeatured, $listingId])) {
                $statusText = $newFeatured ? 'featured' : 'unfeatured';
                $message = "Listing '{$listing['name']}' {$statusText} successfully.";
            } else {
                $error = 'Failed to update featured status.';
            }
        } else {
            $error = 'Listing not found or you do not have permission.';
        }
    }
    
    // Get pagination parameters
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count for THIS USER
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM listings WHERE seller_id = ?");
    $countStmt->execute([$userId]);
    $totalCount = $countStmt->fetch()['total'] ?? 0;
    $totalPages = ceil($totalCount / $limit);
    
    error_log("DEBUG: Total listings for user $userId: $totalCount");
    
    // Get listings with pagination - REMOVE is_active filter to see ALL listings
    $stmt = $db->prepare("
        SELECT 
            l.*,
            c.name as category_name
        FROM listings l
        LEFT JOIN categories c ON l.category_id = c.id
        WHERE l.seller_id = ?
        ORDER BY l.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("DEBUG: Found " . count($listings) . " listings for display");
    
    // Get statistics - FIXED: Ensure all keys exist
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_listings,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_listings,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_listings,
            SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_listings,
            SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_listings,
            COALESCE(AVG(price), 0) as avg_price,
            COALESCE(SUM(views), 0) as total_views
        FROM listings 
        WHERE seller_id = ?
    ");
    $statsStmt->execute([$userId]);
    $fetchedStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Merge fetched stats with defaults
    if ($fetchedStats) {
        $stats = array_merge($stats, $fetchedStats);
    }
    
    // Ensure all values are set and not null
    foreach ($stats as $key => $value) {
        $stats[$key] = $value ?? 0;
    }
    
} catch (PDOException $e) {
    error_log("Manage listings PDO error: " . $e->getMessage());
    $error = "Database error occurred. Please try again.";
} catch (Exception $e) {
    error_log("Manage listings general error: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}

// Set page title
$title = "Manage Listings | PeerCart";

// Include header
include __DIR__ . '/../includes/header.php';
?>

<!-- Include CSS -->
<link rel="stylesheet" href="<?= asset('css/pages/manage-listings.css') ?>">

<div class="manage-listings-container">
    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Debug Info (visible only for testing) -->
    <?php if (isset($_GET['debug'])): ?>
    <div class="debug-info" style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
        <strong>Debug Info:</strong><br>
        User ID: <?= $userId ?><br>
        Total Listings in DB: <?= $allCount ?? 'N/A' ?><br>
        User's Listings Count: <?= $totalCount ?><br>
        Listings Found: <?= count($listings) ?><br>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="manage-header">
        <h1><i class="fas fa-store"></i> Manage Listings</h1>
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/pages/sell.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Listing
            </a>
            <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="stats-cards">
        <div class="stat-card">
            <i class="fas fa-boxes"></i>
            <h3><?= htmlspecialchars((string)$stats['total_listings']) ?></h3>
            <p>Total Listings</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <h3><?= htmlspecialchars((string)$stats['active_listings']) ?></h3>
            <p>Active Listings</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-pause-circle"></i>
            <h3><?= htmlspecialchars((string)$stats['inactive_listings']) ?></h3>
            <p>Inactive Listings</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-star"></i>
            <h3><?= htmlspecialchars((string)$stats['featured_listings']) ?></h3>
            <p>Featured Listings</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-money-bill-wave"></i>
            <h3>R<?= number_format((float)$stats['avg_price'], 2) ?></h3>
            <p>Average Price</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-eye"></i>
            <h3><?= htmlspecialchars((string)$stats['total_views']) ?></h3>
            <p>Total Views</p>
        </div>
    </div>
    
    <!-- Listings Table -->
    <div class="listings-table-container">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> Your Listings (<?= $totalCount ?> total)</h3>
            <div class="table-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search listings..." id="searchInput">
                </div>
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="sold">Sold</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
        </div>
        
        <?php if (!empty($listings)): ?>
        <!-- Bulk Actions Form -->
        <form method="POST" action="" id="bulkActionForm" class="bulk-actions">
            <select name="bulk_action" id="bulkAction">
                <option value="">Bulk Actions</option>
                <option value="activate">Activate Selected</option>
                <option value="deactivate">Deactivate Selected</option>
                <option value="feature">Feature Selected</option>
                <option value="unfeature">Unfeature Selected</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button type="submit" name="apply_bulk_action" id="applyBulkAction" class="btn-primary">
                Apply
            </button>
        </form>
        <?php endif; ?>
        
        <div class="table-responsive">
            <?php if (!empty($listings)): ?>
                <table class="listings-table">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Listing</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th>Featured</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): 
                            $imageSrc = !empty($listing['image']) 
                                ? getListingImage($listing['image'])
                                : asset('images/products/default-product.png');
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_listings[]" value="<?= $listing['id'] ?>" class="listing-checkbox">
                            </td>
                            <td>
                                <div class="listing-info-cell">
                                    <div class="listing-image">
                                        <img src="<?= $imageSrc ?>" 
                                             alt="<?= htmlspecialchars($listing['name']) ?>"
                                             onerror="this.src='<?= asset('images/products/default-product.png') ?>'">
                                    </div>
                                    <div class="listing-details">
                                        <h4><?= htmlspecialchars(substr($listing['name'], 0, 50)) ?></h4>
                                        <p>ID: <?= $listing['id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($listing['category_name'] ?? 'Uncategorized') ?></td>
                            <td class="price-cell">R<?= number_format($listing['price'], 2) ?></td>
                            <td class="stock-cell <?= $listing['quantity'] < 10 ? 'stock-low' : 'stock-good' ?>">
                                <?= $listing['quantity'] ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $listing['status'] ?>">
                                    <?= ucfirst($listing['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $listing['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $listing['is_active'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($listing['featured']): ?>
                                    <span class="featured-badge">Yes</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">No</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($listing['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= BASE_URL ?>/pages/listing.php?id=<?= $listing['id'] ?>" 
                                       class="btn-action btn-view" title="View Listing" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/sell.php?edit=<?= $listing['id'] ?>" 
                                       class="btn-action btn-edit" title="Edit Listing">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($listing['is_active']): ?>
                                    <a href="<?= BASE_URL ?>/pages/manage-listings.php?action=toggle-status&id=<?= $listing['id'] ?>&page=<?= $page ?>" 
                                       class="btn-action btn-toggle" 
                                       title="<?= $listing['status'] === 'active' ? 'Deactivate' : 'Activate' ?>"
                                       onclick="return confirm('Are you sure you want to <?= $listing['status'] === 'active' ? 'deactivate' : 'activate' ?> this listing?')">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/manage-listings.php?action=toggle-featured&id=<?= $listing['id'] ?>&page=<?= $page ?>" 
                                       class="btn-action btn-featured" 
                                       title="<?= $listing['featured'] ? 'Remove Feature' : 'Make Featured' ?>"
                                       onclick="return confirm('Are you sure you want to <?= $listing['featured'] ? 'remove featured status from' : 'feature' ?> this listing?')">
                                        <i class="fas fa-star"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/manage-listings.php?action=delete&id=<?= $listing['id'] ?>&page=<?= $page ?>" 
                                       class="btn-action btn-delete" 
                                       title="Delete Listing"
                                       onclick="return confirm('Are you sure you want to delete this listing? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span style="color: #999; font-size: 0.8rem;">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open fa-3x"></i>
                    <h3>No listings found</h3>
                    <p>
                        <?php if ($totalCount > 0): ?>
                            You have <?= $totalCount ?> listings but none are active or visible.
                        <?php else: ?>
                            You haven't created any listings yet.
                        <?php endif; ?>
                    </p>
                    <p><small>User ID: <?= $userId ?></small></p>
                    <a href="<?= BASE_URL ?>/pages/sell.php" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Create Your First Listing
                    </a>
                    <br><br>
                    <a href="<?= BASE_URL ?>/pages/manage-listings.php?debug=1" class="btn-outline" style="font-size: 0.9rem;">
                        <i class="fas fa-bug"></i> Show Debug Info
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= BASE_URL ?>/pages/manage-listings.php?page=<?= $page - 1 ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i> Previous</span>
            <?php endif; ?>
            
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1): ?>
                <a href="<?= BASE_URL ?>/pages/manage-listings.php?page=1">1</a>
                <?php if ($startPage > 2): ?>
                    <span class="disabled">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/pages/manage-listings.php?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span class="disabled">...</span>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pages/manage-listings.php?page=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?= BASE_URL ?>/pages/manage-listings.php?page=<?= $page + 1 ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Confirmation Modal -->
<div class="confirmation-modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this listing? This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-confirm" id="confirmDelete">Delete</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    function filterListings() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const rows = document.querySelectorAll('.listings-table tbody tr');
        
        rows.forEach(row => {
            const listingName = row.querySelector('.listing-details h4').textContent.toLowerCase();
            const statusBadge = row.querySelector('.status-badge.status-active, .status-badge.status-inactive, .status-badge.status-sold, .status-badge.status-draft');
            const status = statusBadge ? statusBadge.textContent.toLowerCase() : '';
            
            const matchesSearch = listingName.includes(searchTerm);
            const matchesStatus = !statusValue || status.includes(statusValue);
            
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterListings);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', filterListings);
    }
    
    // Select All checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    const listingCheckboxes = document.querySelectorAll('.listing-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            listingCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all when individual checkboxes change
        listingCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(listingCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = !allChecked && Array.from(listingCheckboxes).some(cb => cb.checked);
            });
        });
    }
    
    // Bulk action form
    const bulkActionForm = document.getElementById('bulkActionForm');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const bulkAction = document.getElementById('bulkAction').value;
            const selectedCheckboxes = document.querySelectorAll('.listing-checkbox:checked');
            
            if (!bulkAction) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return;
            }
            
            if (selectedCheckboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one listing.');
                return;
            }
            
            if (bulkAction === 'delete') {
                e.preventDefault();
                if (confirm(`Are you sure you want to delete ${selectedCheckboxes.length} listing(s)? This action cannot be undone.`)) {
                    // Add selected IDs to form
                    selectedCheckboxes.forEach(checkbox => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_listings[]';
                        input.value = checkbox.value;
                        bulkActionForm.appendChild(input);
                    });
                    
                    // Show loading
                    document.getElementById('loadingOverlay').classList.add('active');
                    
                    // Submit form
                    setTimeout(() => {
                        bulkActionForm.submit();
                    }, 500);
                }
            } else {
                // For other bulk actions, show confirmation
                const actionText = bulkAction === 'activate' ? 'activate' :
                                  bulkAction === 'deactivate' ? 'deactivate' :
                                  bulkAction === 'feature' ? 'feature' :
                                  'unfeature';
                
                if (!confirm(`Are you sure you want to ${actionText} ${selectedCheckboxes.length} listing(s)?`)) {
                    e.preventDefault();
                    return;
                }
                
                // Add selected IDs to form
                selectedCheckboxes.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_listings[]';
                    input.value = checkbox.value;
                    bulkActionForm.appendChild(input);
                });
                
                // Show loading
                document.getElementById('loadingOverlay').classList.add('active');
            }
        });
    }
    
    // Quick status filters
    const quickFilters = document.createElement('div');
    quickFilters.className = 'quick-filters';
    quickFilters.style.marginTop = '1rem';
    quickFilters.innerHTML = `
        <span style="margin-right: 10px; color: #666;">Quick Filters:</span>
        <button class="filter-btn" data-status="active" style="padding: 5px 10px; margin: 0 5px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer;">Active</button>
        <button class="filter-btn" data-status="inactive" style="padding: 5px 10px; margin: 0 5px; background: #f39c12; color: white; border: none; border-radius: 4px; cursor: pointer;">Inactive</button>
        <button class="filter-btn" data-status="featured" style="padding: 5px 10px; margin: 0 5px; background: #9b59b6; color: white; border: none; border-radius: 4px; cursor: pointer;">Featured</button>
        <button class="filter-btn" data-status="" style="padding: 5px 10px; margin: 0 5px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">Show All</button>
    `;
    
    // Add quick filters only if there are listings
    const tableHeader = document.querySelector('.table-header');
    if (tableHeader && <?= !empty($listings) ? 'true' : 'false' ?>) {
        tableHeader.appendChild(quickFilters);
        
        // Quick filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.getAttribute('data-status');
                if (status === 'featured') {
                    // Special handling for featured (not a status)
                    alert('Featured filter would show only featured listings');
                } else if (statusFilter) {
                    statusFilter.value = status;
                    filterListings();
                }
            });
        });
    }
    
    // Auto-refresh after actions (if there's a message)
    <?php if (!empty($message) || !empty($error)): ?>
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    <?php endif; ?>
    
    // Update page title with count
    const listingCount = <?= count($listings) ?>;
    if (listingCount > 0) {
        document.title = `(${listingCount}) ${document.title}`;
    }
});
</script>

<?php
// Include footer
include __DIR__ . '/../includes/footer.php';
?>