<?php
/**
 * College Complaint Management System
 * View Complaint Details & History Timeline (Student View)
 * 
 * Concept: Demonstrates single-record relational fetching (READ).
 * Implements strict ownership verification: user_id must match logged-in student.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = currentUser();
$userId = (int) $user['id'];
$complaintId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$complaintId) {
    setFlash('danger', 'Invalid complaint identifier.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

$pdo = getDBConnection();

// Fetch complaint details strictly verifying user ownership
$stmt = $pdo->prepare("
    SELECT c.*, cat.name AS category_name, u.name AS student_name, u.email AS student_email
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    WHERE c.id = :id AND c.user_id = :user_id
    LIMIT 1
");
$stmt->execute([
    ':id'      => $complaintId,
    ':user_id' => $userId
]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found or you do not have permission to view it.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

// Fetch complaint timeline history
$logsStmt = $pdo->prepare("
    SELECT l.*, u.name AS updater_name, u.role AS updater_role
    FROM complaint_logs l
    JOIN users u ON l.updated_by = u.id
    WHERE l.complaint_id = :complaint_id
    ORDER BY l.created_at ASC
");
$logsStmt->execute([':complaint_id' => $complaintId]);
$logs = $logsStmt->fetchAll();

$pageTitle = 'Complaint Details: ' . $complaint['complaint_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Top Action Bar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <a href="<?= BASE_URL ?>/student/my_complaints.php" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Back to My Complaints
                </a>
                <h2 class="fw-bold mb-0 mt-1">
                    Grievance <span class="text-primary font-monospace"><?= e($complaint['complaint_number']) ?></span>
                </h2>
            </div>
            <div class="mt-2 mt-sm-0 d-flex gap-2">
                <?php if ($complaint['status'] === 'Pending'): ?>
                    <a href="<?= BASE_URL ?>/student/edit_complaint.php?id=<?= (int)$complaint['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit Details
                    </a>
                    <a href="<?= BASE_URL ?>/student/delete_complaint.php?id=<?= (int)$complaint['id'] ?>&token=<?= getCsrfToken() ?>" 
                       class="btn btn-outline-danger"
                       onclick="return confirmAction('Are you sure you want to delete this pending complaint?');">
                        <i class="bi bi-trash me-1"></i> Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="row g-4">
            <!-- Main Details Panel -->
            <div class="col-lg-8">
                <div class="app-card shadow-sm border-0 mb-4">
                    <div class="app-card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-secondary border me-2">
                                <i class="bi bi-building me-1"></i> <?= e($complaint['category_name']) ?>
                            </span>
                            <span class="text-muted small">
                                Submitted on <?= formatDate($complaint['created_at']) ?>
                            </span>
                        </div>
                        <div>
                            <?= getStatusBadge($complaint['status']) ?>
                        </div>
                    </div>

                    <div class="app-card-body p-4">
                        <h4 class="fw-bold mb-3"><?= e($complaint['title']) ?></h4>

                        <div class="text-muted small fw-semibold text-uppercase mb-1">Issue Description</div>
                        <div class="p-3 bg-light rounded border mb-4" style="line-height: 1.6; white-space: pre-line;">
                            <?= e($complaint['description']) ?>
                        </div>

                        <!-- Uploaded Evidence Photo -->
                        <?php if (!empty($complaint['image'])): ?>
                            <div class="mb-4">
                                <div class="text-muted small fw-semibold text-uppercase mb-2">
                                    <i class="bi bi-image me-1"></i> Attached Photographic Evidence
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>/<?= e($complaint['image']) ?>" target="_blank">
                                        <img src="<?= BASE_URL ?>/<?= e($complaint['image']) ?>" 
                                             alt="Attached Evidence" class="evidence-preview-box shadow-sm">
                                    </a>
                                </div>
                                <div class="form-text small mt-1">Click the image to view full resolution in a new tab.</div>
                            </div>
                        <?php endif; ?>

                        <!-- Official Administration Remark -->
                        <?php if (!empty($complaint['admin_remark'])): ?>
                            <div class="alert alert-info border-info-subtle shadow-sm mb-0">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-chat-left-quote-fill fs-5 text-primary me-2"></i>
                                    <strong class="text-dark">Official Administration Remark</strong>
                                </div>
                                <p class="mb-0 small text-secondary" style="white-space: pre-line;">
                                    <?= e($complaint['admin_remark']) ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="p-3 bg-light rounded border text-muted small">
                                <i class="bi bi-hourglass-split me-1"></i> Administration review in progress. Remarks will be displayed here once campus officials update the status.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Complaint Resolution Timeline -->
                <div class="app-card shadow-sm border-0">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi bi-clock-history text-primary me-2"></i> Activity & Resolution Timeline
                        </h5>
                    </div>
                    <div class="app-card-body p-4">
                        <?php if (empty($logs)): ?>
                            <p class="text-muted small mb-0">No timeline events recorded yet.</p>
                        <?php else: ?>
                            <ul class="timeline">
                                <?php foreach ($logs as $log): ?>
                                    <li class="timeline-item">
                                        <div class="timeline-badge">
                                            <i class="bi bi-circle-fill"></i>
                                        </div>
                                        <div class="timeline-content shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div>
                                                    <span class="fw-semibold text-dark"><?= e($log['updater_name']) ?></span>
                                                    <span class="badge bg-secondary-subtle text-secondary small ms-1">
                                                        <?= strtoupper(e($log['updater_role'])) ?>
                                                    </span>
                                                </div>
                                                <span class="timeline-date"><?= formatDate($log['created_at']) ?></span>
                                            </div>
                                            <div class="mb-1">
                                                Status updated to: <strong><?= getStatusBadge($log['new_status']) ?></strong>
                                            </div>
                                            <?php if (!empty($log['remark'])): ?>
                                                <div class="text-muted small mt-1 bg-white p-2 rounded border">
                                                    <?= e($log['remark']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Meta Sidebar -->
            <div class="col-lg-4">
                <div class="app-card shadow-sm border-0 mb-4">
                    <div class="app-card-header bg-white">
                        <h6 class="app-card-title small fw-bold text-uppercase text-muted">
                            <i class="bi bi-info-circle me-1"></i> Quick Information
                        </h6>
                    </div>
                    <div class="app-card-body p-3">
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted">Reference:</td>
                                    <td class="fw-bold font-monospace text-primary"><?= e($complaint['complaint_number']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status:</td>
                                    <td><?= getStatusBadge($complaint['status']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Category:</td>
                                    <td class="fw-medium"><?= e($complaint['category_name']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Lodged By:</td>
                                    <td><?= e($complaint['student_name']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created:</td>
                                    <td><?= formatDate($complaint['created_at']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Last Updated:</td>
                                    <td><?= formatDate($complaint['updated_at']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-3 bg-white rounded border shadow-sm small text-muted">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-question-circle text-primary me-1"></i> Need Urgent Action?</h6>
                    <p class="mb-0">
                        For immediate safety or electrical hazards, visit the campus administration block directly quoting your reference ID <strong><?= e($complaint['complaint_number']) ?></strong>.
                    </p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
