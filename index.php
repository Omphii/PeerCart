<?php
// Root index.php for InfinityFree
require_once 'includes/bootstrap.php';
require_once 'includes/functions.php';

// Redirect to home page
header('Location: ' . url('pages/home.php'));
exit;
?>