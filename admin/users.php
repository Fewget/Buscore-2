<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$queryParams = [];

if (!empty($search)) {
    $searchTerm = "%$search%";
    $searchQuery = "WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
    $queryParams = array_fill(0, 3, $searchTerm);
}

// Get users with optional search
$query = "SELECT * FROM users $searchQuery ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($queryParams);
$users = $stmt->fetchAll();

// Include header after setting up all required variables
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Manage Users</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">User List</h5>
            <a href="user-add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add User
            </a>
        </div>
        <div class="card-body">
            <!-- Search Bar -->
            <div class="mb-4">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by username, email, or name..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="users.php" class="btn btn-outline-danger">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="user-delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
