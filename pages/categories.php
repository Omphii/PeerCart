<?php
require_once __DIR__.'/../includes/bootstrap.php';
includePartial('header');
?>
<div class="container mt-4">
    <h1>Browse Categories</h1>
    <div class="row">
        <?php
        $categories = [
            ['id' => 1, 'name' => 'Electronics', 'icon' => 'fa-laptop'],
            ['id' => 2, 'name' => 'Fashion', 'icon' => 'fa-tshirt'],
            ['id' => 3, 'name' => 'Home & Garden', 'icon' => 'fa-home'],
            ['id' => 4, 'name' => 'Books', 'icon' => 'fa-book'],
            ['id' => 5, 'name' => 'Sports', 'icon' => 'fa-running']
        ];
        
        foreach ($categories as $category): ?>
            <div class="col-md-4 mb-4">
                <a href="<?= BASE_PATH ?>category/<?= $category['id'] ?>" class="card category-card">
                    <div class="card-body text-center">
                        <i class="fas <?= $category['icon'] ?> fa-3x mb-3"></i>
                        <h3><?= $category['name'] ?></h3>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php includePartial('footer'); ?>