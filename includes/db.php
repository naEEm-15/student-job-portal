<?php
// includes/db.php
// Creates the $pdo database connection that every page will use.

$config_file = __DIR__ . '/../config/database.php';

if (!file_exists($config_file)) {
    exit('Database settings are missing. Copy config/database.example.php to config/database.php and edit it.');
}

$config = require $config_file;

$dsn = 'mysql:host=' . $config['host']
     . ';dbname=' . $config['dbname']
     . ';charset=' . $config['charset'];

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errors stop the script
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows come back with column names
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use real prepared statements
];

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
} catch (PDOException $e) {
    // Save the real error in the server log, but show visitors a safe message.
    error_log('Database connection failed: ' . $e->getMessage());
    exit('Sorry, the database connection failed. Please try again later.');
}

// Remove variables we no longer need, so the password does not stay in memory.
unset($config, $config_file, $dsn, $options);