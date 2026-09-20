<?php
/**
 * College Complaint Management System
 * Administrator Dashboard
 * 
 * Concept: High-level overview of all college grievances.
 * Calculates aggregate statistics and renders a lightweight Chart.js chart.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$admin = currentUser();
$pdo = getDBConnection();

// Fetch system-wide metrics
$metrics = $pdo->query("
    SELECT 
        COUNT(*) AS total_complaints,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS progress_count,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count
    FROM complaints
")->fetch();

$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Fetch category distribution for Chart.js
$categoryDist = $pdo->query("
    SELECT cat.name, COUNT(c.id) as count
    FROM categories cat
    LEFT JOIN complaints c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY count DESC
    LIMIT 6
")->fetchAll();

$chartLabels = [];
$chartData = [];
foreach ($categoryDist as $cd) {
    $chartLabels[] = $cd['name'];
    $chartData[] = (int) $cd['count'];
}

// Fetch 5 most recent complaints for quick triage
$recentComplaints = $pdo->query("
    SELECT c.id, c.complaint_number, c.title, c.status, c.created_at,
           cat.name AS category_name, u.name AS student_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetchAll();

$includeChartJs = true;
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Top Title Bar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="fw-bold mb-1">Administrative Dashboard</h2>
                <p class="text-muted small mb-0">
                    Welcome, <strong><?= e($admin['name']) ?></strong>. Campus grievance overview and operational metrics.
                </p>
            </div>
            <div class="mt-2 mt-sm-0 d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-primary shadow-sm">
                    <i class="bi bi-card-checklist me-1"></i> Manage All Complaints
                </a>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline-secondary">
                    <i class="bi bi-tags me-1"></i> Categories
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- Key Metrics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-2">
                <div class="stat-card">
                    <div class="stat-title">Total Lodged</div>
                    <div class="stat-value text-dark"><?= (int)$metrics['total_complaints'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-folder2 text-secondary me-1"></i> All Time</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="stat-card border-warning-subtle">
                    <div class="stat-title">Pending</div>
                    <div class="stat-value text-warning"><?= (int)$metrics['pending_count'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-clock-history me-1"></i> Needs Action</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="stat-card border-primary-subtle">
                    <div class="stat-title">In Progress</div>
                    <div class="stat-value text-primary"><?= (int)$metrics['progress_count'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-gear-wide-connected me-1"></i> Under Review</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="stat-card border-success-subtle">
                    <div class="stat-title">Resolved</div>
                    <div class="stat-value text-success"><?= (int)$metrics['resolved_count'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-check2-circle me-1"></i> Completed</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="stat-card border-danger-subtle">
                    <div class="stat-title">Rejected</div>
                    <div class="stat-value text-danger"><?= (int)$metrics['rejected_count'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-x-circle me-1"></i> Closed</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="stat-card border-info-subtle">
                    <div class="stat-title">Students</div>
                    <div class="stat-value text-info"><?= (int)$totalStudents ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-people me-1"></i> Registered</div>
                </div>
            </div>
        </div>

        <!-- Charts & Category Distribution Row -->
        <div class="row g-4 mb-4">
            <!-- Status Breakdown Doughnut Chart -->
            <div class="col-lg-5">
                <div class="app-card shadow-sm border-0 h-100">
                    <div class="app-card-header bg-white">
                        <h6 class="app-card-title fw-bold">
                            <i class="bi bi-pie-chart text-primary me-2"></i> Grievance Status Ratio
                        </h6>
                    </div>
                    <div class="app-card-body d-flex flex-column align-items-center justify-content-center">
                        <div style="position: relative; height: 220px; width: 100%;">
                            <canvas id="statusDoughnutChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Complaints by Category Bar Chart -->
            <div class="col-lg-7">
                <div class="app-card shadow-sm border-0 h-100">
                    <div class="app-card-header bg-white">
                        <h6 class="app-card-title fw-bold">
                            <i class="bi bi-bar-chart text-primary me-2"></i> Top Complaints by Campus Category
                        </h6>
                    </div>
                    <div class="app-card-body">
                        <div style="position: relative; height: 220px; width: 100%;">
                            <canvas id="categoryBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Complaints Awaiting Review Table -->
        <div class="app-card shadow-sm border-0">
            <div class="app-card-header bg-white">
                <h5 class="app-card-title">
                    <i class="bi bi-exclamation-circle text-warning me-2"></i> Recent Submissions Awaiting Action
                </h5>
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-sm btn-outline-primary">
                    View All Grievances <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="app-card-body p-0">
                <?php if (empty($recentComplaints)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check2-circle fs-1 text-success mb-2"></i>
                        <h6 class="fw-bold">All clear! No complaints submitted yet.</h6>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table app-table align-middle">
                            <thead>
                                <tr>
                                    <th>Ref ID</th>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Subject</th>
                                    <th>Lodged Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Manage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentComplaints as $rc): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/view_complaint.php?id=<?= (int)$rc['id'] ?>" class="font-monospace fw-bold text-decoration-none">
                                                <?= e($rc['complaint_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark"><?= e($rc['student_name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?= e($rc['category_name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-inline-block text-truncate" style="max-width: 250px;">
                                                <?= e($rc['title']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= formatDate($rc['created_at'], false) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($rc['status']) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/admin/view_complaint.php?id=<?= (int)$rc['id'] ?>" class="btn btn-sm btn-primary">
                                                Review <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- Chart.js Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Status Doughnut Chart
    const statusCtx = document.getElementById('statusDoughnutChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Resolved', 'Rejected'],
                datasets: [{
                    data: [
                        <?= (int)$metrics['pending_count'] ?>,
                        <?= (int)$metrics['progress_count'] ?>,
                        <?= (int)$metrics['resolved_count'] ?>,
                        <?= (int)$metrics['rejected_count'] ?>
                    ],
                    backgroundColor: ['#f59e0b', '#2563eb', '#10b981', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 2. Categories Bar Chart
    const catCtx = document.getElementById('categoryBarChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Complaints',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    },
                    x: {
                        ticks: {
                            callback: function(val, index) {
                                // Shorten long labels for clean axis
                                const label = this.getLabelForValue(val);
                                return label.length > 15 ? label.substr(0, 15) + '...' : label;
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
