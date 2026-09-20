<?php
/**
 * College Complaint Management System
 * Contextual Dashboard Sidebar (Student / Admin)
 */

$user = currentUser();
$currentScript = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="dashboard-sidebar shadow-sm">
    <?php if ($user && $user['role'] === 'admin'): ?>
        <!-- Administrator Navigation Section -->
        <div class="sidebar-heading">Admin Management</div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && $currentDir === 'admin') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/admin/complaints.php" class="sidebar-link <?= ($currentScript === 'complaints.php' || $currentScript === 'view_complaint.php') ? 'active' : '' ?>">
            <i class="bi bi-card-checklist"></i> All Complaints
        </a>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="sidebar-link <?= ($currentScript === 'categories.php') ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link <?= ($currentScript === 'users.php') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Manage Users
        </a>
    <?php else: ?>
        <!-- Student Navigation Section -->
        <div class="sidebar-heading">Student Portal</div>
        <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && $currentDir === 'student') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Overview
        </a>
        <a href="<?= BASE_URL ?>/student/submit_complaint.php" class="sidebar-link <?= ($currentScript === 'submit_complaint.php') ? 'active' : '' ?>">
            <i class="bi bi-plus-circle"></i> Lodge Complaint
        </a>
        <a href="<?= BASE_URL ?>/student/my_complaints.php" class="sidebar-link <?= (in_array($currentScript, ['my_complaints.php', 'view_complaint.php', 'edit_complaint.php'])) ? 'active' : '' ?>">
            <i class="bi bi-journal-bookmark"></i> My Complaints
        </a>
        <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> Profile Settings
        </a>
    <?php endif; ?>

    <hr class="my-3 text-secondary">

    <!-- Quick Help / Information Box -->
    <div class="p-3 bg-light rounded border border-light-subtle small mt-auto">
        <div class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-primary me-1"></i> CCMS Support</div>
        <p class="text-muted mb-0" style="font-size: 0.78rem;">
            Report campus issues transparently. Track resolution progress in real time.
        </p>
    </div>

    <div class="mt-2">
        <a href="<?= BASE_URL ?>/logout.php" class="sidebar-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</aside>
