<?php
// Start session at the very beginning
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$success = false;
$error = '';
$bus = null;
$busId = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;
$registrationNumber = isset($_GET['registration_number']) ? trim($_GET['registration_number']) : '';

// Get user ID from session if logged in
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// Initialize bus_id
$bus_id = $busId;  // Use the sanitized busId as bus_id

// Get bus details if bus_id is provided
if ($busId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        $error = 'Bus not found. You can still submit a rating for this bus.';
    }
} 
// If registration number is provided but no bus found, pre-fill the form
elseif (!empty($registrationNumber)) {
    // Format and validate registration number
    $formattedReg = format_registration_number($registrationNumber);
    if ($formattedReg === false) {
        $error = 'Invalid registration number format. Please use format like NA-1234 or 62-1234';
    } else {
        $bus = [
            'registration_number' => $formattedReg,
            'route_number' => '',
            'bus_name' => '',
            'company_name' => '',
            'type' => 'private' // Default to private if not specified
        ];
        $error = 'Bus not found in our system. Please fill in the details below to add and rate this bus.';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Get and validate form data
        $registration = trim($_POST['registration_number'] ?? '');
        $formattedReg = format_registration_number($registration);
        if ($formattedReg === false) {
            throw new Exception('Invalid registration number format. Please use format like NA-1234 or 62-1234');
        }
        $registration = $formattedReg;
        $route_number = trim($_POST['route_number'] ?? '');
        $bus_name = trim($_POST['bus_name'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $bus_type = trim($_POST['bus_type'] ?? 'private'); // Default to private if not specified
        $driver_rating = (int)($_POST['driver_rating'] ?? 0);
        $conductor_rating = (int)($_POST['conductor_rating'] ?? 0);
        $condition_rating = (int)($_POST['condition_rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $guest_name = trim($_POST['guest_name'] ?? '');
        $guest_email = trim($_POST['guest_email'] ?? '');
        
        // Validate ratings
        if ($driver_rating < 1 || $driver_rating > 5 || 
            $conductor_rating < 1 || $conductor_rating > 5 || 
            $condition_rating < 1 || $condition_rating > 5) {
            throw new Exception('All ratings must be between 1 and 5');
        }
        
        // Validate required fields
        if (empty($registration)) {
            throw new Exception('Registration number is required');
        }
        
        // Find or create bus
        if ($busId > 0) {
            // Use existing bus
            $bus_id = $bus['id'];
        } else {
            // Check if bus exists with this registration
            $stmt = $pdo->prepare("SELECT id FROM buses WHERE registration_number = ?");
            $stmt->execute([$registration]);
            $existing_bus = $stmt->fetch();
            
            if ($existing_bus) {
                $bus_id = $existing_bus['id'];
            } else {
                // Create new bus
                // Prepare the bus data with all required fields
                $bus_data = [
                    'registration_number' => $registration,
                    'route_number' => !empty($route_number) ? $route_number : 'N/A',
                    'bus_name' => !empty($bus_name) ? $bus_name : 'Unknown',
                    'company_name' => !empty($company_name) ? $company_name : 'Unknown',
                    'ownership' => in_array($bus_type, ['private', 'government']) ? $bus_type : 'private',
                    'is_premium' => 0,
                    'user_id' => $user_id ?: 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'active',
                    'show_company_name' => 1,
                    'show_bus_name' => 1,
                    'is_premium_active' => 0
                ];

                // Build the query dynamically based on available fields
                $columns = implode(', ', array_keys($bus_data));
                $placeholders = implode(', ', array_fill(0, count($bus_data), '?'));
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO buses 
                        ($columns)
                        VALUES ($placeholders)
                    ") or die(print_r($pdo->errorInfo(), true));
                    
                    $result = $stmt->execute(array_values($bus_data));
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        error_log("Database error: " . print_r($errorInfo, true));
                        throw new Exception("Database error: " . ($errorInfo[2] ?? 'Unknown error'));
                    }
                } catch (PDOException $e) {
                    error_log("PDO Exception: " . $e->getMessage());
                    throw new Exception("Database error: " . $e->getMessage());
                }
                $bus_id = $pdo->lastInsertId();
            }
        }
        
        // Check if user has already reviewed this bus (only for logged-in users)
        $existing_review = null;
        if (!empty($user_id)) {
            $stmt = $pdo->prepare("SELECT id FROM ratings WHERE bus_id = ? AND user_id = ?");
            $stmt->execute([$bus_id, $user_id]);
            $existing_review = $stmt->fetch();
        }
        
        // For guests, we'll always create a new rating since we can't track them uniquely
        $is_guest = empty($user_id);
        
        if ($existing_review) {
            // Update existing review
            $stmt = $pdo->prepare("
                UPDATE ratings 
                SET driver_rating = ?, 
                    conductor_rating = ?, 
                    condition_rating = ?, 
                    comment = ?
                WHERE id = ?
            
            ");
            $stmt->execute([
                $driver_rating, 
                $conductor_rating, 
                $condition_rating, 
                $comment,
                $existing_review['id']
            ]);
            $message = 'Your review has been updated successfully!';
        } else {
            // Create new review
            if ($is_guest) {
                // For guests, we'll create a new rating with a NULL user_id
                // Note: This means guests can submit multiple reviews
                $stmt = $pdo->prepare("
                    INSERT INTO ratings 
                    (bus_id, driver_rating, conductor_rating, condition_rating, comment, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                
                ");
                $stmt->execute([
                    $bus_id, 
                    $driver_rating, 
                    $conductor_rating, 
                    $condition_rating, 
                    $comment
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO ratings 
                    (bus_id, user_id, driver_rating, conductor_rating, condition_rating, comment, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                
                ");
                $stmt->execute([
                    $bus_id, 
                    $user_id, 
                    $driver_rating, 
                    $conductor_rating, 
                    $condition_rating, 
                    $comment
                ]);
            }
            $message = 'Thank you for your review! Your feedback has been submitted successfully.';
        }
        
        $pdo->commit();
        $success = true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Set page title
$page_title = !empty($bus['registration_number']) ? "Rate Bus: {$bus['registration_number']}" : "Rate a Bus";

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-star me-2"></i>
                        <?php echo $bus ? "Rate Bus: {$bus['registration_number']}" : 'Rate a Bus'; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $message ?? 'Thank you for your rating! Your feedback has been submitted successfully.'; ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="bus.php?id=<?php echo $bus_id; ?>" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Bus Details
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <h4>Bus Information</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="registration_number" class="form-label">Registration Number *</label>
                                        <input type="text" class="form-control" id="registration_number" 
                                               name="registration_number" required 
                                               value="<?php echo htmlspecialchars($bus['registration_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="route_number" class="form-label">Route Number</label>
                                        <input type="text" class="form-control" id="route_number" 
                                               name="route_number" 
                                               value="<?php echo htmlspecialchars($bus['route_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="bus_name" class="form-label">Bus Name</label>
                                        <?php if (isset($bus['bus_name']) && !empty($bus['bus_name']) && !isset($_SESSION['user_id'])): ?>
                                            <input type="text" class="form-control" id="bus_name" 
                                                   name="bus_name" 
                                                   value="<?php echo htmlspecialchars($bus['bus_name']); ?>"
                                                   readonly>
                                            <small class="text-muted">Set by bus owner</small>
                                        <?php else: ?>
                                            <input type="text" class="form-control" id="bus_name" 
                                                   name="bus_name" 
                                                   value="<?php echo htmlspecialchars($bus['bus_name'] ?? ''); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <?php if (isset($bus['company_name']) && !empty($bus['company_name']) && !isset($_SESSION['user_id'])): ?>
                                            <input type="text" class="form-control" id="company_name" 
                                                   name="company_name" 
                                                   value="<?php echo htmlspecialchars($bus['company_name']); ?>"
                                                   readonly>
                                            <small class="text-muted">Set by bus owner</small>
                                        <?php else: ?>
                                            <input type="text" class="form-control" id="company_name" 
                                                   name="company_name" 
                                                   value="<?php echo htmlspecialchars($bus['company_name'] ?? ''); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="bus_type" class="form-label">Bus Type *</label>
                                        <select class="form-select" id="bus_type" name="bus_type" required>
                                            <option value="private" <?php echo (isset($bus['type']) && $bus['type'] === 'private') ? 'selected' : ''; ?>>Private Bus</option>
                                            <option value="government" <?php echo (isset($bus['type']) && $bus['type'] === 'government') ? 'selected' : ''; ?>>Government Bus</option>
                                        </select>
                                        <small class="text-muted">Select whether this is a private or government bus</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h4>Your Rating</h4>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="rating-item">
                                            <label class="form-label d-block mb-2">Driver *</label>
                                            <div class="rating-input">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" id="driver_<?php echo $i; ?>" name="driver_rating" 
                                                           value="<?php echo $i; ?>" required 
                                                           <?php echo (int)($_POST['driver_rating'] ?? 0) === $i ? 'checked' : ''; ?>>
                                                    <label for="driver_<?php echo $i; ?>" title="<?php echo $i; ?> star">★</label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="rating-item">
                                            <label class="form-label d-block mb-2">Conductor *</label>
                                            <div class="rating-input">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" id="conductor_<?php echo $i; ?>" name="conductor_rating" 
                                                           value="<?php echo $i; ?>" required
                                                           <?php echo (int)($_POST['conductor_rating'] ?? 0) === $i ? 'checked' : ''; ?>>
                                                    <label for="conductor_<?php echo $i; ?>" title="<?php echo $i; ?> star">★</label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="rating-item">
                                            <label class="form-label d-block mb-2">Bus Condition *</label>
                                            <div class="rating-input">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" id="condition_<?php echo $i; ?>" name="condition_rating" 
                                                           value="<?php echo $i; ?>" required
                                                           <?php echo (int)($_POST['condition_rating'] ?? 0) === $i ? 'checked' : ''; ?>>
                                                    <label for="condition_<?php echo $i; ?>" title="<?php echo $i; ?> star">★</label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="comment" class="form-label">Your Review (Optional)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3"><?php 
                                    echo htmlspecialchars($_POST['comment'] ?? ''); 
                                ?></textarea>
                            </div>
                            
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <div class="mb-4">
                                    <h5>Your Information</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="guest_name" class="form-label">Your Name *</label>
                                            <input type="text" class="form-control" id="guest_name" 
                                                   name="guest_name" required
                                                   value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="guest_email" class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" id="guest_email" 
                                                   name="guest_email" required
                                                   value="<?php echo htmlspecialchars($_POST['guest_email'] ?? ''); ?>">
                                            <div class="form-text">We'll never share your email with anyone else.</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-star me-1"></i> Submit Rating
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-item {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}

.rating-input {
    display: flex;
    flex-direction: row;
    gap: 2px;
    flex-grow: 1;
    direction: rtl; /* This makes the stars render from right to left */
    unicode-bidi: bidi-override; /* This ensures the text direction is properly handled */
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    cursor: pointer;
    color: #ddd;
    font-size: 1.5rem;
    transition: all 0.2s;
    flex: 1;
    text-align: center;
    padding: 0.2rem;
}

/* Update the checked and hover states to work with RTL direction */
.rating-input input[type="radio"]:checked ~ label,
.rating-input input[type="radio"]:checked + label {
    color: #ffc107;
    transform: scale(1.1);
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:hover ~ label {
    color: #ffc107;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .rating-input label {
        font-size: 1.8rem;
        padding: 0.3rem;
    }
    
    .rating-item {
        padding: 0.75rem;
    }
}

@media (min-width: 992px) {
    .rating-item {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
