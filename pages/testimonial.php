<?php
// testimonials.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '/../includes/header.php'; // optional, if you have a header file
?>

<div class="container my-5">
    <h1 class="text-center mb-4">What Our Customers Say</h1>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm p-3 mb-4">
                <p>"This platform made buying and selling so easy. Highly recommend!"</p>
                <h6 class="text-muted">– John D.</h6>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3 mb-4">
                <p>"Amazing customer service and smooth transactions."</p>
                <h6 class="text-muted">– Sarah W.</h6>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3 mb-4">
                <p>"PeerCart helped me sell my items quickly and safely."</p>
                <h6 class="text-muted">– Michael T.</h6>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
