<?php
// test-css.php
require_once __DIR__ . '/../includes/bootstrap.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test CSS Path</title>
    <style>
        .test-box {
            width: 200px;
            height: 100px;
            background: red;
            color: white;
            padding: 20px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h1>Testing CSS Paths</h1>
    
    <div class="test-box">
        This should be a red box (inline CSS test)
    </div>
    
    <?php
    // Test the asset() function
    $cssPath = asset('css/pages/manage-listings.css');
    echo "<p>CSS Path from asset(): <code>$cssPath</code></p>";
    
    // Test direct path
    $directPath = BASE_URL . '/assets/css/pages/manage-listings.css';
    echo "<p>Direct Path: <code>$directPath</code></p>";
    
    // Check if file exists on server
    $serverPath = __DIR__ . '/assets/css/pages/manage-listings.css';
    echo "<p>Server Path: <code>$serverPath</code></p>";
    
    if (file_exists($serverPath)) {
        echo "<p style='color: green;'>✅ CSS file exists on server</p>";
        echo "<p>File size: " . filesize($serverPath) . " bytes</p>";
    } else {
        echo "<p style='color: red;'>❌ CSS file NOT FOUND on server</p>";
        
        // Check parent directories
        echo "<h3>Checking parent directories:</h3>";
        $possiblePaths = [
            __DIR__ . '/assets/css/pages/manage-listings.css',
            __DIR__ . '/../assets/css/pages/manage-listings.css',
            __DIR__ . '/../../assets/css/pages/manage-listings.css',
            __DIR__ . '/../../../assets/css/pages/manage-listings.css',
            realpath(__DIR__ . '/assets/css/pages/manage-listings.css'),
            realpath(__DIR__ . '/../assets/css/pages/manage-listings.css'),
        ];
        
        foreach ($possiblePaths as $path) {
            echo "<p>Checking: <code>$path</code> - ";
            if (file_exists($path)) {
                echo "<span style='color: green;'>✅ FOUND</span></p>";
                break;
            } else {
                echo "<span style='color: red;'>❌ NOT FOUND</span></p>";
            }
        }
    }
    ?>
    
    <!-- Test loading the CSS -->
    <link rel="stylesheet" href="<?= $cssPath ?>">
    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
        <h3>Testing loaded CSS:</h3>
        <div class="manage-listings-container" style="border: 2px solid blue;">
            This should be styled if CSS loads
        </div>
    </div>
    
    <!-- Test with version parameter -->
    <link rel="stylesheet" href="<?= $cssPath ?>?v=<?= time() ?>">
</body>
</html>