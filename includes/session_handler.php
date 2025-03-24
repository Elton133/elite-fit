<?php
// Start output buffering to prevent "headers already sent" errors
ob_start(); 

// Start or resume the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['email']);
}

// Function to redirect if user is not logged in
function requireLogin() {
    if (!isUserLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Flush the output buffer
ob_end_flush();
?>
