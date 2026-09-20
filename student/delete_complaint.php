<?php
/**
 * College Complaint Management System
 * Delete Complaint (DELETE Operation - Student)
 * 
 * Concept: Demonstrates conditional deletion with file cleanup.
 * Students can only delete a complaint if it is still 'Pending'.
 * Token verification prevents unauthorized CSRF deletion attacks.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = currentUser();
$userId = (int) $user['id'];

$complaintId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$token       = $_GET['token'] ?? '';

// Verify CSRF Token
if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Security validation failed. Deletion cancelled.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

if (!$complaintId) {
    setFlash('danger', 'Invalid complaint identifier.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

$pdo = getDBConnection();

// Fetch complaint verifying ownership and status
$stmt = $pdo->prepare("SELECT id, complaint_number, image, status FROM complaints WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([':id' => $complaintId, ':user_id' => $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found or unauthorized access.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

// Business Rule: Only Pending complaints can be deleted
if ($complaint['status'] !== 'Pending') {
    setFlash('danger', 'Cannot delete complaint in "' . $complaint['status'] . '" status.');
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

try {
    // Delete attached image file if one was saved
    if (!empty($complaint['image'])) {
        deleteUploadedFile($complaint['image']);
    }

    // Execute DELETE statement (foreign keys cascade will remove complaint_logs)
    $delStmt = $pdo->prepare("DELETE FROM complaints WHERE id = :id AND user_id = :user_id AND status = 'Pending'");
    $delStmt->execute([':id' => $complaintId, ':user_id' => $userId]);

    setFlash('success', 'Complaint ' . $complaint['complaint_number'] . ' has been deleted successfully.');
} catch (PDOException $e) {
    setFlash('danger', 'Database error while deleting complaint: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/student/my_complaints.php');
exit;
