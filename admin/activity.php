<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define SITE_URL if not defined
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/BS');
}

// Include config and functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

// Get activity logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$total = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Include header after setting up all required variables
require_once 'includes/header.php';

// Get logs
$stmt = $pdo->prepare("SELECT al.*, u.username, u.full_name 
                      FROM activity_logs al 
                      LEFT JOIN users u ON al.user_id = u.id 
                      ORDER BY al.created_at DESC 
                      LIMIT :offset, :per_page");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Activity Logs</h1>
    </div>

    <div class="admin-card">
        <div class="card-header">
            <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <a href="user-edit.php?id=<?php echo $log['user_id']; ?>">
                                            <?php echo htmlspecialchars($log['full_name'] ?? $log['username']); ?>
                                        </a>
                                    <?php else: ?>
                                        System
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
