<?php
// includes/header.php
// Top part of every page: <head>, Bootstrap CSS and the navigation bar.
// A page can set $page_title before including this file.

// If the project folder is ever renamed or moved, change only this line.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/student-job-portal/');
}

if (!isset($page_title)) {
    $page_title = 'Student Job Portal';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?> | Student Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">Student Job Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">