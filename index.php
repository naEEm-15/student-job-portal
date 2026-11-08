<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero text-center shadow-sm">
    <h1 class="display-5 fw-bold">Student Job Portal</h1>
    <p class="lead text-muted">Find jobs that match your skills and see what to learn next.</p>
    <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-lg me-2">Get Started</a>
    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-primary btn-lg">Login</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>