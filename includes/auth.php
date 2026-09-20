<?php
/**
 * College Complaint Management System
 * Authentication & Role Access Control Guard
 * 
 * Concept: Session-based authentication. Ensures users can only access
 * the modules authorized for their role (student or admin).
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Automatically start session if not started
if (session_status() === PHP_SESSION_NONE) {
    // Basic session cookie security settings
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Checks whether a user is currently logged in.
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Returns current authenticated user array from session or database.
 * 
 * @return array|null
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'student',
        'phone' => $_SESSION['user_phone'] ?? ''
    ];
}

/**
 * Restricts page access to logged-in users only.
 * Redirects guests to login page.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Restricts page access to a specific role ('student' or 'admin').
 * Prevents students from accessing admin features, and vice versa.
 * 
 * @param string $requiredRole
 */
function requireRole(string $requiredRole): void {
    requireLogin();

    $currentRole = $_SESSION['user_role'] ?? '';
    if ($currentRole !== $requiredRole) {
        setFlash('danger', 'Unauthorized access. You do not have permission to view this page.');
        
        // Redirect to user's rightful dashboard
        if ($currentRole === 'admin') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/student/dashboard.php');
        }
        exit;
    }
}

/**
 * Logs in a user by saving their identity to the PHP session.
 * 
 * @param array $user User record from database
 */
function loginUser(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
}

/**
 * Logs out the user and cleans up the session.
 */
function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie if present
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy session data on server
    session_destroy();
}
