<?php
/**
 * College Complaint Management System
 * Submit Complaint (CREATE Operation)
 * 
 * Concept: Demonstrates handling multipart form data (text + image upload).
 * Performs server-side validation, sanitizes inputs, handles file upload securely,
 * and writes records using PDO Transactions.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = currentUser();
$userId = (int) $user['id'];
$pdo = getDBConnection();

// Fetch active categories for dropdown
$categoriesStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll();

$errors = [];
$categoryId = '';
$title = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid submission verification. Please refresh the page.';
    }

    // 2. Retrieve and clean inputs
    $categoryId  = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // 3. Validation
    if (empty($categoryId)) {
        $errors[] = 'Please select a valid complaint category.';
    }

    if (empty($title)) {
        $errors[] = 'Please enter a clear title/subject for the issue.';
    } elseif (strlen($title) < 5 || strlen($title) > 200) {
        $errors[] = 'Complaint title must be between 5 and 200 characters.';
    }

    if (empty($description)) {
        $errors[] = 'Please provide a detailed description of the problem.';
    } elseif (strlen($description) < 15) {
        $errors[] = 'Description should be at least 15 characters to explain the problem adequately.';
    }

    // 4. Handle optional image upload
    $imagePath = null;
    if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $uploadError = null;
        $imagePath = handleImageUpload($_FILES['image'], $uploadError);
        if ($uploadError) {
            $errors[] = $uploadError;
        }
    }

    // 5. Database Insert using Transaction
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Generate user-friendly reference: CMP-2026-0001
            $complaintNumber = generateComplaintNumber($pdo);

            $insertSql = "
                INSERT INTO complaints (complaint_number, user_id, category_id, title, description, image, status)
                VALUES (:complaint_number, :user_id, :category_id, :title, :description, :image, 'Pending')
            ";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':complaint_number' => $complaintNumber,
                ':user_id'          => $userId,
                ':category_id'      => $categoryId,
                ':title'            => $title,
                ':description'      => $description,
                ':image'            => $imagePath
            ]);

            $newComplaintId = (int) $pdo->lastInsertId();

            // Record initial history log entry
            $logSql = "
                INSERT INTO complaint_logs (complaint_id, updated_by, previous_status, new_status, remark)
                VALUES (:complaint_id, :updated_by, NULL, 'Pending', 'Complaint registered by student.')
            ";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([
                ':complaint_id' => $newComplaintId,
                ':updated_by'   => $userId
            ]);

            // Commit transaction
            $pdo->commit();

            setFlash('success', "Complaint submitted successfully! Your tracking reference is {$complaintNumber}.");
            header('Location: ' . BASE_URL . '/student/my_complaints.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            // If image was uploaded before DB error, clean it up
            if ($imagePath) {
                deleteUploadedFile($imagePath);
            }
            $errors[] = 'Failed to submit complaint: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Lodge New Complaint';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Lodge a New Complaint</h2>
            <p class="text-muted small mb-0">Fill in the grievance details accurately. College authorities will investigate and update you.</p>
        </div>

        <?php displayFlash(); ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="app-card shadow-sm border-0">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi bi-pencil-square text-primary me-2"></i> Complaint Details Form
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

                        <form method="POST" action="submit_complaint.php" enctype="multipart/form-data" novalidate>
                            <?php csrfField(); ?>

                            <!-- Category Selection -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">
                                    Department / Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- Choose Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['id'] ?>" <?= ((int)$categoryId === (int)$cat['id']) ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text small">Select the campus area or department where the problem exists.</div>
                            </div>

                            <!-- Complaint Title -->
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    Complaint Subject / Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= e($title) ?>" placeholder="e.g. Projector in BCA Room 202 is flickering" 
                                       maxlength="200" required>
                                <div class="form-text small">Be specific and brief (5 to 200 characters).</div>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="complaint_description" class="form-label mb-0">
                                        Detailed Description <span class="text-danger">*</span>
                                    </label>
                                    <span id="char_counter" class="text-muted small">0 characters entered</span>
                                </div>
                                <textarea class="form-control" id="complaint_description" name="description" 
                                          rows="5" placeholder="Describe the problem in detail: room number, affected computers/equipment, time of occurrence, etc." required><?= e($description) ?></textarea>
                            </div>

                            <!-- Optional Image Evidence -->
                            <div class="mb-4">
                                <label for="complaint_image_input" class="form-label">
                                    Attach Photo Evidence <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="file" class="form-control" id="complaint_image_input" name="image" 
                                       accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text small">
                                    Allowed formats: <strong>JPG, JPEG, PNG, WEBP</strong>. Maximum size: <strong>2 MB</strong>.
                                </div>

                                <!-- Live Image Preview Area -->
                                <div id="image_preview_container" class="mt-3 d-none">
                                    <div class="fw-semibold text-muted small mb-1">Selected Image Preview:</div>
                                    <img id="image_preview" src="#" alt="Evidence Preview" class="evidence-preview-box">
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-send-fill me-1"></i> Submit Complaint
                                </button>
                                <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline-secondary px-3">
                                    Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Guidelines Sidebar -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="app-card shadow-sm border-0 mb-3">
                    <div class="app-card-header bg-white">
                        <h6 class="app-card-title small fw-bold text-uppercase text-muted">
                            <i class="bi bi-info-circle text-primary me-1"></i> Submission Guidelines
                        </h6>
                    </div>
                    <div class="app-card-body small text-muted">
                        <ul class="ps-3 mb-0">
                            <li class="mb-2"><strong>Be Respectful:</strong> Maintain academic and professional language in your description.</li>
                            <li class="mb-2"><strong>Exact Locations:</strong> Mention classroom numbers, lab stations, or hostel room numbers clearly.</li>
                            <li class="mb-2"><strong>Evidence Helps:</strong> A clear photo of broken equipment or error messages speeds up resolution.</li>
                            <li><strong>Tracking:</strong> You can track live status updates from your dashboard at any time.</li>
                        </ul>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border small text-muted">
                    <strong>Note:</strong> False or defamatory complaints may result in disciplinary review under college regulations.
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
