<?php
/**
 * College Complaint Management System
 * Admin Review & Status Update (UPDATE Operation - Admin)
 * 
 * Concept: Demonstrates administrative grievance processing.
 * Admins can change status (Pending -> In Progress -> Resolved/Rejected),
 * add remarks, and log every state transition into the complaint_logs table.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$admin = currentUser();
$adminId = (int) $admin['id'];
$complaintId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$complaintId) {
    setFlash('danger', 'Invalid complaint reference.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$pdo = getDBConnection();

// Fetch complaint with student and category details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name AS category_name,
           u.name AS student_name, u.email AS student_email, u.phone AS student_phone
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    WHERE c.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint record not found.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$errors = [];

// Handle Admin Status & Remark Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please refresh the page.';
    } else {
        $newStatus   = trim($_POST['status'] ?? '');
        $adminRemark = trim($_POST['admin_remark'] ?? '');

        // Validation
        if (!in_array($newStatus, ['Pending', 'In Progress', 'Resolved', 'Rejected'], true)) {
            $errors[] = 'Please select a valid grievance status.';
        }

        if (empty($adminRemark)) {
            $errors[] = 'Please provide an administrative remark explaining the status change or action taken.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $previousStatus = $complaint['status'];

                // 1. Update Complaint Record
                $updateStmt = $pdo->prepare("
                    UPDATE complaints 
                    SET status = :status,
                        admin_remark = :remark,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':status' => $newStatus,
                    ':remark' => $adminRemark,
                    ':id'     => $complaintId
                ]);

                // 2. Insert Timeline History Log
                $logStmt = $pdo->prepare("
                    INSERT INTO complaint_logs (complaint_id, updated_by, previous_status, new_status, remark)
                    VALUES (:cid, :admin_id, :prev_status, :new_status, :remark)
                ");
                $logStmt->execute([
                    ':cid'         => $complaintId,
                    ':admin_id'    => $adminId,
                    ':prev_status' => $previousStatus,
                    ':new_status'  => $newStatus,
                    ':remark'      => $adminRemark
                ]);

                $pdo->commit();

                setFlash('success', "Complaint status successfully updated to '{$newStatus}'.");
                header('Location: ' . BASE_URL . '/admin/view_complaint.php?id=' . $complaintId);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Database error while saving updates: ' . $e->getMessage();
            }
        }
    }
}

// Fetch complaint history logs
$logsStmt = $pdo->prepare("
    SELECT l.*, u.name AS updater_name, u.role AS updater_role
    FROM complaint_logs l
    JOIN users u ON l.updated_by = u.id
    WHERE l.complaint_id = :complaint_id
    ORDER BY l.created_at ASC
");
$logsStmt->execute([':complaint_id' => $complaintId]);
$logs = $logsStmt->fetchAll();

$pageTitle = 'Review Grievance ' . $complaint['complaint_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Back to Complaints Directory
                </a>
                <h2 class="fw-bold mb-0 mt-1">
                    Grievance Review <span class="text-primary font-monospace"><?= e($complaint['complaint_number']) ?></span>
                </h2>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="<?= BASE_URL ?>/admin/delete_complaint.php?id=<?= (int)$complaint['id'] ?>&token=<?= getCsrfToken() ?>" 
                   class="btn btn-outline-danger"
                   onclick="return confirmAction('Are you sure you want to permanently delete this complaint and all its logs?');">
                    <i class="bi bi-trash me-1"></i> Delete Grievance
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm py-2 mb-4">
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Details & Action Form -->
            <div class="col-lg-7">
                
                <!-- Grievance Information Card -->
                <div class="app-card shadow-sm border-0 mb-4">
                    <div class="app-card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-secondary border me-2">
                                <i class="bi bi-building me-1"></i> <?= e($complaint['category_name']) ?>
                            </span>
                            <span class="text-muted small">Submitted on <?= formatDate($complaint['created_at']) ?></span>
                        </div>
                        <div><?= getStatusBadge($complaint['status']) ?></div>
                    </div>

                    <div class="app-card-body p-4">
                        <h4 class="fw-bold mb-3"><?= e($complaint['title']) ?></h4>

                        <div class="text-muted small fw-semibold text-uppercase mb-1">Student Description:</div>
                        <div class="p-3 bg-light rounded border mb-4" style="line-height: 1.6; white-space: pre-line;">
                            <?= e($complaint['description']) ?>
                        </div>

                        <?php if (!empty($complaint['image'])): ?>
                            <div class="mb-3">
                                <div class="text-muted small fw-semibold text-uppercase mb-2">
                                    <i class="bi bi-image me-1"></i> Attached Photographic Evidence:
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>/<?= e($complaint['image']) ?>" target="_blank">
                                        <img src="<?= BASE_URL ?>/<?= e($complaint['image']) ?>" 
                                             alt="Attached Evidence" class="evidence-preview-box shadow-sm">
                                    </a>
                                </div>
                                <div class="form-text small mt-1">Click to inspect image in full resolution.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Admin Action & Resolution Form Card -->
                <div class="app-card shadow-sm border-0 border-top border-primary border-3 mb-4">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi bi-gear-fill text-primary me-2"></i> Update Status & Post Official Remark
                        </h5>
                    </div>

                    <div class="app-card-body p-4">
                        <form method="POST" action="view_complaint.php?id=<?= (int)$complaint['id'] ?>">
                            <?php csrfField(); ?>

                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    Change Grievance Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Pending" <?= ($complaint['status'] === 'Pending') ? 'selected' : '' ?>>
                                        Pending (Awaiting investigation)
                                    </option>
                                    <option value="In Progress" <?= ($complaint['status'] === 'In Progress') ? 'selected' : '' ?>>
                                        In Progress (Assigned to department / technician)
                                    </option>
                                    <option value="Resolved" <?= ($complaint['status'] === 'Resolved') ? 'selected' : '' ?>>
                                        Resolved (Issue fixed & verified)
                                    </option>
                                    <option value="Rejected" <?= ($complaint['status'] === 'Rejected') ? 'selected' : '' ?>>
                                        Rejected (Invalid, duplicate, or against policy)
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="admin_remark" class="form-label">
                                    Official Remark / Resolution Note <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="admin_remark" name="admin_remark" rows="4" 
                                          placeholder="Explain the findings, actions taken, or reason for resolution/rejection..." required><?= e($complaint['admin_remark']) ?></textarea>
                                <div class="form-text small">This remark will be visible to the student on their portal.</div>
                            </div>

                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i> Save Status & Remark
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Right Column: Student Info & Resolution Timeline -->
            <div class="col-lg-5">
                
                <!-- Student Profile Card -->
                <div class="app-card shadow-sm border-0 mb-4">
                    <div class="app-card-header bg-white">
                        <h6 class="app-card-title small fw-bold text-uppercase text-muted">
                            <i class="bi bi-person-badge me-1"></i> Student Information
                        </h6>
                    </div>
                    <div class="app-card-body p-3">
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Full Name:</td>
                                    <td class="fw-bold text-dark"><?= e($complaint['student_name']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email:</td>
                                    <td>
                                        <a href="mailto:<?= e($complaint['student_email']) ?>" class="text-decoration-none">
                                            <?= e($complaint['student_email']) ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone:</td>
                                    <td><?= !empty($complaint['student_phone']) ? e($complaint['student_phone']) : '<span class="text-muted">Not provided</span>' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Last Active:</td>
                                    <td><?= formatDate($complaint['updated_at']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Timeline / Audit Trail -->
                <div class="app-card shadow-sm border-0">
                    <div class="app-card-header bg-white">
                        <h6 class="app-card-title small fw-bold text-uppercase text-muted">
                            <i class="bi bi-clock-history me-1"></i> Action & Audit Timeline
                        </h6>
                    </div>
                    <div class="app-card-body p-4">
                        <?php if (empty($logs)): ?>
                            <p class="text-muted small mb-0">No timeline entries yet.</p>
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
                                                Status: <strong><?= getStatusBadge($log['new_status']) ?></strong>
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
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
