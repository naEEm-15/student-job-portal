<?php
// setup/create_admin.php
// Creates the FIRST admin account. Run it once, from the command line only:
//   C:\xampp\php\php.exe setup\create_admin.php

// Refuse to run from a web browser. PHP_SAPI is 'cli' only in a terminal.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../includes/db.php';

// Only one initial admin is allowed. If one exists, stop.
$count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($count > 0) {
    exit("An admin account already exists. Nothing was changed.\n");
}

// Ask a question in the terminal and return the typed answer.
function ask($question)
{
    echo $question;
    return rtrim((string) fgets(STDIN), "\r\n");
}

echo "=== Create the first admin account ===\n";
$full_name = trim(ask('Full name: '));
$email     = strtolower(trim(ask('Email: ')));
$password  = ask('Password (8-72 characters, letter + number): ');
$confirm   = ask('Confirm password: ');

// Same rules as the registration page.
$errors = [];

if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) {
    $errors[] = 'Name must be 2 to 100 characters.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $errors[] = 'Please enter a valid email address.';
}
if (strlen($password) < 8 || strlen($password) > 72) {
    $errors[] = 'Password must be 8 to 72 characters.';
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain at least one letter and one number.';
}
if ($confirm !== $password) {
    $errors[] = 'The two passwords do not match.';
}

if (!empty($errors)) {
    echo "\nNo account was created:\n";
    foreach ($errors as $message) {
        echo " - $message\n";
    }
    exit(1);
}

// Save the admin (prepared statement, hashed password).
try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')"
    );
    $stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        exit("\nThis email is already used by another account. Nothing was created.\n");
    }
    error_log('Create admin failed: ' . $e->getMessage());
    exit("\nSomething went wrong. Nothing was created.\n");
}

echo "\nAdmin account created.\n";
echo "Log in at http://localhost/student-job-portal/login.php\n";
echo "Tip: type 'cls' now to clear this screen, so the password is not left visible.\n";