<?php
/**
 * Student Voice - College Complaint Management System
 * Global Responsive Navigation Bar
 */
$currentUser = currentUser();
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top shadow-sm">
    <div class="container-fluid px-lg-4">
        <!-- Logo acts as Home -->
        <a class="navbar-brand py-1 d-flex align-items-center" href="<?= BASE_URL ?>/index.php" title="Home" aria-label="Student Voice - Home">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Student Voice - Home" class="brand-logo-img">
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Nav links without icons (Home removed - logo acts as Home) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#about-section">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#track-section">Track Complaint</a>
                </li>
                <?php if ($currentUser): ?>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/admin/dashboard.php">
                                Admin Portal
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/student/dashboard.php">
                                Student Portal
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- Authentication User Actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= BASE_URL ?>/assets/images/avatar-default.png" alt="Avatar" class="rounded-circle me-2" style="width: 24px; height: 24px; object-fit: cover;">
                            <span class="text-dark fw-medium"><?= e($currentUser['name']) ?></span>
                            <span class="badge ms-2 <?= $currentUser['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                <?= strtoupper(e($currentUser['role'])) ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <?php if ($currentUser['role'] === 'student'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/student/dashboard.php">
                                        <i class="bi bi-grid-1x2 me-2"></i> My Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/student/profile.php">
                                        <i class="bi bi-person me-2"></i> My Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">
                                        <i class="bi bi-speedometer2 me-2"></i> Admin Panel
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-secondary navbar-auth-btn">
                        Login
                    </a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary navbar-auth-btn">
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
