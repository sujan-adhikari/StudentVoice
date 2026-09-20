<?php
/**
 * College Complaint Management System
 * Edit Complaint (UPDATE Operation - Student)
 * 
 * Concept: Demonstrates conditional record updating. Students can update
 * a complaint ONLY while it remains in 'Pending' status.
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

// Fetch complaint verifying ownership
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([':id' => $complaintId, ':user_id' => $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found or unauthorized access.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

// Business Rule: Student can ONLY edit while status is Pending
if ($complaint['status'] !== 'Pending') {
    setFlash('danger', 'Complaints currently in "' . $complaint['status'] . '" status cannot be modified.');
    header('Location: ' . BASE_URL . '/student/view_complaint.php?id=' . $complaintId);
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$errors = [];
$categoryId  = $complaint['category_id'];
$title       = $complaint['title'];
$description = $complaint['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid submission verification. Please refresh the page.';
    }

    $categoryId  = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // 2. Validation
    if (empty($categoryId)) {
        $errors[] = 'Please select a valid category.';
    }

    if (empty($title) || strlen($title) < 5 || strlen($title) > 200) {
        $errors[] = 'Complaint title must be between 5 and 200 characters.';
    }

    if (empty($description) || strlen($description) < 15) {
        $errors[] = 'Description must be at least 15 characters.';
    }

    // 3. Handle optional replacement image
    $newImagePath = null;
    if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $uploadError = null;
        $newImagePath = handleImageUpload($_FILES['image'], $uploadError);
        if ($uploadError) {
            $errors[] = $uploadError;
        }
    }

    // 4. Update Database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $imageToSave = $complaint['image'];
            // If new image uploaded, remove old one from disk
            if ($newImagePath) {
                deleteUploadedFile($complaint['image']);
                $imageToSave = $newImagePath;
            }

            $updateSql = "
                UPDATE complaints 
                SET category_id = :cat_id,
                    title = :title,
                    description = :desc,
                    image = :image,
                    updated_at = NOW()
                WHERE id = :id AND user_id = :user_id AND status = 'Pending'
            ";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':cat_id'  => $categoryId,
                ':title'   => $title,
                ':desc'    => $description,
                ':image'   => $imageToSave,
                ':id'      => $complaintId,
                ':user_id' => $userId
            ]);

            // Log update
            $logSql = "
                INSERT INTO complaint_logs (complaint_id, updated_by, previous_status, new_status, remark)
                VALUES (:cid, :uid, 'Pending', 'Pending', 'Complaint details modified by student.')
            ";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([':cid' => $complaintId, ':uid' => $userId]);

            $pdo->commit();

            setFlash('success', 'Complaint updated successfully.');
            header('Location: ' . BASE_URL . '/student/view_complaint.php?id=' . $complaintId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            if ($newImagePath) {
                deleteUploadedFile($newImagePath);
            }
            $errors[] = 'Failed to update complaint: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Complaint: ' . $complaint['complaint_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="mb-4">
            <a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= (int)$complaint['id'] ?>" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to Complaint Details
            </a>
            <h2 class="fw-bold mb-1 mt-1">
                Edit Complaint <span class="text-primary font-monospace"><?= e($complaint['complaint_number']) ?></span>
            </h2>
            <p class="text-muted small mb-0">Modify information for your pending campus grievance.</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="app-card shadow-sm border-0">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi bi-pencil-square text-primary me-2"></i> Edit Grievance Information
                        </h5>
                    </div>

                    <div class="app-card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger shadow-sm py-2">
                                <ul class="mb-0 small ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="edit_complaint.php?id=<?= (int)$complaint['id'] ?>" enctype="multipart/form-data" novalidate>
                            <?php csrfField(); ?>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['id'] ?>" <?= ((int)$categoryId === (int)$cat['id']) ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    Complaint Subject / Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= e($title) ?>" required maxlength="200">
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="complaint_description" class="form-label mb-0">
                                        Detailed Description <span class="text-danger">*</span>
                                    </label>
                                    <span id="char_counter" class="text-muted small">0 characters entered</span>
                                </div>
                                <textarea class="form-control" id="complaint_description" name="description" 
                                          rows="5" required><?= e($description) ?></textarea>
                            </div>

                            <!-- Current Image Display if available -->
                            <?php if (!empty($complaint['image'])): ?>
                                <div class="mb-3 p-3 bg-light rounded border">
                                    <div class="small fw-semibold text-muted mb-2">Current Attached Evidence:</div>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= BASE_URL ?>/<?= e($complaint['image']) ?>" alt="Evidence" class="evidence-thumbnail">
                                        <span class="small text-muted">You can upload a new photo below to replace this file.</span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <label for="complaint_image_input" class="form-label">
                                    <?= !empty($complaint['image']) ? 'Replace Attached Photo' : 'Attach Photo Evidence' ?> <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="file" class="form-control" id="complaint_image_input" name="image" 
                                       accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text small">Allowed: JPG, JPEG, PNG, WEBP (Max: 2MB).</div>

                                <div id="image_preview_container" class="mt-3 d-none">
                                    <div class="fw-semibold text-muted small mb-1">New Image Preview:</div>
                                    <img id="image_preview" src="#" alt="New Preview" class="evidence-preview-box">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check-lg me-1"></i> Save Changes
                                </button>
                                <a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= (int)$complaint['id'] ?>" class="btn btn-outline-secondary px-3">
                                    Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="p-3 bg-white rounded border shadow-sm small text-muted">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock text-warning me-1"></i> Edit Restrictions</h6>
                    <p class="mb-0">
                        Editing is only permitted while your grievance is marked as <strong>Pending</strong>. Once college staff begins processing your grievance, modifications will be locked.
                    </p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
