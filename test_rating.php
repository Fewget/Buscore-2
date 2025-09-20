<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Rating Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rating-display {
            margin: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Test Rating Display</h1>
        
        <div class="rating-display">
            <h3>Test Rating (4.7/5)</h3>
            <div class="d-flex align-items-center">
                <div class="d-flex align-items-center">
                    <span class="fw-bold fs-4 me-1">4.7</span>
                    <span class="text-muted fs-5">/ 5</span>
                    <div class="ms-2 d-flex">
                        <i class="fas fa-star text-warning" style="font-size: 1.5rem;"></i>
                        <i class="fas fa-star text-warning" style="font-size: 1.5rem;"></i>
                        <i class="fas fa-star text-warning" style="font-size: 1.5rem;"></i>
                        <i class="fas fa-star text-warning" style="font-size: 1.5rem;"></i>
                        <i class="fas fa-star-half-alt text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <span class="text-muted ms-3">(42 ratings)</span>
            </div>
        </div>
    </div>
</body>
</html>
