<?php
// sell.php - Clean compact version
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/auth.php?mode=login&redirect=' . urlencode('/pages/sell.php'));
    exit;
}

// Check user type - seller, admin, or buyer who wants to sell
$user_id = $_SESSION['user_id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Get user type and listing count
$stmt = $db->prepare("
    SELECT u.user_type, COUNT(l.id) as listing_count 
    FROM users u 
    LEFT JOIN listings l ON u.id = l.seller_id AND l.is_active = 1
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$user_type = $user_data['user_type'] ?? 'buyer';
$listing_count = $user_data['listing_count'] ?? 0;

// Get categories
$categories = getCategories();

// Generate CSRF token
$csrfToken = generateCSRFToken('listing_submit');

// Handle form submission if POST request (for fallback)
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data for re-population
    $form_data = $_POST;
}

// Include header
$title = 'Sell an Item - PeerCart';
$currentPage = 'sell';
include(__DIR__ . '/../includes/header.php');
?>

<link rel="stylesheet" href="<?= asset('css/pages/sell.css') ?>?v=<?= time() ?>">

<div class="sell-page">
    <div class="sell-container compact">
        <h1>Sell an Item</h1>
        
        <!-- User type indicator -->
        <?php if ($user_type !== 'buyer'): ?>
            <div class="alert alert-info compact">
                <i class="fas fa-user-tie me-1"></i> 
                You are registered as a <strong><?= htmlspecialchars(ucfirst($user_type)) ?></strong>.
            </div>
        <?php endif; ?>
        
        <!-- Listing count info -->
        <?php if ($listing_count > 0): ?>
            <div class="alert alert-info compact">
                <i class="fas fa-chart-line me-1"></i> 
                You have listed <?= $listing_count ?> item<?= $listing_count > 1 ? 's' : '' ?> before.
            </div>
        <?php elseif ($user_type === 'buyer'): ?>
            <div class="alert alert-warning compact">
                <i class="fas fa-info-circle me-1"></i> 
                You are currently listed as a <strong>Buyer</strong>. 
                <a href="<?= BASE_URL ?>/pages/settings.php?tab=account" class="alert-link">
                    Consider upgrading to Seller
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-success compact">
                <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_SESSION['flash_message']['text'] ?? '') ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/controllers/submit-listing.php" enctype="multipart/form-data" class="sell-form" id="listingForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-grid">
                <!-- Item Name -->
                <div class="form-group full-width">
                    <label for="title" class="form-label required compact">
                        <i class="fas fa-tag"></i> Item Name
                    </label>
                    <input type="text" class="form-control compact" id="title" name="title" 
                           value="<?= htmlspecialchars($form_data['title'] ?? '') ?>" 
                           required maxlength="100" 
                           placeholder="Enter item name">
                    <div class="validation-error compact" id="title_error"></div>
                </div>

                <!-- Price -->
                <div class="form-group">
                    <label for="price" class="form-label required compact">
                        <i class="fas fa-money-bill-wave"></i> Price (R)
                    </label>
                    <div class="input-group compact">
                        <span class="input-group-text compact">R</span>
                        <input type="number" class="form-control compact" id="price" name="price" 
                               step="0.01" min="0.01" max="999999.99" 
                               value="<?= htmlspecialchars($form_data['price'] ?? '') ?>" 
                               required placeholder="0.00">
                    </div>
                    <div class="validation-error compact" id="price_error"></div>
                </div>

                <!-- Original Price -->
                <div class="form-group">
                    <label for="original_price" class="form-label compact">
                        <i class="fas fa-tag"></i> Original Price (Optional)
                    </label>
                    <div class="input-group compact">
                        <span class="input-group-text compact">R</span>
                        <input type="number" class="form-control compact" id="original_price" name="original_price" 
                               step="0.01" min="0" max="999999.99" 
                               value="<?= htmlspecialchars($form_data['original_price'] ?? '') ?>" 
                               placeholder="Original price">
                    </div>
                    <div class="text-muted compact">
                        <i class="fas fa-info-circle"></i> Display original price to show savings
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group full-width">
                    <label for="description" class="form-label compact">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea class="form-control compact" id="description" name="description" 
                              rows="3" maxlength="2000"
                              placeholder="Describe your item in detail"><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                    <div class="text-muted compact">
                        <span id="charCount">0</span> / 2000 characters
                    </div>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category_id" class="form-label required compact">
                        <i class="fas fa-folder"></i> Category
                    </label>
                    <select class="form-select compact" id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                <?= ($form_data['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="validation-error compact" id="category_id_error"></div>
                </div>

                <!-- Quantity -->
                <div class="form-group">
                    <label for="quantity" class="form-label required compact">
                        <i class="fas fa-box"></i> Quantity
                    </label>
                    <input type="number" class="form-control compact" id="quantity" name="quantity" 
                           min="1" max="999" 
                           value="<?= htmlspecialchars($form_data['quantity'] ?? '1') ?>" 
                           required>
                    <div class="validation-error compact" id="quantity_error"></div>
                </div>

                <!-- Condition -->
                <div class="form-group full-width">
                    <label class="form-label required compact">
                        <i class="fas fa-star"></i> Condition
                    </label>
                    <div class="condition-badges compact" id="condition-container">
                        <div class="condition-badge-option compact" 
                             data-value="new">
                            <i class="fas fa-gem"></i>
                            <span>New</span>
                            <small>Unused, original packaging</small>
                        </div>
                        <div class="condition-badge-option compact" 
                             data-value="used_like_new">
                            <i class="fas fa-thumbs-up"></i>
                            <span>Used - Like New</span>
                            <small>Minimal signs of use</small>
                        </div>
                        <div class="condition-badge-option compact" 
                             data-value="used_good">
                            <i class="fas fa-check-circle"></i>
                            <span>Used - Good</span>
                            <small>Normal wear, works perfectly</small>
                        </div>
                        <div class="condition-badge-option compact" 
                             data-value="used_fair">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Used - Fair</span>
                            <small>Visible wear, may need repair</small>
                        </div>
                    </div>
                    <input type="hidden" id="item_condition" name="item_condition" 
                           value="<?= htmlspecialchars($form_data['item_condition'] ?? '') ?>" required>
                    <div class="validation-error compact" id="item_condition_error"></div>
                </div>

                <!-- Province -->
                <div class="form-group">
                    <label for="province" class="form-label required compact">
                        <i class="fas fa-map-marker-alt"></i> Province
                    </label>
                    <select class="form-select compact" id="province" name="province" required>
                        <option value="">Select your province</option>
                        <?php 
                        $provinces = [
                            'EC' => 'Eastern Cape',
                            'FS' => 'Free State', 
                            'GP' => 'Gauteng',
                            'KZN' => 'KwaZulu-Natal',
                            'LP' => 'Limpopo',
                            'MP' => 'Mpumalanga',
                            'NC' => 'Northern Cape',
                            'NW' => 'North West',
                            'WC' => 'Western Cape'
                        ];
                        foreach ($provinces as $code => $name): ?>
                            <option value="<?= $code ?>" 
                                <?= ($form_data['province'] ?? '') == $code ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="validation-error compact" id="province_error"></div>
                </div>

                <!-- Images -->
                <div class="form-group full-width">
                    <label for="images" class="form-label compact">
                        <i class="fas fa-images"></i> Upload Images (Max 5)
                    </label>
                    <input type="file" class="form-control compact" id="images" name="images[]" 
                           multiple accept="image/*" 
                           onchange="previewImages(this)">
                    <div class="text-muted compact">
                        <i class="fas fa-info-circle"></i> First image will be the main listing image
                    </div>
                    <div id="image-preview" class="image-preview-container compact mt-2"></div>
                </div>

                <!-- Featured -->
                <div class="form-group full-width">
                    <div class="checkbox-group compact">
                        <input type="checkbox" id="featured" name="featured" value="1" 
                               <?= ($form_data['featured'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label for="featured" class="compact">
                            <i class="fas fa-crown"></i> Feature this listing
                            <span class="text-muted compact">(Get more visibility for a small fee)</span>
                        </label>
                    </div>
                </div>

                <!-- Terms -->
                <div class="form-group full-width">
                    <div class="checkbox-group compact">
                        <input type="checkbox" id="terms" name="terms" value="1" required>
                        <label for="terms" class="required compact">
                            <i class="fas fa-file-contract"></i> I agree to the 
                            <a href="<?= BASE_URL ?>/pages/terms.php" target="_blank">Terms of Service</a>
                        </label>
                    </div>
                    <div class="validation-error compact" id="terms_error"></div>
                </div>

                <!-- Submit -->
                <div class="form-group full-width">
                    <button type="submit" class="btn-primary compact" id="submitBtn">
                        <i class="fas fa-plus-circle"></i> List Item
                    </button>
                    <div class="text-center mt-2">
                        <a href="<?= BASE_URL ?>/" class="btn-text compact">
                            <i class="fas fa-arrow-left"></i> Cancel and return to home
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Condition selection function
function selectCondition(element, value) {
    // Remove selected class from all condition badges
    document.querySelectorAll('.condition-badge-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected class to clicked element
    element.classList.add('selected');
    
    // Update hidden input
    const conditionInput = document.getElementById('item_condition');
    if (conditionInput) {
        conditionInput.value = value;
    }
    
    // Clear any validation error
    const errorElement = document.getElementById('item_condition_error');
    if (errorElement) {
        errorElement.style.display = 'none';
        errorElement.textContent = '';
    }
}

// File preview function
function previewImages(input) {
    const preview = document.getElementById('image-preview');
    if (!preview) return;
    
    preview.innerHTML = '';
    
    const files = input.files;
    const maxFiles = 5;
    
    if (files.length > maxFiles) {
        alert(`You can only select up to ${maxFiles} images.`);
        input.value = '';
        return;
    }
    
    for (let i = 0; i < Math.min(files.length, maxFiles); i++) {
        const file = files[i];
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert(`File "${file.name}" is not an image.`);
            continue;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(`Image "${file.name}" is too large. Maximum size is 5MB.`);
            continue;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const imageContainer = document.createElement('div');
            imageContainer.className = 'image-preview compact';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Preview';
            
            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-image compact';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.title = 'Remove image';
            
            removeBtn.addEventListener('click', function() {
                imageContainer.remove();
                updateFileList(input, file);
            });
            
            imageContainer.appendChild(img);
            imageContainer.appendChild(removeBtn);
            preview.appendChild(imageContainer);
        };
        
        reader.readAsDataURL(file);
    }
}

function updateFileList(input, fileToRemove) {
    const dataTransfer = new DataTransfer();
    const files = Array.from(input.files);
    
    for (const file of files) {
        if (file !== fileToRemove) {
            dataTransfer.items.add(file);
        }
    }
    
    input.files = dataTransfer.files;
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('listingForm');
    if (!form) return;
    
    const submitBtn = document.getElementById('submitBtn');
    const description = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const conditionInput = document.getElementById('item_condition');
    
    // Character counter for description
    if (description && charCount) {
        charCount.textContent = description.value.length;
        description.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    // Initialize condition selection
    const initialCondition = conditionInput.value;
    if (initialCondition) {
        const initialBadge = document.querySelector(`.condition-badge-option[data-value="${initialCondition}"]`);
        if (initialBadge) {
            initialBadge.classList.add('selected');
        }
    }
    
    // Make condition badges clickable
    document.querySelectorAll('.condition-badge-option').forEach(option => {
        option.style.cursor = 'pointer';
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.getAttribute('data-value');
            selectCondition(this, value);
        });
    });
    
    // Form validation and AJAX submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Clear previous errors
        document.querySelectorAll('.validation-error').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });
        
        // Validate required fields
        const requiredFields = [
            { id: 'title', name: 'Item Name' },
            { id: 'price', name: 'Price' },
            { id: 'category_id', name: 'Category' },
            { id: 'quantity', name: 'Quantity' },
            { id: 'item_condition', name: 'Condition' },
            { id: 'province', name: 'Province' },
            { id: 'terms', name: 'Terms and Conditions' }
        ];
        
        requiredFields.forEach(field => {
            const element = document.getElementById(field.id);
            const errorElement = document.getElementById(field.id + '_error');
            
            if (!element) return;
            
            if (element.type === 'checkbox') {
                if (!element.checked) {
                    isValid = false;
                    if (errorElement) {
                        errorElement.style.display = 'block';
                        errorElement.textContent = `You must accept the ${field.name}`;
                        element.classList.add('invalid');
                    }
                }
            } else if (element.value.trim() === '') {
                isValid = false;
                if (errorElement) {
                    errorElement.style.display = 'block';
                    errorElement.textContent = `${field.name} is required`;
                    element.classList.add('invalid');
                }
            } else {
                element.classList.remove('invalid');
            }
        });
        
        // Validate price
        const priceInput = document.getElementById('price');
        if (priceInput && priceInput.value) {
            const price = parseFloat(priceInput.value);
            if (price <= 0 || price > 999999.99) {
                isValid = false;
                const errorElement = document.getElementById('price_error');
                if (errorElement) {
                    errorElement.style.display = 'block';
                    errorElement.textContent = 'Price must be between R0.01 and R999,999.99';
                    priceInput.classList.add('invalid');
                }
            }
        }
        
        // Validate quantity
        const quantityInput = document.getElementById('quantity');
        if (quantityInput && quantityInput.value) {
            const quantity = parseInt(quantityInput.value);
            if (quantity < 1 || quantity > 999) {
                isValid = false;
                const errorElement = document.getElementById('quantity_error');
                if (errorElement) {
                    errorElement.style.display = 'block';
                    errorElement.textContent = 'Quantity must be between 1 and 999';
                    quantityInput.classList.add('invalid');
                }
            }
        }
        
        // Validate images (max 5)
        const imageInput = document.getElementById('images');
        if (imageInput && imageInput.files.length > 5) {
            isValid = false;
            alert('You can only upload a maximum of 5 images.');
            imageInput.value = '';
            previewImages(imageInput);
        }
        
        if (!isValid) {
            const firstError = form.querySelector('.invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        
        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Listing...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(this);
            
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message
                const messagesDiv = document.createElement('div');
                messagesDiv.className = 'alert alert-success compact';
                messagesDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${result.message}`;
                
                const existingMessages = document.querySelector('.sell-container .alert');
                const h1 = document.querySelector('.sell-container h1');
                
                if (existingMessages) {
                    existingMessages.replaceWith(messagesDiv);
                } else if (h1) {
                    h1.after(messagesDiv);
                } else {
                    document.querySelector('.sell-container').prepend(messagesDiv);
                }
                
                // Clear form
                form.reset();
                const imagePreview = document.getElementById('image-preview');
                if (imagePreview) {
                    imagePreview.innerHTML = '';
                }
                
                if (charCount) {
                    charCount.textContent = '0';
                }
                
                // Remove selected condition
                document.querySelectorAll('.condition-badge-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                if (conditionInput) {
                    conditionInput.value = '';
                }
                
                // Clear localStorage
                localStorage.removeItem('listing_form_data');
                
                // Redirect after 2 seconds
                setTimeout(() => {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.href = '<?= BASE_URL ?>/pages/dashboard.php?tab=listings';
                    }
                }, 2000);
            } else {
                // Show error message
                const messagesDiv = document.createElement('div');
                messagesDiv.className = 'alert alert-danger compact';
                messagesDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${result.message || 'An error occurred'}`;
                
                const existingMessages = document.querySelector('.sell-container .alert');
                const h1 = document.querySelector('.sell-container h1');
                
                if (existingMessages) {
                    existingMessages.replaceWith(messagesDiv);
                } else if (h1) {
                    h1.after(messagesDiv);
                } else {
                    document.querySelector('.sell-container').prepend(messagesDiv);
                }
                
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } catch (error) {
            // Show error message
            const messagesDiv = document.createElement('div');
            messagesDiv.className = 'alert alert-danger compact';
            messagesDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i> 
                Submission failed. Please try again.
            `;
            
            const existingMessages = document.querySelector('.sell-container .alert');
            const h1 = document.querySelector('.sell-container h1');
            
            if (existingMessages) {
                existingMessages.replaceWith(messagesDiv);
            } else if (h1) {
                h1.after(messagesDiv);
            } else {
                document.querySelector('.sell-container').prepend(messagesDiv);
            }
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    
    // Real-time validation
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', function() {
            if (this.classList.contains('invalid')) {
                this.classList.remove('invalid');
                const errorElement = document.getElementById(this.id + '_error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                    errorElement.textContent = '';
                }
            }
        });
        
        input.addEventListener('change', function() {
            if (this.classList.contains('invalid')) {
                this.classList.remove('invalid');
                const errorElement = document.getElementById(this.id + '_error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                    errorElement.textContent = '';
                }
            }
        });
    });
    
    // Auto-save form data to localStorage
    const formKey = 'listing_form_data';
    
    const savedData = localStorage.getItem(formKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const element = form.querySelector(`[name="${key}"]`);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = data[key] === '1' || data[key] === true;
                    } else if (element.type === 'select-one') {
                        element.value = data[key];
                    } else {
                        element.value = data[key];
                    }
                }
            });
            
            if (data.item_condition) {
                const conditionBadge = document.querySelector(`.condition-badge-option[data-value="${data.item_condition}"]`);
                if (conditionBadge) {
                    conditionBadge.classList.add('selected');
                    conditionInput.value = data.item_condition;
                }
            }
            
            if (data.description) {
                const charCount = document.getElementById('charCount');
                if (charCount) {
                    charCount.textContent = data.description.length;
                }
            }
        } catch (e) {
            console.error('Error loading saved form data:', e);
        }
    }
    
    // Save data on input
    form.addEventListener('input', function() {
        const formData = {};
        new FormData(form).forEach((value, key) => {
            formData[key] = value;
        });
        localStorage.setItem(formKey, JSON.stringify(formData));
    });
    
    // Clear saved data on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem(formKey);
    });
});
</script>

<?php 
include(__DIR__ . '/../includes/footer.php');
?>