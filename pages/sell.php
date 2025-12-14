<?php
// sell.php - Add debugging at the top
error_log("=== SELL.PHP DEBUG ===");
error_log("Session ID before require: " . session_id());

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: ' . url('login'));
    exit;
}

// Get categories
$categories = getCategories();

// Generate CSRF token with debugging
error_log("Generating CSRF token...");
$csrfToken = generateCSRFToken();
error_log("CSRF Token generated: " . substr($csrfToken, 0, 20) . '...');
error_log("Session csrf_tokens after generation: " . print_r($_SESSION['csrf_tokens'] ?? 'No tokens', true));

require_once __DIR__ . '/../includes/header.php';
?>
<div class="sell-container mt-2">
    <h1>Sell an Item</h1>
    
    <div id="form-messages"></div>

    <form id="listing-form" method="post" action="<?= url('controllers/submit-listing.php') ?>" enctype="multipart/form-data" class="sell-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="debug_csrf_prefix" value="<?= substr($csrfToken, 0, 10) ?>">
    
        <div class="form-grid">
            <!-- Item Name -->
            <div class="mb-3 full-width">
                <label for="title" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required maxlength="100">
            </div>

            <!-- Price -->
            <div class="mb-3">
                <label for="price" class="form-label">Price (R)</label>
                <input type="number" class="form-control" id="price" name="price" 
                       step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            </div>

            <!-- Original Price -->
            <div class="mb-3">
                <label for="original_price" class="form-label">Original Price (Optional)</label>
                <input type="number" class="form-control" id="original_price" name="original_price" 
                       step="0.01" min="0" value="<?= htmlspecialchars($_POST['original_price'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div class="mb-3 full-width">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Category -->
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Quantity -->
            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="<?= htmlspecialchars($_POST['quantity'] ?? '1') ?>" required>
            </div>

            <!-- Condition -->
            <div class="mb-3">
                <label for="item_condition" class="form-label">Condition</label>
                <select class="form-select" id="item_condition" name="item_condition" required>
                    <option value="">Select condition</option>
                    <option value="new" <?= ($_POST['item_condition'] ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                    <option value="used_like_new" <?= ($_POST['item_condition'] ?? '') == 'used_like_new' ? 'selected' : '' ?>>Used - Like New</option>
                    <option value="used_good" <?= ($_POST['item_condition'] ?? '') == 'used_good' ? 'selected' : '' ?>>Used - Good</option>
                    <option value="used_fair" <?= ($_POST['item_condition'] ?? '') == 'used_fair' ? 'selected' : '' ?>>Used - Fair</option>
                </select>
            </div>

            <!-- Province -->
            <div class="mb-3">
                <label for="province" class="form-label">Province</label>
                <select class="form-select" id="province" name="province" required>
                    <option value="">Select your province</option>
                    <?php 
                    $provinces = ['EC'=>'Eastern Cape','FS'=>'Free State','GP'=>'Gauteng','KZN'=>'KwaZulu-Natal','LP'=>'Limpopo','MP'=>'Mpumalanga','NC'=>'Northern Cape','NW'=>'North West','WC'=>'Western Cape'];
                    foreach ($provinces as $code => $name): ?>
                        <option value="<?= $code ?>" <?= ($_POST['province'] ?? '') == $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Images -->
            <div class="mb-3 full-width">
                <label for="images" class="form-label">Upload Images (Max 5)</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                <div id="image-preview" class="mt-2"></div>
            </div>

            <!-- Featured -->
            <div class="mb-3 full-width">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" <?= ($_POST['featured'] ?? '0') == '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="featured">Feature this listing (may require additional fee)</label>
                </div>
            </div>

            <!-- Submit -->
            <div class="mb-3 full-width">
                <button type="submit" class="btn btn-primary">List Item</button>
            </div>
        </div>
    </form>
</div>

<script>
// Image preview
document.getElementById('images').addEventListener('change', function() {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    const files = this.files;
    if(files.length > 5){
        alert('You can only upload a maximum of 5 images.');
        this.value = '';
        return;
    }
    Array.from(files).forEach(file => {
        if(!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.height = '100px';
            img.style.marginRight = '10px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});

// AJAX submit
document.getElementById('listing-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const messages = document.getElementById('form-messages');
    messages.innerHTML = '';

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if(result.success){
            messages.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
            setTimeout(() => window.location.href = result.redirect, 2000);
        } else {
            messages.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (err) {
        messages.innerHTML = `<div class="alert alert-danger">An unexpected error occurred</div>`;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
