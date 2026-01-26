<?php
// Centralized session timeout management
session_start();

// Set session timeout to 10 minutes (600 seconds)
$inactive = 600;

// Check if timeout is set
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: ../public/login.php?timeout=1");
        exit();
    }
}

// Update timeout
$_SESSION['timeout'] = time();
?>