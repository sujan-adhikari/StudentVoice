<?php
/**
 * College Complaint Management System
 * Logout Handler
 * 
 * Concept: Clears session data, unsets cookie, and safely redirects to login page.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();

setFlash('info', 'You have been successfully logged out.');
header('Location: ' . BASE_URL . '/login.php');
exit;
