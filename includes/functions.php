<?php
/**
 * College Complaint Management System
 * Core Helper Functions
 * 
 * Concept: Reusable helper functions that handle common tasks across all pages:
 * - HTML escaping to prevent Cross-Site Scripting (XSS)
 * - Session flash message alerts (Success/Error)
 * - Human-friendly complaint reference number generation
 * - CSRF token creation & verification
 * - Image validation and file upload handling
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Escapes output strings safely for rendering in HTML.
 * Defends against Cross-Site Scripting (XSS) attacks.
 * 
 * @param string|null $string
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sets a flash notification message in the session.
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Renders and clears any flash message stored in session.
 */
function displayFlash(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        $type = e($flash['type']);
        $message = e($flash['message']);

        // Bootstrap alert with dismiss button
        echo "
        <div class=\"alert alert-{$type} alert-dismissible fade show shadow-sm\" role=\"alert\">
            <div class=\"d-flex align-items-center\">
                <span class=\"me-2\">" . ($type === 'success' ? '✓' : 'ℹ') . "</span>
                <div>{$message}</div>
            </div>
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
        </div>";
    }
}

/**
 * Generates or retrieves current CSRF token from the session.
 * Protects forms against Cross-Site Request Forgery.
 * 
 * @return string
 */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders a hidden CSRF token input field for forms.
 */
function csrfField(): void {
    $token = getCsrfToken();
    echo "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
}

/**
 * Verifies if submitted CSRF token matches session token.
 * 
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generates a unique, friendly complaint reference number: CMP-YYYY-XXXX
 * Example: CMP-2026-0001
 * 
 * @param PDO $pdo
 * @return string
 */
function generateComplaintNumber(PDO $pdo): string {
    $currentYear = date('Y');
    
    // Count complaints lodged in the current year to determine sequential index
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE complaint_number LIKE :prefix");
    $stmt->execute([':prefix' => "CMP-{$currentYear}-%"]);
    $count = (int) $stmt->fetchColumn();

    $nextNumber = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    $reference = "CMP-{$currentYear}-{$nextNumber}";

    // Extra check to ensure uniqueness in case of concurrent submissions
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE complaint_number = :ref");
    $stmtCheck->execute([':ref' => $reference]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        // Fallback: append random 2 digits if duplicate exists
        $reference = "CMP-{$currentYear}-" . str_pad($count + mt_rand(2, 99), 4, '0', STR_PAD_LEFT);
    }

    return $reference;
}

/**
 * Returns a styled HTML badge for complaint status.
 * 
 * @param string $status
 * @return string
 */
function getStatusBadge(string $status): string {
    switch ($status) {
        case 'Pending':
            return '<span class="badge badge-status-pending px-2 py-1"><i class="bi bi-clock me-1"></i>Pending</span>';
        case 'In Progress':
            return '<span class="badge badge-status-progress px-2 py-1"><i class="bi bi-gear-wide-connected me-1"></i>In Progress</span>';
        case 'Resolved':
            return '<span class="badge badge-status-resolved px-2 py-1"><i class="bi bi-check-circle me-1"></i>Resolved</span>';
        case 'Rejected':
            return '<span class="badge badge-status-rejected px-2 py-1"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
        default:
            return '<span class="badge bg-secondary px-2 py-1">' . e($status) . '</span>';
    }
}


/**
 * Validates and saves an uploaded evidence image.
 * 
 * @param array $file $_FILES['image']
 * @param string|null &$errorMessage Output variable for any validation error
 * @return string|null Returns the saved relative file path, or null if no file or failed
 */
function handleImageUpload(array $file, ?string &$errorMessage = null): ?string {
    // If no file was uploaded
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Check basic upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Error during file upload. Code: " . $file['error'];
        return null;
    }

    // Check file size limit (2MB max)
    if ($file['size'] > MAX_FILE_SIZE) {
        $errorMessage = "The uploaded image exceeds the 2 MB maximum size limit.";
        return null;
    }

    // Validate extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
        $errorMessage = "Invalid image extension. Only JPG, JPEG, PNG, and WEBP files are allowed.";
        return null;
    }

    // Validate MIME type using PHP Fileinfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        $errorMessage = "Invalid file type detected ({$mimeType}). Please upload a genuine image.";
        return null;
    }

    // Ensure uploads directory exists
    if (!is_dir(UPLOADS_PATH)) {
        mkdir(UPLOADS_PATH, 0755, true);
    }

    // Generate a secure, unique filename to prevent overwriting or directory traversal
    $uniqueName = 'evidence_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = UPLOADS_PATH . DIRECTORY_SEPARATOR . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $uniqueName;
    }

    $errorMessage = "Could not save the uploaded file to server storage.";
    return null;
}

/**
 * Safely unlinks an uploaded file if it exists.
 * 
 * @param string|null $relativePath
 */
function deleteUploadedFile(?string $relativePath): void {
    if (empty($relativePath)) {
        return;
    }
    $fullPath = ROOT_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    if (file_exists($fullPath) && is_file($fullPath)) {
        unlink($fullPath);
    }
}

/**
 * Formats datetime into a clean, human-readable format.
 * 
 * @param string|null $datetime
 * @param bool $withTime
 * @return string
 */
function formatDate(?string $datetime, bool $withTime = true): string {
    if (empty($datetime)) {
        return 'N/A';
    }
    $timestamp = strtotime($datetime);
    if ($withTime) {
        return date('M d, Y h:i A', $timestamp);
    }
    return date('M d, Y', $timestamp);
}

/**
 * Resolves an asset image path by checking extensions (.webp, .png, .jpg, .jpeg).
 * Ensures images display regardless of whether the user saves them as webp, png, or jpg.
 * 
 * @param string $baseName File name without extension (e.g. 'hero-campus', 'logo', 'about-college')
 * @param string $fallbackExtension Default extension to assume if not found
 * @return string Relative web path (e.g. 'assets/images/hero-campus.webp')
 */
function getAssetImage(string $baseName, string $fallbackExtension = 'webp'): string {
    $extensions = ['webp', 'png', 'jpg', 'jpeg', 'svg'];
    foreach ($extensions as $ext) {
        $relPath = 'assets/images/' . $baseName . '.' . $ext;
        $fullPath = ROOT_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
        if (file_exists($fullPath)) {
            return $relPath;
        }
    }
    return 'assets/images/' . $baseName . '.' . $fallbackExtension;
}

