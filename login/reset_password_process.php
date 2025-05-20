<?php
include_once "../datacon.php";
session_start();

// Check if email and verification are set in session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Get passwords from form
if (!isset($_POST['new_password']) || !isset($_POST['confirm_password'])) {
    header("Location: reset-password.php?error=1");
    exit();
}

$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate passwords
if ($new_password !== $confirm_password) {
    header("Location: reset-password.php?error=1");
    exit();
}

if (strlen($new_password) < 8) {
    header("Location: reset-password.php?error=2");
    exit();
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Begin transaction
$conn->begin_transaction();

try {
    // Update password in user_register_details table
    $stmt = $conn->prepare("UPDATE user_register_details SET user_password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    $stmt->execute();
    
    // Update password in user_login_details table
    // First, get the username from email
    $stmt = $conn->prepare("SELECT email FROM user_register_details WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $username = $row['email']; // Assuming email is used as username
    
    $stmt = $conn->prepare("UPDATE user_login_details SET user_password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed_password, $username);
    $stmt->execute();
    
    // Delete OTP record
    $stmt = $conn->prepare("DELETE FROM password_reset_otp WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Redirect to login with success message
    header("Location: index.php?password_reset=1");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: reset-password.php?error=3");
    exit();
}
?>