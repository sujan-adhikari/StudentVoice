<?php
/**
 * Student Voice - College Complaint Management System
 * Public Website Showcase & Grievance Portal Gateway
 * 
 * Concept: The public-facing institutional website that introduces the
 * "Student Voice" platform, explains its purpose, provides public tracking,
 * and routes students and administrators to their respective app portals.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();

// Fetch live statistics
$totalComplaints = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$resolvedComplaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$activeCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

// Public Reference Lookup
$searchedComplaint = null;
$searchError = '';
$searchQuery = trim($_GET['ref'] ?? '');

if (!empty($searchQuery)) {
    $stmt = $pdo->prepare("SELECT c.complaint_number, c.title, c.description, c.status, c.admin_remark, c.created_at, c.updated_at,
                                  cat.name AS category_name
                           FROM complaints c
                           JOIN categories cat ON c.category_id = cat.id
                           WHERE c.complaint_number = :ref LIMIT 1");
    $stmt->execute([':ref' => strtoupper($searchQuery)]);
    $searchedComplaint = $stmt->fetch();

    if (!$searchedComplaint) {
        $searchError = "No complaint found matching reference number '" . e($searchQuery) . "'. Please check the number and try again.";
    }
}

// Fetch active categories
$categories = $pdo->query("SELECT name, description FROM categories ORDER BY name ASC LIMIT 6")->fetchAll();

$pageTitle = APP_NAME . ' | Official College Grievance Portal';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<header class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold text-white mb-3 hero-title">
                    See a Problem?<br>
                    Say Something.
                </h1>
                <p class="lead text-light opacity-90 mb-4" style="max-width: 600px;">
                    Student Voice gives students a simple way to report campus issues, track their complaints, and stay
                    updated.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="register.php" class="btn btn-primary btn-lg px-4 shadow-sm">
                        <i class="bi bi-pencil-square me-2"></i> Lodge a Grievance
                    </a>
                    <a href="#portals-section" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-door-open me-2"></i> Enter Portals
                    </a>
                    <a href="#track-section"
                        class="btn btn-link text-white text-decoration-none d-flex align-items-center">
                        <i class="bi bi-search me-1"></i> Track Reference
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <img src="<?= BASE_URL ?>/assets/images/hero-campus.webp" alt="Campus Facility"
                    class="img-fluid rounded-4 shadow-lg border border-primary border-2"
                    style="max-height: 420px; width: 100%; object-fit: cover;">
            </div>
        </div>
    </div>
</header>

<!-- Portal Gateways Section (Public Website -> App Portals) -->
<section id="portals-section" class="py-5 bg-white border-bottom">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2 fw-semibold text-uppercase">
                Select Your Access Gateway
            </span>
            <h2 class="fw-bold mt-2" style="color: var(--app-primary);">Access the Student Voice Portals</h2>
            <p class="text-muted col-md-6 mx-auto">Choose your respective role to proceed into the secured application
                portal.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Student Portal Gateway Card -->
            <div class="col-md-5">
                <div class="portal-entry-card h-100 d-flex flex-column">
                    <div class="stat-icon-wrapper bg-primary text-white mx-auto mb-3"
                        style="width: 60px; height: 60px; border-radius: 50%;">
                        <i class="bi bi-mortarboard-fill fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Student Portal</h4>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Lodge new complaints with photo evidence, monitor status in real-time, view official admin
                        remarks, and edit pending submissions.
                    </p>
                    <div class="d-grid gap-2">
                        <a href="login.php" class="btn btn-primary py-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Student Portal
                        </a>
                        <a href="register.php" class="btn btn-outline-secondary py-2 small">
                            <i class="bi bi-person-plus me-1"></i> Create Student Account
                        </a>
                    </div>
                </div>
            </div>

            <!-- Administration Gateway Card -->
            <div class="col-md-5">
                <div class="portal-entry-card h-100 d-flex flex-column">
                    <div class="stat-icon-wrapper text-white mx-auto mb-3"
                        style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--app-primary);">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Administrator Portal</h4>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Authorized departmental staff and administrators can manage grievance queues, filter complaints,
                        update statuses, and log resolution remarks.
                    </p>
                    <div class="d-grid gap-2 mt-auto">
                        <a href="login.php" class="btn btn-navy py-2">
                            <i class="bi bi-shield-check me-1"></i> Admin Portal Login
                        </a>
                        <span class="text-muted small py-2">
                            <i class="bi bi-info-circle me-1"></i> Restricted to authorized faculty and IT coordinators
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Student Voice Section -->
<section id="about-section" class="py-5" style="background-color: var(--app-bg);">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="<?= BASE_URL ?>/assets/images/about-college.webp" alt="College Community"
                    class="img-fluid rounded-4 shadow-sm border"
                    style="width: 100%; max-height: 380px; object-fit: cover;">
            </div>
            <div class="col-lg-6">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold text-uppercase">About The
                    Platform</span>
                <h2 class="fw-bold mt-2 mb-3" style="color: var(--app-primary);">Transforming College Grievance
                    Redressal</h2>
                <p class="text-muted mb-4" style="line-height: 1.7;">
                    <strong>Student Voice</strong> is developed under the <strong>Tribhuvan University BCA
                        program</strong>
                    to replace outdated paper complaints with an accountable digital process. By digitizing the
                    workflow, students gain
                    total transparency over their issues while college administrators receive organized data to resolve
                    campus bottlenecks promptly.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-camera-fill text-primary fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Visual Evidence</h6>
                                <p class="text-muted small mb-0">Upload photos of broken equipment, lab errors, or water
                                    leakages.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-clock-history text-primary fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Activity Timeline</h6>
                                <p class="text-muted small mb-0">Track each step from review to technician dispatch and
                                    final fix.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Public Complaint Quick Tracker Section -->
<section id="track-section" class="py-5 bg-white border-top border-bottom">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="app-card shadow-sm border">
                    <div class="app-card-body p-4 text-center">
                        <div class="stat-icon-wrapper bg-primary-subtle text-primary mx-auto mb-3">
                            <i class="bi bi-search fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-2" style="color: var(--app-primary);">Public Complaint Status Tracker</h3>
                        <p class="text-muted small mb-4">Enter any complaint reference number (e.g.
                            <code>CMP-2026-0001</code>) to check immediate status without logging in.
                        </p>

                        <form method="GET" action="index.php#track-section" class="row g-2 justify-content-center">
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-ticket-detailed"></i></span>
                                    <input type="text" name="ref" class="form-control text-uppercase"
                                        placeholder="Enter CMP-YYYY-XXXX" value="<?= e($searchQuery) ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Track Status
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($searchError)): ?>
                            <div class="alert alert-warning mt-4 text-start small mb-0">
                                <i class="bi bi-exclamation-circle me-2"></i> <?= e($searchError) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($searchedComplaint): ?>
                            <div class="card border-primary-subtle bg-white text-start mt-4 shadow-sm">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <span
                                        class="fw-bold font-monospace text-primary fs-6"><?= e($searchedComplaint['complaint_number']) ?></span>
                                    <?= getStatusBadge($searchedComplaint['status']) ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title fw-bold mb-2"><?= e($searchedComplaint['title']) ?></h5>
                                    <div class="text-muted small mb-3">
                                        <span class="me-3"><i class="bi bi-tag me-1"></i> Category:
                                            <strong><?= e($searchedComplaint['category_name']) ?></strong></span>
                                        <span><i class="bi bi-calendar-event me-1"></i> Submitted:
                                            <?= formatDate($searchedComplaint['created_at']) ?></span>
                                    </div>

                                    <?php if (!empty($searchedComplaint['admin_remark'])): ?>
                                        <div class="p-3 bg-light rounded border border-info-subtle mt-2">
                                            <div class="fw-bold text-dark small mb-1">
                                                <i class="bi bi-chat-left-quote text-primary me-1"></i> Official Administration
                                                Remark:
                                            </div>
                                            <p class="mb-0 small text-secondary">
                                                <?= nl2br(e($searchedComplaint['admin_remark'])) ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-2 bg-light rounded small text-muted">
                                            <i class="bi bi-hourglass-split me-1"></i> Grievance is currently awaiting
                                            administrative review.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Active Complaint Categories Grid -->
<section class="py-5" style="background-color: var(--app-bg);">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 fw-semibold text-uppercase">Campus
                Departments</span>
            <h2 class="fw-bold mt-2" style="color: var(--app-primary);">Supported Complaint Categories</h2>
            <p class="text-muted">Select departments where issues can be officially lodged</p>
        </div>

        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-md-4">
                    <div class="app-card h-100 p-4 border">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="bi bi-building me-2"></i><?= e($cat['name']) ?>
                        </h6>
                        <p class="text-muted small mb-0"><?= e($cat['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="register.php" class="btn btn-primary px-4 py-2">
                <i class="bi bi-arrow-right-circle me-1"></i> Register to Submit a Grievance
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>