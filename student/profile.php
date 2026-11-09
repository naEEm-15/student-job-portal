<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('student');

$user_id = (int) $_SESSION['user_id'];

// Load the current data (the name lives in users, the rest in student_profiles).
$stmt = $pdo->prepare(
    'SELECT u.full_name, u.email, sp.phone, sp.university, sp.department,
            sp.graduation_year, sp.bio, sp.preferred_location,
            sp.preferred_job_type, sp.preferred_role
     FROM users u
     JOIN student_profiles sp ON sp.user_id = u.user_id
     WHERE u.user_id = ?'
);
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    set_flash('danger', 'Student profile not found.');
    redirect('student/dashboard.php');
}

$job_types = ['Full-time', 'Part-time', 'Internship', 'Remote'];
$errors    = [];

// The values shown in the form (from the database at first).
$form = [
    'full_name'          => (string) $profile['full_name'],
    'phone'              => (string) ($profile['phone'] ?? ''),
    'university'         => (string) ($profile['university'] ?? ''),
    'department'         => (string) ($profile['department'] ?? ''),
    'graduation_year'    => (string) ($profile['graduation_year'] ?? ''),
    'preferred_location' => (string) ($profile['preferred_location'] ?? ''),
    'preferred_job_type' => (string) ($profile['preferred_job_type'] ?? ''),
    'preferred_role'     => (string) ($profile['preferred_role'] ?? ''),
    'bio'                => (string) ($profile['bio'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Read what was submitted.
    foreach ($form as $field => $old_value) {
        $form[$field] = clean_input($_POST[$field] ?? '');
    }

    // 2. Check the secret form code.
    if (!csrf_check()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    // 3. Validate.
    if (mb_strlen($form['full_name']) < 2 || mb_strlen($form['full_name']) > 100) {
        $errors['full_name'] = 'Name must be 2 to 100 characters.';
    }

    if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $form['phone'])) {
        $errors['phone'] = 'Phone may contain digits, spaces, + - ( ) and be 7 to 20 characters.';
    }

    if (mb_strlen($form['university']) > 150) {
        $errors['university'] = 'University name is too long (maximum 150 characters).';
    }

    if (mb_strlen($form['department']) > 100) {
        $errors['department'] = 'Department is too long (maximum 100 characters).';
    }

    if ($form['graduation_year'] !== '') {
        $max_year = (int) date('Y') + 10;
        if (!ctype_digit($form['graduation_year'])
            || (int) $form['graduation_year'] < 2000
            || (int) $form['graduation_year'] > $max_year) {
            $errors['graduation_year'] = 'Enter a year between 2000 and ' . $max_year . '.';
        }
    }

    if (mb_strlen($form['preferred_location']) > 100) {
        $errors['preferred_location'] = 'Location is too long (maximum 100 characters).';
    }

    if ($form['preferred_job_type'] !== '' && !in_array($form['preferred_job_type'], $job_types, true)) {
        $errors['preferred_job_type'] = 'Please choose a valid job type.';
    }

    if (mb_strlen($form['preferred_role']) > 100) {
        $errors['preferred_role'] = 'Preferred role is too long (maximum 100 characters).';
    }

    if (mb_strlen($form['bio']) > 1000) {
        $errors['bio'] = 'Bio is too long (maximum 1000 characters).';
    }

    // 4. No errors? Save both tables together.
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE users SET full_name = ? WHERE user_id = ?');
            $stmt->execute([$form['full_name'], $user_id]);

            $stmt = $pdo->prepare(
                'UPDATE student_profiles
                 SET phone = ?, university = ?, department = ?, graduation_year = ?,
                     bio = ?, preferred_location = ?, preferred_job_type = ?, preferred_role = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([
                null_if_empty($form['phone']),
                null_if_empty($form['university']),
                null_if_empty($form['department']),
                null_if_empty($form['graduation_year']),
                null_if_empty($form['bio']),
                null_if_empty($form['preferred_location']),
                null_if_empty($form['preferred_job_type']),
                null_if_empty($form['preferred_role']),
                $user_id,
            ]);

            $pdo->commit();

            $_SESSION['full_name'] = $form['full_name'];   // keep the navbar name up to date
            set_flash('success', 'Profile saved.');
            redirect('student/profile.php');

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Profile update failed: ' . $e->getMessage());
            $errors['form'] = 'Sorry, we could not save your profile. Please try again.';
        }
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-3">My Profile</h2>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?php echo e($errors['form']); ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo BASE_URL; ?>student/profile.php" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   maxlength="100" value="<?php echo e($form['full_name']); ?>">
                            <?php field_error($errors, 'full_name'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email (cannot be changed)</label>
                            <input type="email" class="form-control" id="email"
                                   value="<?php echo e($profile['email']); ?>" disabled>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   maxlength="20" value="<?php echo e($form['phone']); ?>">
                            <?php field_error($errors, 'phone'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="university" class="form-label">University</label>
                            <input type="text" class="form-control" id="university" name="university"
                                   maxlength="150" value="<?php echo e($form['university']); ?>">
                            <?php field_error($errors, 'university'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department"
                                   maxlength="100" value="<?php echo e($form['department']); ?>">
                            <?php field_error($errors, 'department'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="graduation_year" class="form-label">Graduation year</label>
                            <input type="text" class="form-control" id="graduation_year" name="graduation_year"
                                   maxlength="4" value="<?php echo e($form['graduation_year']); ?>">
                            <?php field_error($errors, 'graduation_year'); ?>
                        </div>
                    </div>

                    <h3 class="h6 mt-4 mb-3">Job preferences</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="preferred_location" class="form-label">Preferred location</label>
                            <input type="text" class="form-control" id="preferred_location" name="preferred_location"
                                   maxlength="100" value="<?php echo e($form['preferred_location']); ?>">
                            <div class="form-text">Leave empty if you accept any location.</div>
                            <?php field_error($errors, 'preferred_location'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="preferred_job_type" class="form-label">Preferred job type</label>
                            <select class="form-select" id="preferred_job_type" name="preferred_job_type">
                                <option value="">No preference</option>
                                <?php foreach ($job_types as $type): ?>
                                    <option value="<?php echo e($type); ?>"
                                        <?php echo $form['preferred_job_type'] === $type ? 'selected' : ''; ?>>
                                        <?php echo e($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php field_error($errors, 'preferred_job_type'); ?>
                        </div>

                        <div class="col-12">
                            <label for="preferred_role" class="form-label">Preferred role</label>
                            <input type="text" class="form-control" id="preferred_role" name="preferred_role"
                                   maxlength="100" value="<?php echo e($form['preferred_role']); ?>"
                                   placeholder="For example: Web Developer">
                            <?php field_error($errors, 'preferred_role'); ?>
                        </div>

                        <div class="col-12">
                            <label for="bio" class="form-label">About me</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"
                                      maxlength="1000"><?php echo e($form['bio']); ?></textarea>
                            <?php field_error($errors, 'bio'); ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">Save profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>