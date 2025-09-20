<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$message = $_GET['message'] ?? 'Operation completed successfully';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Success!
                    </h2>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="mb-4"><?php echo htmlspecialchars($message); ?></h3>
                    <p class="text-muted mb-4">Thank you for your submission.</p>
                    <div class="mt-4">
                        <a href="add-bus.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-1"></i> Add Another Bus
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
