<?php
// RESET PASSWORD PROCESSOR
// File: login/reset_password_process.php
// REPLACE your existing reset_password_process.php with this code

require_once "../datacon.php";
require_once "includes/phpmailer-sender.php";
require_once "includes/otp-manager.php";

session_start();

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: forgot-password.php");
    exit();
}

if (!isset($_SESSION['verification_time']) || (time() - $_SESSION['verification_time']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: forgot-password.php?error=4");
    exit();
}

$email = $_SESSION['reset_email'];

if (!isset($_POST['new_password']) || !isset($_POST['confirm_password'])) {
    header("Location: reset-password.php?error=1");
    exit();
}

$newPassword = $_POST['new_password'];
$confirmPassword = $_POST['confirm_password'];

if ($newPassword !== $confirmPassword) {
    header("Location: reset-password.php?error=1");
    exit();
}

if (strlen($newPassword) < 8) {
    header("Location: reset-password.php?error=2");
    exit();
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $newPassword)) {
    header("Location: reset-password.php?error=5");
    exit();
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$conn->begin_transaction();

try {
    $userStmt = $conn->prepare("SELECT first_name, last_name FROM user_register_details WHERE email = ?");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userName = trim($user['first_name'] . ' ' . $user['last_name']);
    
    $stmt = $conn->prepare("UPDATE user_register_details SET user_password = ?, password_updated_at = NOW() WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    $stmt->execute();
    
    $loginStmt = $conn->prepare("UPDATE user_login_details SET user_password = ? WHERE username = ?");
    $loginStmt->bind_param("ss", $hashedPassword, $email);
    $loginStmt->execute();
    
    $otpManager = new OTPManager($conn);
    $emailSender = new PHPMailerSender();
    
    $otpManager->deleteOTP($email);
    
    $conn->commit();
    
    $emailSender->sendPasswordResetConfirmation($email, $userName);
    
    session_unset();
    session_destroy();
    
    header("Location: index.php?password_reset=1");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Password reset error: " . $e->getMessage());
    header("Location: reset-password.php?error=3");
    exit();
}
?>