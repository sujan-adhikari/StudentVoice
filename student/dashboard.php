<?php
/**
 * College Complaint Management System
 * Student Dashboard
 * 
 * Concept: Overview of student's complaint activities. Fetches counts
 * using SQL aggregate functions (COUNT, SUM) grouped by status for the active user.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Restrict page access strictly to logged-in students
requireRole('student');

$user = currentUser();
$userId = (int) $user['id'];
$pdo = getDBConnection();

// Fetch summary metrics for this student
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_complaints,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS total_pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS total_in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS total_resolved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS total_rejected
    FROM complaints 
    WHERE user_id = :user_id
");
$statsStmt->execute([':user_id' => $userId]);
$stats = $statsStmt->fetch() ?: [
    'total_complaints' => 0,
    'total_pending' => 0,
    'total_in_progress' => 0,
    'total_resolved' => 0,
    'total_rejected' => 0
];

// Fetch 5 most recent complaints submitted by this student
$recentStmt = $pdo->prepare("
    SELECT c.id, c.complaint_number, c.title, c.status, c.created_at, cat.name AS category_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.user_id = :user_id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$recentStmt->execute([':user_id' => $userId]);
$recentComplaints = $recentStmt->fetchAll();

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Breadcrumb & Top Bar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="fw-bold mb-1">Student Dashboard</h2>
                <p class="text-muted small mb-0">
                    Welcome back, <strong><?= e($user['name']) ?></strong>! Track and manage your campus grievances.
                </p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="<?= BASE_URL ?>/student/submit_complaint.php" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Lodge New Complaint
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- Statistics Metrics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary me-3">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Lodged</div>
                        <div class="stat-value"><?= (int)$stats['total_complaints'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning me-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value"><?= (int)$stats['total_pending'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon-wrapper bg-info-subtle text-info me-3">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <div>
                        <div class="stat-title">In Progress</div>
                        <div class="stat-value"><?= (int)$stats['total_in_progress'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon-wrapper bg-success-subtle text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Resolved</div>
                        <div class="stat-value"><?= (int)$stats['total_resolved'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Complaints Table Panel -->
        <div class="app-card shadow-sm mb-4">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="bi bi-list-task text-primary me-2"></i> Recent Complaints
                </h5>
                <a href="<?= BASE_URL ?>/student/my_complaints.php" class="btn btn-sm btn-outline-primary">
                    View All Complaints <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="app-card-body p-0">
                <?php if (empty($recentComplaints)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3"><i class="bi bi-inbox fs-1"></i></div>
                        <h6 class="fw-bold">No Complaints Submitted Yet</h6>
                        <p class="text-muted small mb-3">You have not submitted any complaints yet. Encountered a campus issue?</p>
                        <a href="<?= BASE_URL ?>/student/submit_complaint.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Lodge Your First Complaint
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table app-table align-middle">
                            <thead>
                                <tr>
                                    <th>Complaint No</th>
                                    <th>Category</th>
                                    <th>Subject / Title</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentComplaints as $c): ?>
                                    <tr>
                                        <td>
                                            <span class="font-monospace fw-semibold text-primary">
                                                <?= e($c['complaint_number']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= e($c['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark d-inline-block text-truncate" style="max-width: 260px;">
                                                <?= e($c['title']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= formatDate($c['created_at'], false) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($c['status']) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= (int)$c['id'] ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="View Details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($c['status'] === 'Pending'): ?>
                                                <a href="<?= BASE_URL ?>/student/edit_complaint.php?id=<?= (int)$c['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary ms-1" title="Edit Complaint">
                                                    <i class="bi bi-pencil"></i>
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

        <!-- Quick Tips Callout -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 bg-white rounded border shadow-sm h-100">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-shield-check text-success me-1"></i> Grievance Redressal Policy
                    </h6>
                    <p class="text-muted small mb-0">
                        Urgent matters regarding electricity, internet outages, and lab equipment are reviewed by department coordinators within 24 to 48 working hours.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-white rounded border shadow-sm h-100">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-pencil-square text-primary me-1"></i> Editing & Withdrawing
                    </h6>
                    <p class="text-muted small mb-0">
                        Complaints can be updated or deleted by you as long as their status remains <strong>Pending</strong>. Once an administrator begins review, changes are locked to maintain audit records.
                    </p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
