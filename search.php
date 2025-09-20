<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Initialize variables
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$buses = [];

// Process search if query exists
if (!empty($searchQuery)) {
    try {
        // Check if the search query looks like a registration number
        $formattedReg = format_registration_number($searchQuery);
        $searchReg = $formattedReg !== false ? $formattedReg : $searchQuery;
        
        // Prepare search parameters
        $query = "%$searchQuery%";
        $queryWithoutSpaces = "%" . str_replace(' ', '', $searchQuery) . "%";
        $descriptionQuery = "%$searchQuery%";
        $regQuery = "%$searchReg%";
        
        // Search for buses
        $sql = "
            SELECT 
                b.id,
                b.registration_number,
                b.bus_name,
                b.company_name,
                b.route_number,
                b.route_description,
                COALESCE(ROUND(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 1), 0) as avg_rating,
                COUNT(r.id) as rating_count,
                u.username as added_by,
                b.ownership as type,
                b.is_premium
            FROM buses b
            LEFT JOIN ratings r ON b.id = r.bus_id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE 
                (b.registration_number = :exact_reg OR 
                 b.registration_number LIKE :reg_query OR
                 b.route_number LIKE :query OR
                 b.route_number LIKE :query_without_spaces OR
                 b.route_description LIKE :description_query OR
                 b.company_name LIKE :query OR
                 b.bus_name LIKE :query)
                AND b.status = 'active'
            GROUP BY b.id
            ORDER BY 
                b.registration_number = :exact_reg DESC,
                b.registration_number LIKE :reg_query AND b.registration_number != :exact_reg2 DESC,
                b.route_number = :exact_query DESC,
                rating_count DESC,
                avg_rating DESC,
                b.id DESC
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':exact_reg', $searchReg);
        $stmt->bindValue(':exact_reg2', $searchReg);
        $stmt->bindValue(':reg_query', $regQuery);
        $stmt->bindValue(':query', $query);
        $stmt->bindValue(':query_without_spaces', $queryWithoutSpaces);
        $stmt->bindValue(':description_query', $descriptionQuery);
        $stmt->bindValue(':exact_query', $searchQuery);
        $stmt->execute();
        
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "An error occurred while searching: " . $e->getMessage();
    }
}

// Include header
$page_title = 'Search Results';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($searchQuery)): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Search Results</h2>
                    <div class="text-muted">
                        <?php echo count($buses); ?> result<?php echo count($buses) !== 1 ? 's' : ''; ?> found
                    </div>
                </div>
                
                <?php if (empty($buses)): ?>
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <h4>Bus not found</h4>
                                <p class="mb-3">We couldn't find any buses matching your search for "<?php echo htmlspecialchars($searchQuery); ?>".</p>
                                <p>Would you like to add and rate this bus?</p>
                                <a href="rate-bus.php?registration_number=<?php echo urlencode($searchQuery); ?>" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-1"></i> Add & Rate This Bus
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($buses as $bus): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <a href="bus.php?id=<?php echo $bus['id']; ?>" class="text-decoration-none">
                                                <?php 
                                                // Show bus name only if premium is enabled
                                                $isPremium = !empty($bus['is_premium']);
                                                $busName = $bus['bus_name'] ?? null;
                                                
                                                if ($isPremium && $busName) {
                                                    echo htmlspecialchars($busName);
                                                } else if (!empty($bus['registration_number'])) {
                                                    echo 'Bus #' . htmlspecialchars($bus['registration_number']);
                                                } else {
                                                    echo 'ID:' . $bus['id'];
                                                }
                                                ?>
                                            </a>
                                        </h5>
                                        <div class="mb-2">
                                            <span class="badge bg-primary me-1">
                                                <?php 
                                                if (!empty($bus['registration_number'])) {
                                                    echo 'Bus #' . htmlspecialchars($bus['registration_number']);
                                                } else {
                                                    echo 'ID:' . $bus['id'];
                                                }
                                                ?>
                                            </span>
                                            <?php if (!empty($bus['type'])): ?>
                                                <span class="badge bg-<?php echo $bus['type'] === 'government' ? 'success' : 'primary'; ?> me-1">
                                                    <?php echo ucfirst(htmlspecialchars($bus['type'])); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php 
                                            // Show company name only if premium is enabled
                                            if ($isPremium && !empty($bus['company_name'])): ?>
                                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($bus['company_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <?php if (!empty($bus['added_by'])): ?>
                                            <small class="text-muted d-block">Added by: <?php echo htmlspecialchars($bus['added_by']); ?></small>
                                        <?php endif; ?>
                                        <div class="rating">
                                            <?php 
                                            $rating = round($bus['avg_rating'], 1);
                                            $ratingCount = (int)$bus['rating_count'];
                                            
                                            if ($ratingCount > 0 && $rating > 0): ?>
                                                <span class="fw-bold"><?php echo number_format($rating, 1); ?></span>
                                                <span class="text-muted">/5.0</span>
                                                <small class="text-muted ms-1">
                                                    (<?php echo $ratingCount; ?> rating<?php echo $ratingCount !== 1 ? 's' : ''; ?>)
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">No ratings yet</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($bus['route_number']) || !empty($bus['route_description'])): ?>
                                    <div class="mt-2">
                                        <?php if (!empty($bus['route_number'])): ?>
                                            <span class="d-inline-block me-3">
                                                <i class="fas fa-route text-primary me-1"></i>
                                                <strong>Route:</strong> <?php echo htmlspecialchars($bus['route_number']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($bus['route_description'])): ?>
                                            <span class="text-muted">
                                                <?php echo htmlspecialchars($bus['route_description']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="btn-group" role="group">
                                        <a href="bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-info-circle me-1"></i> View Details
                                        </a>
                                        <a href="bus.php?id=<?php echo $bus['id']; ?>#reviews" class="btn btn-sm btn-outline-secondary">
                                            <i class="far fa-comment-dots me-1"></i> View Reviews
                                        </a>
                                        <a href="rate-bus.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-star me-1"></i> Rate This Bus
                                        </a>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-star text-warning"></i> 
                                        <?php echo number_format($bus['avg_rating'], 1); ?> (<?php echo $bus['rating_count']; ?> reviews)
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-4"></i>
                    <h3>Search for Buses</h3>
                    <p class="text-muted">Enter a bus registration number, route number, or company name to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.rating {
    color: #ffc107;
    font-size: 1rem;
}

.badge {
    font-weight: 500;
}

.search-form .input-group {
    max-width: 600px;
    margin: 0 auto;
}

.list-group-item {
    transition: all 0.2s ease;
}

.list-group-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
