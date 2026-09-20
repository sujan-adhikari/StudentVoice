<?php
/**
 * College Complaint Management System
 * Global Footer Partial
 */
?>
    <footer class="app-footer text-center">
        <div class="container-fluid">
            <p class="mb-0">
                &copy; 2026 <strong>Student Voice</strong> &ndash; Complaint Management System
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Project JavaScript -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>

    <?php if (isset($includeChartJs) && $includeChartJs): ?>
        <!-- Chart.js for Simple Dashboard Analytics -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</body>
</html>
