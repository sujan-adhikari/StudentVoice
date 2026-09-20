<?php
/**
 * College Complaint Management System
 * Student My Complaints List (READ Operation)
 * 
 * Concept: Demonstrates fetching and displaying records filtered by the logged-in user.
 * Supports status filtering and keyword search with PDO prepared statements.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = currentUser();
$userId = (int) $user['id'];
$pdo = getDBConnection();

// Read filter parameters
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery  = trim($_GET['q'] ?? '');

// Build dynamic WHERE clause safely using prepared statement parameters
$sql = "
    SELECT c.id, c.complaint_number, c.title, c.image, c.status, c.created_at, c.updated_at,
           cat.name AS category_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.user_id = :user_id
";

$params = [':user_id' => $userId];

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'In Progress', 'Resolved', 'Rejected'], true)) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (c.complaint_number LIKE :q OR c.title LIKE :q OR c.description LIKE :q)";
    $params[':q'] = "%{$searchQuery}%";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Total count per status for the filter pills
$countsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as all_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as progress_count,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM complaints WHERE user_id = :user_id
");
$countsStmt->execute([':user_id' => $userId]);
$counts = $countsStmt->fetch();

$pageTitle = 'My Complaints';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="fw-bold mb-1">My Submitted Complaints</h2>
                <p class="text-muted small mb-0">Track all grievances submitted by your account and check current resolution status.</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="<?= BASE_URL ?>/student/submit_complaint.php" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Lodge New Complaint
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- Filter and Search Toolbar -->
        <div class="app-card shadow-sm mb-4">
            <div class="app-card-body py-3">
                <form method="GET" action="my_complaints.php" class="row g-2 align-items-center">
                    
                    <!-- Status Filter Tabs / Dropdown -->
                    <div class="col-md-7">
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="my_complaints.php<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" 
                               class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                All (<?= (int)$counts['all_count'] ?>)
                            </a>
                            <a href="my_complaints.php?status=Pending<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                               class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">
                                Pending (<?= (int)$counts['pending_count'] ?>)
                            </a>
                            <a href="my_complaints.php?status=In Progress<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                               class="btn btn-sm <?= $statusFilter === 'In Progress' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                In Progress (<?= (int)$counts['progress_count'] ?>)
                            </a>
                            <a href="my_complaints.php?status=Resolved<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                               class="btn btn-sm <?= $statusFilter === 'Resolved' ? 'btn-success' : 'btn-outline-success' ?>">
                                Resolved (<?= (int)$counts['resolved_count'] ?>)
                            </a>
                            <a href="my_complaints.php?status=Rejected<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                               class="btn btn-sm <?= $statusFilter === 'Rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">
                                Rejected (<?= (int)$counts['rejected_count'] ?>)
                            </a>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <?php if (!empty($statusFilter)): ?>
                                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                            <?php endif; ?>
                            <input type="text" name="q" class="form-control" placeholder="Search by title or CMP number..." 
                                   value="<?= e($searchQuery) ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                                <a href="my_complaints.php" class="btn btn-outline-danger" title="Clear Filters">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- Complaints Table -->
        <div class="app-card shadow-sm">
            <div class="app-card-body p-0">
                <?php if (empty($complaints)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-2"><i class="bi bi-folder-x fs-1"></i></div>
                        <h6 class="fw-bold">No Complaints Found</h6>
                        <p class="text-muted small mb-3">
                            <?= (!empty($statusFilter) || !empty($searchQuery)) ? 'No complaints match the specified filter criteria.' : 'You have not submitted any complaints yet.' ?>
                        </p>
                        <a href="<?= BASE_URL ?>/student/submit_complaint.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Lodge a New Complaint
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table app-table align-middle">
                            <thead>
                                <tr>
                                    <th>Complaint No</th>
                                    <th>Category</th>
                                    <th>Subject</th>
                                    <th>Evidence</th>
                                    <th>Submitted On</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= (int)$c['id'] ?>" 
                                               class="font-monospace fw-bold text-decoration-none">
                                                <?= e($c['complaint_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                <?= e($c['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-inline-block text-truncate" style="max-width: 250px;">
                                                <?= e($c['title']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($c['image'])): ?>
                                                <a href="<?= BASE_URL ?>/<?= e($c['image']) ?>" target="_blank" title="View attached image">
                                                    <span class="badge bg-secondary-subtle text-secondary border">
                                                        <i class="bi bi-image me-1"></i> Photo
                                                    </span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= formatDate($c['created_at']) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($c['status']) ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <!-- View Details -->
                                            <a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= (int)$c['id'] ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <!-- Edit (Allowed ONLY when status is Pending) -->
                                            <?php if ($c['status'] === 'Pending'): ?>
                                                <a href="<?= BASE_URL ?>/student/edit_complaint.php?id=<?= (int)$c['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary ms-1" title="Edit Complaint">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                <!-- Delete (Allowed ONLY when status is Pending) -->
                                                <a href="<?= BASE_URL ?>/student/delete_complaint.php?id=<?= (int)$c['id'] ?>&token=<?= getCsrfToken() ?>" 
                                                   class="btn btn-sm btn-outline-danger ms-1" 
                                                   onclick="return confirmAction('Are you sure you want to delete this pending complaint? This action cannot be undone.');" 
                                                   title="Delete Complaint">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
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
