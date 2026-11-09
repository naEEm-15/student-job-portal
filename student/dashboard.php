<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$page_title = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="h4">Student Dashboard</h2>
<p class="text-muted">
    Welcome, <?php echo e($_SESSION['full_name']); ?>.
    Your skills and job recommendations will appear here in the next modules.
</p>

<div class="list-group" style="max-width: 420px;">
    <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>student/profile.php">
        My Profile
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>