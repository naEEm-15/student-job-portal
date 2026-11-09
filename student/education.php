<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('student');

$student_id = get_student_id($pdo, (int) $_SESSION['user_id']);
if ($student_id === null) {
    set_flash('danger', 'Student profile not found.');
    redirect('student/dashboard.php');
}

// Levels a student can choose (not "Any", which is only for job requirements).
$levels    = $pdo->query(
    'SELECT level_id, level_name FROM education_levels WHERE rank_order > 0 ORDER BY rank_order'
)->fetchAll();
$level_ids = array_map('intval', array_column($levels, 'level_id'));

$current_year = (int) date('Y');
$errors       = [];

// The values shown in the form. education_id stays empty when adding a new entry.
$form = [
    'education_id'   => '',
    'level_id'       => '',
    'status'         => 'Completed',
    'institution'    => '',
    'field_of_study' => '',
    'start_year'     => '',
    'end_year'       => '',
    'cgpa'           => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_check()) {
        $errors['form'] = 'Your session expired. Please try again.';

    } elseif ($action === 'delete') {
        // Delete one entry, but only if it belongs to this student.
        $delete_id = (int) ($_POST['education_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM education WHERE education_id = ? AND student_id = ?');
        $stmt->execute([$delete_id, $student_id]);

        if ($stmt->rowCount() > 0) {
            set_flash('success', 'Education entry deleted.');
        } else {
            set_flash('danger', 'Education entry not found.');
        }
        redirect('student/education.php');

    } elseif ($action === 'save') {
        // 1. Read the form.
        foreach ($form as $field => $old_value) {
            $form[$field] = clean_input($_POST[$field] ?? '');
        }
        $education_id = (int) $form['education_id'];   // 0 means "add a new entry"

        // 2. Validate.
        if (!in_array((int) $form['level_id'], $level_ids, true)) {
            $errors['level_id'] = 'Please choose a level.';
        }

        if (!in_array($form['status'], ['Completed', 'Ongoing'], true)) {
            $errors['status'] = 'Please choose Completed or Ongoing.';
        }

        if (mb_strlen($form['institution']) < 2 || mb_strlen($form['institution']) > 150) {
            $errors['institution'] = 'Institution must be 2 to 150 characters.';
        }

        if (mb_strlen($form['field_of_study']) > 100) {
            $errors['field_of_study'] = 'Field of study is too long (maximum 100 characters).';
        }

        if ($form['start_year'] !== '') {
            if (!ctype_digit($form['start_year'])
                || (int) $form['start_year'] < 1980
                || (int) $form['start_year'] > $current_year) {
                $errors['start_year'] = 'Enter a year between 1980 and ' . $current_year . '.';
            }
        }

        // Completed education needs an end year; an ongoing one may give the expected year.
        $max_end = $form['status'] === 'Ongoing' ? $current_year + 10 : $current_year;
        if ($form['end_year'] === '') {
            if ($form['status'] === 'Completed') {
                $errors['end_year'] = 'Completed education needs an end year.';
            }
        } elseif (!ctype_digit($form['end_year'])
                  || (int) $form['end_year'] < 1980
                  || (int) $form['end_year'] > $max_end) {
            $errors['end_year'] = 'Enter a year between 1980 and ' . $max_end . '.';
        }

        if (!isset($errors['start_year']) && !isset($errors['end_year'])
            && $form['start_year'] !== '' && $form['end_year'] !== ''
            && (int) $form['end_year'] < (int) $form['start_year']) {
            $errors['end_year'] = 'End year cannot be before the start year.';
        }

        if ($form['cgpa'] !== ''
            && (!preg_match('/^[0-4](\.\d{1,2})?$/', $form['cgpa']) || (float) $form['cgpa'] > 4.00)) {
            $errors['cgpa'] = 'CGPA must be between 0 and 4.00 (for example 3.45).';
        }

        // 3. Save.
        if (empty($errors)) {
            $values = [
                (int) $form['level_id'],
                $form['status'],
                $form['institution'],
                null_if_empty($form['field_of_study']),
                null_if_empty($form['start_year']),
                null_if_empty($form['end_year']),
                null_if_empty($form['cgpa']),
            ];

            try {
                if ($education_id > 0) {
                    // Updating: first make sure this entry belongs to the student.
                    $stmt = $pdo->prepare(
                        'SELECT COUNT(*) FROM education WHERE education_id = ? AND student_id = ?'
                    );
                    $stmt->execute([$education_id, $student_id]);

                    if ($stmt->fetchColumn() == 0) {
                        set_flash('danger', 'Education entry not found.');
                        redirect('student/education.php');
                    }

                    $stmt = $pdo->prepare(
                        'UPDATE education
                         SET level_id = ?, status = ?, institution = ?, field_of_study = ?,
                             start_year = ?, end_year = ?, cgpa = ?
                         WHERE education_id = ? AND student_id = ?'
                    );
                    $stmt->execute(array_merge($values, [$education_id, $student_id]));
                    set_flash('success', 'Education entry updated.');

                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO education
                         (level_id, status, institution, field_of_study, start_year, end_year, cgpa, student_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute(array_merge($values, [$student_id]));
                    set_flash('success', 'Education entry added.');
                }
                redirect('student/education.php');

            } catch (PDOException $e) {
                error_log('Education save failed: ' . $e->getMessage());
                $errors['form'] = 'Sorry, we could not save this entry. Please try again.';
            }
        }
    }

} elseif (isset($_GET['edit'])) {
    // Load one entry into the form for editing (only if it is this student's).
    $stmt = $pdo->prepare(
        'SELECT education_id, level_id, status, institution, field_of_study, start_year, end_year, cgpa
         FROM education WHERE education_id = ? AND student_id = ?'
    );
    $stmt->execute([(int) $_GET['edit'], $student_id]);
    $row = $stmt->fetch();

    if (!$row) {
        set_flash('danger', 'Education entry not found.');
        redirect('student/education.php');
    }
    foreach ($form as $field => $old_value) {
        $form[$field] = (string) ($row[$field] ?? '');
    }
}

// The list shown on the page (ongoing first, then newest).
$stmt = $pdo->prepare(
    "SELECT ed.education_id, ed.status, ed.institution, ed.field_of_study,
            ed.start_year, ed.end_year, ed.cgpa, el.level_name
     FROM education ed
     JOIN education_levels el ON el.level_id = ed.level_id
     WHERE ed.student_id = ?
     ORDER BY (ed.status = 'Ongoing') DESC, ed.end_year DESC, ed.education_id DESC"
);
$stmt->execute([$student_id]);
$entries = $stmt->fetchAll();

$is_editing = $form['education_id'] !== '';

$page_title = 'My Education';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="h4 mb-3">My Education</h2>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-danger"><?php echo e($errors['form']); ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- Left: the list -->
    <div class="col-lg-7">
        <?php if (empty($entries)): ?>
            <div class="alert alert-info">You have not added any education yet. Use the form to add your first entry.</div>
        <?php else: ?>
            <?php foreach ($entries as $entry): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="h6 mb-1">
                                    <?php echo e($entry['level_name']); ?>
                                    <?php if ($entry['field_of_study']): ?>
                                        in <?php echo e($entry['field_of_study']); ?>
                                    <?php endif; ?>
                                </h3>
                                <div class="text-muted"><?php echo e($entry['institution']); ?></div>
                                <div class="small text-muted">
                                    <?php echo e($entry['start_year'] ?: '?'); ?> &ndash;
                                    <?php echo e($entry['end_year'] ?: '?'); ?>
                                    <?php if ($entry['cgpa'] !== null): ?>
                                        &middot; CGPA <?php echo e($entry['cgpa']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?php echo $entry['status'] === 'Completed' ? 'text-bg-success' : 'text-bg-warning'; ?>">
                                <?php echo e($entry['status']); ?>
                            </span>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?php echo BASE_URL; ?>student/education.php?edit=<?php echo (int) $entry['education_id']; ?>">Edit</a>

                            <form method="post" action="<?php echo BASE_URL; ?>student/education.php"
                                  onsubmit="return confirm('Delete this education entry?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="education_id" value="<?php echo (int) $entry['education_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Right: the add / edit form -->
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="h6 mb-3"><?php echo $is_editing ? 'Edit education' : 'Add education'; ?></h3>

                <form method="post" action="<?php echo BASE_URL; ?>student/education.php" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="education_id" value="<?php echo e($form['education_id']); ?>">

                    <div class="mb-3">
                        <label for="level_id" class="form-label">Level</label>
                        <select class="form-select" id="level_id" name="level_id">
                            <option value="">Choose...</option>
                            <?php foreach ($levels as $level): ?>
                                <option value="<?php echo (int) $level['level_id']; ?>"
                                    <?php echo (string) $level['level_id'] === $form['level_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($level['level_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php field_error($errors, 'level_id'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Completed" <?php echo $form['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Ongoing" <?php echo $form['status'] === 'Ongoing' ? 'selected' : ''; ?>>Ongoing (still studying)</option>
                        </select>
                        <?php field_error($errors, 'status'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="institution" class="form-label">Institution</label>
                        <input type="text" class="form-control" id="institution" name="institution"
                               maxlength="150" value="<?php echo e($form['institution']); ?>">
                        <?php field_error($errors, 'institution'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="field_of_study" class="form-label">Field of study</label>
                        <input type="text" class="form-control" id="field_of_study" name="field_of_study"
                               maxlength="100" value="<?php echo e($form['field_of_study']); ?>">
                        <?php field_error($errors, 'field_of_study'); ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="start_year" class="form-label">Start year</label>
                            <input type="text" class="form-control" id="start_year" name="start_year"
                                   maxlength="4" value="<?php echo e($form['start_year']); ?>">
                            <?php field_error($errors, 'start_year'); ?>
                        </div>
                        <div class="col-6">
                            <label for="end_year" class="form-label">End year</label>
                            <input type="text" class="form-control" id="end_year" name="end_year"
                                   maxlength="4" value="<?php echo e($form['end_year']); ?>">
                            <div class="form-text">If ongoing: expected year.</div>
                            <?php field_error($errors, 'end_year'); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cgpa" class="form-label">CGPA (out of 4.00)</label>
                        <input type="text" class="form-control" id="cgpa" name="cgpa"
                               maxlength="4" value="<?php echo e($form['cgpa']); ?>">
                        <?php field_error($errors, 'cgpa'); ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $is_editing ? 'Save changes' : 'Add entry'; ?>
                    </button>
                    <?php if ($is_editing): ?>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>student/education.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>