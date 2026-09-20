<?php
/**
 * Student Voice - College Complaint Management System
 * Application Constants & Branding Configurations
 */

// Application Title & Branding
define('APP_NAME', 'Student Voice');
define('APP_TAGLINE', 'College Complaint Management System');
define('APP_SHORT_NAME', 'Student Voice');
define('COLLEGE_NAME', 'Tribhuvan University - BCA Department');

// Absolute filesystem paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config');
define('INCLUDES_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'includes');
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('IMAGES_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images');

// File upload restrictions
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp'
]);

/**
 * Automatically determine the dynamic Base URL whether running inside
 * htdocs/Complaint-Management-System or a virtual host.
 */
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Find script's relative path from web root
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $rootPath = str_replace('\\', '/', ROOT_PATH);
    
    // Compute relative subfolder if running in subdirectory like /Complaint-Management-System
    $subDir = '';
    if (strpos($rootPath, $docRoot) === 0) {
        $subDir = substr($rootPath, strlen($docRoot));
    } else {
        $parts = explode('/', trim($scriptName, '/'));
        if (!empty($parts) && $parts[0] !== '' && !preg_match('/\.php$/', $parts[0])) {
            $subDir = '/' . $parts[0];
        }
    }
    
    $subDir = rtrim($subDir, '/');
    define('BASE_URL', $protocol . $host . $subDir);
}
