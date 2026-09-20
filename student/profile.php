<?php
/**
 * College Complaint Management System
 * Student Profile & Password Settings
 * 
 * Concept: Demonstrates user profile viewing and updating personal records
 * including password change with secure verification.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = currentUser();
$userId = (int) $user['id'];
$pdo = getDBConnection();

// Fetch fresh user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$userData = $stmt->fetch();

$profileErrors = [];
$passwordErrors = [];

// Handle Profile Info Update (Phone Number)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $profileErrors[] = 'Security token invalid. Please reload.';
    } else {
        $phone = trim($_POST['phone'] ?? '');
        if (!empty($phone) && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
            $profileErrors[] = 'Please enter a valid phone number.';
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET phone = :phone WHERE id = :id");
            $updateStmt->execute([':phone' => $phone ?: null, ':id' => $userId]);
            
            $_SESSION['user_phone'] = $phone;
            setFlash('success', 'Profile information updated successfully.');
            header('Location: ' . BASE_URL . '/student/profile.php');
            exit;
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $passwordErrors[] = 'Security token invalid. Please reload.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $passwordErrors[] = 'Please enter your current password.';
        } elseif (!password_verify($currentPassword, $userData['password'])) {
            $passwordErrors[] = 'Your current password does not match our records.';
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters long.';
        }

        if ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'New password and confirmation do not match.';
        }

        if (empty($passwordErrors)) {
            $newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $pwdStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $pwdStmt->execute([':password' => $newHashed, ':id' => $userId]);

            setFlash('success', 'Password changed successfully.');
            header('Location: ' . BASE_URL . '/student/profile.php');
            exit;
        }
    }
}

// Total complaints count for this student
$complaintCount = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = :uid");
$complaintCount->execute([':uid' => $userId]);
$totalSubmitted = (int) $complaintCount->fetchColumn();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Student Profile & Settings</h2>
            <p class="text-muted small mb-0">View your registration profile and manage your security password.</p>
        </div>

        <?php displayFlash(); ?>

        <div class="row g-4">
            <!-- Profile Info Panel -->
            <div class="col-lg-6">
                <div class="app-card shadow-sm border-0 h-100">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi bi-person-lines-fill text-primary me-2"></i> Account Details
                        </h5>
                    </div>
                    <div class="app-card-body p-4">
                        <?php if (!empty($profileErrors)): ?>
                            <div class="alert alert-danger shadow-sm py-2 small">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($profileErrors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex align-items-center mb-4">
                            <img src="<?= BASE_URL ?>/assets/images/avatar-default.png" alt="User Avatar" 
                                 class="rounded-circle me-3 border" style="width: 56px; height: 56px; object-fit: cover;">
                            <div>
                                <h5 class="fw-bold mb-0"><?= e($userData['name']) ?></h5>
                                <span class="badge bg-primary-subtle text-primary">BCA Student</span>
                                <span class="text-muted small ms-2">Member since <?= formatDate($userData['created_at'], false) ?></span>
                            </div>
                        </div>

                        <form method="POST" action="profile.php">
                            <?php csrfField(); ?>
                            <input type="hidden" name="update_profile" value="1">

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control bg-light" value="<?= e($userData['name']) ?>" readonly>
                                <div class="form-text small">Student names are locked to match official college records.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">College Email Address</label>
                                <input type="email" class="form-control bg-light" value="<?= e($userData['email']) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?= e($userData['phone']) ?>" placeholder="e.g. 98XXXXXXXX">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Total Lodged Complaints</label>
                                <input type="text" class="form-control bg-light" value="<?= $totalSubmitted ?> complaint(s) submitted" readonly>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Update Contact Info
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Password Change Panel -->
            <div class="col-lg-6">
                <div class="app-card shadow-sm border-0 h-100">
                    <div class="app-card-header bg-white">
                        <h5 class="app-card-title">
                            <i class="bi bi-shield-lock text-primary me-2"></i> Change Password
                        </h5>
                    </div>
                    <div class="app-card-body p-4">
                        <?php if (!empty($passwordErrors)): ?>
                            <div class="alert alert-danger shadow-sm py-2 small">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($passwordErrors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="profile.php">
                            <?php csrfField(); ?>
                            <input type="hidden" name="change_password" value="1">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="Minimum 6 characters" required>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Re-type new password" required>
                            </div>

                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-key-fill me-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
