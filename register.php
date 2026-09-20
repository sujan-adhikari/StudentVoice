<?php
/**
 * College Complaint Management System
 * Student Registration Page
 * 
 * Concept: Demonstrates user registration (CREATE operation in users table).
 * Form inputs are validated on the server, passwords hashed securely,
 * and duplicate emails rejected gracefully.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'student';
    header('Location: ' . BASE_URL . ($role === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request verification. Please refresh and try again.';
    }

    // 2. Sanitize and retrieve POST input
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // 3. Validation Rules
    if (empty($name)) {
        $errors[] = 'Full Name is required.';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Full Name must be at least 3 characters long.';
    }

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Only college emails allowed: must end with .edu or .edu.np
        // Example: ramesh@butwal-kalika.edu.np, sita@anycollege.edu
        $emailLower = strtolower($email);
        $endsWithEdu = substr($emailLower, -4) === '.edu';
        $endsWithEduNp = substr($emailLower, -7) === '.edu.np';
        if (!$endsWithEdu && !$endsWithEduNp) {
            $errors[] = 'Only college emails allowed (must end with .edu or .edu.np).';
        }
    }

    if (!empty($phone) && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Password and Confirm Password do not match.';
    }

    // 4. Check if email already exists in users table
    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered. Please login instead.';
        }
    }

    // 5. Insert new student into database
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            // Hash password securely with Bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO users (name, email, password, role, phone) 
                    VALUES (:name, :email, :password, 'student', :phone)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashedPassword,
                ':phone'    => $phone ?: null
            ]);

            setFlash('success', 'Registration successful! You can now log in to submit complaints.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Registration failed due to a database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="app-card shadow-sm border-0">
                <div class="auth-card-header">
                    <div class="mb-3">
                        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Student Voice" class="auth-logo-img">
                    </div>
                    <h4 class="fw-bold mb-0" style="color: var(--app-primary);">Student Registration</h4>
                </div>

                <div class="app-card-body px-4 pb-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm py-2">
                            <ul class="mb-0 small ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" novalidate>
                        <?php csrfField(); ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= e($name) ?>" placeholder="e.g. Ramesh Thapa" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">College Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= e($email) ?>" placeholder="e.g. ramesh@butwal-kalika.edu.np" required>
                            </div>
                            <div class="form-text small text-muted">Only .edu or .edu.np emails allowed.</div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?= e($phone) ?>" placeholder="e.g. 98XXXXXXXX">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Minimum 6 characters" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Re-enter your password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Register as Student
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">Already have an account?</span>
                        <a href="login.php" class="small fw-bold text-decoration-none ms-1">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
