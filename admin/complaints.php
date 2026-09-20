<?php
/**
 * College Complaint Management System
 * Admin Complaint Master Directory (Search & Filter)
 * 
 * Concept: Demonstrates multi-criteria search and filtering in MySQL.
 * Searches across multiple columns (complaint number, title, student name)
 * and filters by category and status using PDO prepared statements.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();

// Read query string parameters
$statusFilter   = trim($_GET['status'] ?? '');
$categoryFilter = filter_var($_GET['category'] ?? '', FILTER_VALIDATE_INT);
$searchQuery    = trim($_GET['q'] ?? '');

// Fetch all categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Base SQL query joining complaints, categories, and users
$sql = "
    SELECT c.id, c.complaint_number, c.title, c.image, c.status, c.created_at,
           cat.name AS category_name,
           u.name AS student_name, u.email AS student_email
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    WHERE 1=1
";

$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'In Progress', 'Resolved', 'Rejected'], true)) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($categoryFilter)) {
    $sql .= " AND c.category_id = :cat_id";
    $params[':cat_id'] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (c.complaint_number LIKE :q OR c.title LIKE :q OR c.description LIKE :q OR u.name LIKE :q OR u.email LIKE :q)";
    $params[':q'] = "%{$searchQuery}%";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Count total matching results
$totalFound = count($complaints);

$pageTitle = 'Manage Grievances';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="fw-bold mb-1">Grievance Management Directory</h2>
                <p class="text-muted small mb-0">Search, filter, and audit all student complaints across campus departments.</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <span class="badge bg-secondary px-3 py-2 fs-6">
                    <i class="bi bi-file-earmark-text me-1"></i> <?= $totalFound ?> Complaint(s) Found
                </span>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- Search & Filter Controls Card -->
        <div class="app-card shadow-sm border-0 mb-4">
            <div class="app-card-body p-3">
                <form method="GET" action="complaints.php" class="row g-2">
                    
                    <!-- Search Keyword -->
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Search Keywords</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Ref ID, Title, or Student name..." value="<?= e($searchQuery) ?>">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Filter by Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">-- All Categories --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ((int)$categoryFilter === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Filter by Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- All Statuses --</option>
                            <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= ($statusFilter === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                            <option value="Resolved" <?= ($statusFilter === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                            <option value="Rejected" <?= ($statusFilter === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Apply
                        </button>
                        <?php if (!empty($searchQuery) || !empty($statusFilter) || !empty($categoryFilter)): ?>
                            <a href="complaints.php" class="btn btn-sm btn-outline-danger" title="Clear Filters">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>

        <!-- Master Table -->
        <div class="app-card shadow-sm border-0">
            <div class="app-card-body p-0">
                <?php if (empty($complaints)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-2"><i class="bi bi-search fs-1"></i></div>
                        <h6 class="fw-bold">No Complaints Match Your Criteria</h6>
                        <p class="text-muted small mb-3">Try adjusting your search terms or category/status filters.</p>
                        <a href="complaints.php" class="btn btn-sm btn-outline-primary">Reset Filters</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table app-table align-middle">
                            <thead>
                                <tr>
                                    <th>Ref Number</th>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Subject / Title</th>
                                    <th>Photo</th>
                                    <th>Lodged Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/view_complaint.php?id=<?= (int)$c['id'] ?>" class="font-monospace fw-bold text-decoration-none">
                                                <?= e($c['complaint_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($c['student_name']) ?></div>
                                            <div class="text-muted small" style="font-size: 0.78rem;"><?= e($c['student_email']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?= e($c['category_name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark d-inline-block text-truncate" style="max-width: 230px;">
                                                <?= e($c['title']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($c['image'])): ?>
                                                <a href="<?= BASE_URL ?>/<?= e($c['image']) ?>" target="_blank" title="View attached photo">
                                                    <i class="bi bi-image text-primary fs-5"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= formatDate($c['created_at'], false) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($c['status']) ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="<?= BASE_URL ?>/admin/view_complaint.php?id=<?= (int)$c['id'] ?>" 
                                               class="btn btn-sm btn-primary" title="Review & Update">
                                                <i class="bi bi-pencil-square me-1"></i> Review
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/delete_complaint.php?id=<?= (int)$c['id'] ?>&token=<?= getCsrfToken() ?>" 
                                               class="btn btn-sm btn-outline-danger ms-1"
                                               onclick="return confirmAction('Are you sure you want to permanently delete complaint <?= e($c['complaint_number']) ?>?');" 
                                               title="Delete Record">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
