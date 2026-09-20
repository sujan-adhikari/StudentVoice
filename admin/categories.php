<?php
/**
 * College Complaint Management System
 * Category Management (Full CRUD Operation)
 * 
 * Concept: Complete Create, Read, Update, and Delete example.
 * Essential for demonstrating relational database management and 
 * foreign key integrity constraints in a BCA viva.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$errors = [];

// Track if an existing category is being edited
$editCategory = null;
$editId = filter_var($_GET['edit_id'] ?? 0, FILTER_VALIDATE_INT);
if ($editId) {
    $editStmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
    $editStmt->execute([':id' => $editId]);
    $editCategory = $editStmt->fetch();
}

// -------------------------------------------------------------------------
// 1. Handle POST: CREATE or UPDATE Category
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    } else {
        $action      = $_POST['action'] ?? 'create';
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } elseif (strlen($name) < 3 || strlen($name) > 100) {
            $errors[] = 'Category name must be between 3 and 100 characters.';
        }

        // Check for duplicate category name
        if (empty($errors)) {
            $checkSql = "SELECT id FROM categories WHERE name = :name";
            $checkParams = [':name' => $name];

            if ($action === 'update') {
                $targetId = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);
                $checkSql .= " AND id != :id";
                $checkParams[':id'] = $targetId;
            }

            $stmtCheck = $pdo->prepare($checkSql);
            $stmtCheck->execute($checkParams);
            if ($stmtCheck->fetch()) {
                $errors[] = "A category named '{$name}' already exists.";
            }
        }

        // Execute CREATE or UPDATE
        if (empty($errors)) {
            try {
                if ($action === 'create') {
                    $insertStmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
                    $insertStmt->execute([
                        ':name'        => $name,
                        ':description' => $description ?: null
                    ]);
                    setFlash('success', "Category '{$name}' created successfully.");
                } elseif ($action === 'update') {
                    $targetId = (int) $_POST['category_id'];
                    $updateStmt = $pdo->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
                    $updateStmt->execute([
                        ':name'        => $name,
                        ':description' => $description ?: null,
                        ':id'          => $targetId
                    ]);
                    setFlash('success', "Category '{$name}' updated successfully.");
                }

                header('Location: ' . BASE_URL . '/admin/categories.php');
                exit;

            } catch (PDOException $e) {
                $errors[] = 'Database operation failed: ' . $e->getMessage();
            }
        }
    }
}

// -------------------------------------------------------------------------
// 2. Handle GET: DELETE Category
// -------------------------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $delId = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    $token = $_GET['token'] ?? '';

    if (!verifyCsrfToken($token)) {
        setFlash('danger', 'Security validation failed. Cannot delete category.');
        header('Location: ' . BASE_URL . '/admin/categories.php');
        exit;
    }

    if ($delId) {
        // Integrity Check: Check if complaints are linked to this category
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE category_id = :id");
        $countStmt->execute([':id' => $delId]);
        $linkedCount = (int) $countStmt->fetchColumn();

        if ($linkedCount > 0) {
            setFlash('danger', "Cannot delete category: {$linkedCount} active complaint(s) are assigned to it. Relational integrity prevents deletion.");
        } else {
            try {
                $delStmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                $delStmt->execute([':id' => $delId]);
                setFlash('success', 'Category deleted successfully.');
            } catch (PDOException $e) {
                setFlash('danger', 'Failed to delete category: ' . $e->getMessage());
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

// -------------------------------------------------------------------------
// 3. READ: Fetch all categories with count of linked complaints
// -------------------------------------------------------------------------
$categories = $pdo->query("
    SELECT cat.*, COUNT(c.id) AS complaint_count
    FROM categories cat
    LEFT JOIN complaints c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY cat.name ASC
")->fetchAll();

$pageTitle = 'Manage Complaint Categories';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="fw-bold mb-1">Campus Complaint Categories</h2>
                <p class="text-muted small mb-0">Create, edit, and manage department categories for student grievances.</p>
            </div>
            <?php if ($editCategory): ?>
                <a href="categories.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Cancel Editing
                </a>
            <?php endif; ?>
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
            <!-- Left Form Column (CREATE or UPDATE) -->
            <div class="col-lg-4">
                <div class="app-card shadow-sm border-0">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi <?= $editCategory ? 'bi-pencil-square text-warning' : 'bi-plus-circle text-primary' ?> me-2"></i>
                            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
                        </h5>
                    </div>
                    <div class="app-card-body p-4">
                        <form method="POST" action="categories.php">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
                            <?php if ($editCategory): ?>
                                <input type="hidden" name="category_id" value="<?= (int)$editCategory['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= e($editCategory['name'] ?? '') ?>" 
                                       placeholder="e.g. Electrical & Generator" required maxlength="100">
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Briefly describe what kind of problems fall under this category..."><?= e($editCategory['description'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn <?= $editCategory ? 'btn-warning text-dark' : 'btn-primary' ?> w-100">
                                <i class="bi <?= $editCategory ? 'bi-check-lg' : 'bi-plus-lg' ?> me-1"></i>
                                <?= $editCategory ? 'Save Changes' : 'Create Category' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border small text-muted mt-3">
                    <strong><i class="bi bi-shield-check text-primary me-1"></i> Referential Integrity Note:</strong>
                    Categories that already have complaints linked cannot be deleted, preventing orphaned foreign keys.
                </div>
            </div>

            <!-- Right Column: READ Categories Table -->
            <div class="col-lg-8">
                <div class="app-card shadow-sm border-0">
                    <div class="app-card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="app-card-title">
                            <i class="bi bi-tags text-primary me-2"></i> Active Categories
                        </h5>
                        <span class="badge bg-secondary"><?= count($categories) ?> Total</span>
                    </div>
                    <div class="app-card-body p-0">
                        <div class="table-responsive">
                            <table class="table app-table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Complaints</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; foreach ($categories as $cat): ?>
                                        <tr class="<?= ($editCategory && $editCategory['id'] == $cat['id']) ? 'table-warning' : '' ?>">
                                            <td class="text-muted small"><?= $i++ ?></td>
                                            <td>
                                                <strong class="text-dark"><?= e($cat['name']) ?></strong>
                                            </td>
                                            <td class="text-muted small" style="max-width: 250px;">
                                                <?= !empty($cat['description']) ? e($cat['description']) : '<span class="fst-italic text-muted">No description</span>' ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary">
                                                    <?= (int)$cat['complaint_count'] ?> assigned
                                                </span>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <a href="categories.php?edit_id=<?= (int)$cat['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Edit Category">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="categories.php?delete_id=<?= (int)$cat['id'] ?>&token=<?= getCsrfToken() ?>" 
                                                   class="btn btn-sm btn-outline-danger ms-1"
                                                   onclick="return confirmAction('Are you sure you want to delete category \'<?= e(addslashes($cat['name'])) ?>\'?');" 
                                                   title="Delete Category">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
