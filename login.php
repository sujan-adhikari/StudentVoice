<?php
/**
 * College Complaint Management System
 * Unified Login Page (Student & Administrator)
 * 
 * Concept: Authenticates credentials against database. Uses password_verify()
 * to check hashed passwords and establishes user session upon success.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect already authenticated users
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'student';
    header('Location: ' . BASE_URL . ($role === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security session. Please reload and try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            try {
                $pdo = getDBConnection();
                
                // Fetch user by email using PDO Prepared Statement
                $stmt = $pdo->prepare("SELECT id, name, email, password, role, phone FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                // Verify password hash using native PHP password_verify()
                if ($user && password_verify($password, $user['password'])) {
                    loginUser($user);
                    setFlash('success', 'Welcome back, ' . $user['name'] . '!');

                    // Role-based redirection
                    if ($user['role'] === 'admin') {
                        header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    } else {
                        header('Location: ' . BASE_URL . '/student/dashboard.php');
                    }
                    exit;
                } else {
                    // Generic error message prevents username enumeration
                    $error = 'Invalid email address or password. Please check your credentials.';
                }

            } catch (PDOException $e) {
                $error = 'Authentication service encountered a database error.';
            }
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            
            <?php displayFlash(); ?>

            <div class="app-card shadow-sm border-0">
                <div class="auth-card-header">
                    <div class="mb-3">
                        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Student Voice" class="auth-logo-img">
                    </div>
                    <h4 class="fw-bold mb-0" style="color: var(--app-primary);">Sign In to Portal</h4>
                </div>

                <div class="app-card-body px-4 pb-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger shadow-sm py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <?php csrfField(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= e($email) ?>" placeholder="e.g. elonmusk@butwalkalika.edu.np" required autofocus>
                            </div>
                            <div class="form-text small text-muted">Use your college .edu / .edu.np email.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">New student?</span>
                        <a href="register.php" class="small fw-bold text-decoration-none ms-1">Create an account</a>
                    </div>
                </div>
            </div>

            <!-- Demo Credentials Helper Card for Viva and Testing -->
            <div class="card mt-4 border-info-subtle bg-light shadow-sm">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="bi bi-info-circle-fill me-1"></i> Demo Credentials for Evaluation
                    </h6>
                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.82rem;">
                        <tbody>
                            <tr>
                                <td><strong>Admin:</strong></td>
                                <td><code>admin@college.edu</code></td>
                                <td><code>admin123</code></td>
                            </tr>
                            <tr>
                                <td><strong>Student:</strong></td>
                                <td><code>student@college.edu</code></td>
                                <td><code>student123</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
