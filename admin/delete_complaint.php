<?php
/**
 * College Complaint Management System
 * Admin Delete Complaint (DELETE Operation - Admin)
 * 
 * Concept: Allows authorized administrators to purge inappropriate, duplicate,
 * or test records. Automatically deletes stored image files and cascades log records.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$complaintId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$token       = $_GET['token'] ?? '';

// Verify CSRF Token
if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Security validation failed. Deletion aborted.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

if (!$complaintId) {
    setFlash('danger', 'Invalid complaint identifier.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$pdo = getDBConnection();

// Fetch complaint to get reference and image path
$stmt = $pdo->prepare("SELECT complaint_number, image FROM complaints WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint record not found.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

try {
    // Delete attached file from server disk
    if (!empty($complaint['image'])) {
        deleteUploadedFile($complaint['image']);
    }

    // Delete complaint (foreign key ON DELETE CASCADE automatically clears complaint_logs)
    $delStmt = $pdo->prepare("DELETE FROM complaints WHERE id = :id");
    $delStmt->execute([':id' => $complaintId]);

    setFlash('success', "Complaint {$complaint['complaint_number']} and its audit history have been permanently deleted.");
} catch (PDOException $e) {
    setFlash('danger', 'Failed to delete complaint: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/complaints.php');
exit;
