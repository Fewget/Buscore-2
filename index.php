<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set page title
$page_title = 'BuScore - Rate and Review Buses';

// Start output buffering
ob_start();

// Get most rated buses
$mostRatedBuses = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.id,
            b.registration_number,
            b.ownership as type,
            COUNT(r.id) as rating_count,
            COALESCE(ROUND(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 1), 0) as avg_rating
        FROM buses b
        LEFT JOIN ratings r ON b.id = r.bus_id
        GROUP BY b.id, b.registration_number, b.ownership
        HAVING COUNT(r.id) > 0
        ORDER BY avg_rating DESC, rating_count DESC
        LIMIT 6
    ");
    $mostRatedBuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching most rated buses: " . $e->getMessage());
}
?>
<?php
$page_title = 'Buscore - Rate and Review Buses';
include 'includes/header.php';
?>

<style>
        .most-rated .bus-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.25rem;
            margin: 1.5rem 0;
            padding: 0.5rem 0;
            width: 100%;
        }
        
        .bus-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem 1.25rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none;
            color: inherit;
            border: 2px solid #e9ecef;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .bus-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 1400px) {
            .most-rated .bus-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 1200px) {
            .most-rated .bus-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .most-rated .bus-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .most-rated .bus-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .bus-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: #3498db;
        }
        
        .bus-card:hover::before {
            opacity: 1;
            height: 5px;
        }
        
        .bus-card h3 {
            margin: 0 0 0.5rem;
            color: #2c3e50;
        }
        
        .rating {
            color: #f1c40f;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .rating-count {
            color: #7f8c8d;
            font-size: 0.8em;
            font-weight: normal;
        }
        
        .bus-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .bus-type.private {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .rating-display {
            margin: 0.5rem 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .rating-display .stars {
            color: #f1c40f;
            font-size: 1rem;
            white-space: nowrap;
            line-height: 1;
            margin-bottom: 0.1rem;
        }
        
        .rating-display .stars i {
            display: inline-block;
            width: 1em;
            text-align: center;
        }
        
        .rating-display .rating-text {
            font-size: 0.9rem;
            line-height: 1.2;
            white-space: nowrap;
            text-align: center;
        }
        
        .rating-display .fw-bold {
            color: #2c3e50;
        }
        
        .bus-type.government {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Search form styles removed - using header search instead */
    </style>
</head>
<body>
<?php 
// Include header
require_once 'includes/header.php';
?>

<main class="home-page" style="padding-top: 100px;">
    <div class="container">
        <section class="most-rated mt-4">
            <h2>Most Rated Buses</h2>
            <div class="bus-grid">
                <?php if (!empty($mostRatedBuses)): ?>
                    <?php foreach ($mostRatedBuses as $bus): ?>
                        <a href="bus_info.php?id=<?php echo $bus['id']; ?>" class="bus-card">
                            <h3><?php echo htmlspecialchars(format_registration_number($bus['registration_number'])); ?></h3>
                            <div class="rating-display">
                                <?php 
                                $rating = round($bus['avg_rating'] * 2) / 2; // Round to nearest 0.5
                                $ratingCount = (int)$bus['rating_count'];
                                
                                if ($ratingCount > 0 && $rating > 0): ?>
                                    <div class="stars text-center mb-1">
                                        <?php 
                                        $fullStars = floor($rating);
                                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $fullStars) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="rating-text text-center">
                                        <span class="fw-bold"><?php echo number_format($bus['avg_rating'], 1); ?></span>
                                        <span class="text-muted">/5.0</span>
                                        <small class="text-muted ms-1">
                                            (<?php echo $ratingCount; ?> rating<?php echo $ratingCount !== 1 ? 's' : ''; ?>)
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">No ratings yet</small>
                                <?php endif; ?>
                            </div>
                            <div class="bus-type <?php echo $bus['type'] === 'government' ? 'government' : 'private'; ?>">
                                <?php echo $bus['type'] === 'government' ? 'Government' : 'Private'; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No buses have been rated yet. Be the first to rate a bus!</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php 
// Include footer
include 'includes/footer.php'; 

// End output buffering and flush
ob_end_flush();
?>
