<?php
/**
 * College Complaint Management System
 * Admin Users Directory
 * 
 * Concept: Demonstrates user auditing and relational aggregation (counting complaints per user).
 * Uses PDO prepared statements for filtering and searching.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();

$roleFilter  = trim($_GET['role'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

// Build query joining users with complaint count
$sql = "
    SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at,
           COUNT(c.id) AS total_complaints
    FROM users u
    LEFT JOIN complaints c ON u.id = c.user_id
    WHERE 1=1
";

$params = [];

if (!empty($roleFilter) && in_array($roleFilter, ['student', 'admin'], true)) {
    $sql .= " AND u.role = :role";
    $params[':role'] = $roleFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q)";
    $params[':q'] = "%{$searchQuery}%";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$pageTitle = 'Registered Users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="fw-bold mb-1">User Accounts & Directory</h2>
                <p class="text-muted small mb-0">Overview of registered students and administrative staff accounts.</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <span class="badge bg-secondary px-3 py-2 fs-6">
                    <i class="bi bi-people me-1"></i> <?= count($usersList) ?> Account(s) Found
                </span>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- Search and Filter Toolbar -->
        <div class="app-card shadow-sm border-0 mb-4">
            <div class="app-card-body p-3">
                <form method="GET" action="users.php" class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Search by name, email, or phone..." value="<?= e($searchQuery) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="role" class="form-select form-select-sm">
                            <option value="">-- All Roles --</option>
                            <option value="student" <?= ($roleFilter === 'student') ? 'selected' : '' ?>>Students Only</option>
                            <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : '' ?>>Administrators Only</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <?php if (!empty($searchQuery) || !empty($roleFilter)): ?>
                            <a href="users.php" class="btn btn-sm btn-outline-danger" title="Clear Filters">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="app-card shadow-sm border-0">
            <div class="app-card-body p-0">
                <div class="table-responsive">
                    <table class="table app-table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Complaints Lodged</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($usersList as $u): ?>
                                <tr>
                                    <td class="text-muted small"><?= $i++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <i class="bi <?= $u['role'] === 'admin' ? 'bi-shield-shaded text-danger' : 'bi-person text-primary' ?> me-1"></i>
                                            <?= e($u['name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= e($u['email']) ?>" class="text-decoration-none">
                                            <?= e($u['email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= !empty($u['phone']) ? e($u['phone']) : '<span class="text-muted small">&mdash;</span>' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> px-2 py-1">
                                            <?= strtoupper(e($u['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'student'): ?>
                                            <a href="<?= BASE_URL ?>/admin/complaints.php?q=<?= urlencode($u['name']) ?>" 
                                               class="badge bg-light text-primary border text-decoration-none">
                                                <?= (int)$u['total_complaints'] ?> complaints
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A (Staff)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= formatDate($u['created_at'], false) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
