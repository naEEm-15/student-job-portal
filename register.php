<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Already logged in? No need to register.
if (is_logged_in()) {
    redirect('index.php');
}

$errors       = [];
$role         = 'student';
$full_name    = '';
$email        = '';
$company_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Read and clean what the user sent.
    $role         = clean_input($_POST['role'] ?? '');
    $full_name    = clean_input($_POST['full_name'] ?? '');
    $email        = strtolower(clean_input($_POST['email'] ?? ''));
    $company_name = clean_input($_POST['company_name'] ?? '');
    $password     = $_POST['password'] ?? '';          // passwords are never trimmed
    $confirm      = $_POST['confirm_password'] ?? '';

    // 2. Check the secret form code.
    if (!csrf_check()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    // 3. Validate each field.
    if (!in_array($role, ['student', 'employer'], true)) {
        $errors['role'] = 'Please choose Student or Employer.';
    }

    if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) {
        $errors['full_name'] = 'Name must be 2 to 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // Is this email already used? (a prepared statement: the ? is filled in safely)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors['password'] = 'Password must be 8 to 72 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }

    if ($confirm !== $password) {
        $errors['confirm_password'] = 'The two passwords do not match.';
    }

    if ($role === 'employer' && (mb_strlen($company_name) < 2 || mb_strlen($company_name) > 150)) {
        $errors['company_name'] = 'Company name must be 2 to 150 characters.';
    }

    // 4. No errors? (Task 1.3 will save the account here.)
    if (empty($errors)) {
        set_flash('info', 'Validation passed. Saving the account comes in the next task.');
        redirect('register.php');
    }
}

$page_title = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-3">Create your account</h2>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?php echo e($errors['form']); ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo BASE_URL; ?>register.php" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label d-block">I am a</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_student"
                                   value="student" <?php echo $role === 'employer' ? '' : 'checked'; ?>>
                            <label class="form-check-label" for="role_student">Student</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_employer"
                                   value="employer" <?php echo $role === 'employer' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="role_employer">Employer</label>
                        </div>
                        <?php field_error($errors, 'role'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name"
                               maxlength="100" value="<?php echo e($full_name); ?>">
                        <?php field_error($errors, 'full_name'); ?>
                    </div>

                    <div id="company-box" class="mb-3 <?php echo $role === 'employer' ? '' : 'd-none'; ?>">
                        <label for="company_name" class="form-label">Company name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name"
                               maxlength="150" value="<?php echo e($company_name); ?>">
                        <?php field_error($errors, 'company_name'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               maxlength="150" value="<?php echo e($email); ?>">
                        <?php field_error($errors, 'email'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">At least 8 characters, with a letter and a number.</div>
                        <?php field_error($errors, 'password'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <?php field_error($errors, 'confirm_password'); ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>

                <p class="text-center text-muted small mt-3 mb-0">
                    Already have an account? <a href="<?php echo BASE_URL; ?>login.php">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>