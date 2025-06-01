<?php
// SEND OTP PROCESSOR
// File: login/send_otp.php
// REPLACE your existing send_otp.php with this code

require_once "../datacon.php";
require_once "includes/phpmailer-sender.php";
require_once "includes/otp-manager.php";

session_start();

$resend = isset($_GET['resend']) && $_GET['resend'] == 1;

if ($resend && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
} else if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
} else {
    header("Location: forgot-password.php?error=1");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: forgot-password.php?error=1");
    exit();
}

$stmt = $conn->prepare("SELECT email, first_name, last_name FROM user_register_details WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: forgot-password.php?error=1");
    exit();
}

$user = $result->fetch_assoc();
$userName = trim($user['first_name'] . ' ' . $user['last_name']);

$rateLimitStmt = $conn->prepare("SELECT created_at FROM password_reset_otp WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
$rateLimitStmt->bind_param("s", $email);
$rateLimitStmt->execute();
$rateLimitResult = $rateLimitStmt->get_result();

if ($rateLimitResult->num_rows > 0) {
    header("Location: forgot-password.php?error=3");
    exit();
}

$otpManager = new OTPManager($conn);
$emailSender = new PHPMailerSender();

$otpManager->cleanupExpiredOTPs();

$otp = $otpManager->generateOTP();

if (!$otpManager->storeOTP($email, $otp)) {
    header("Location: forgot-password.php?error=2");
    exit();
}

$emailResult = $emailSender->sendOTP($email, $otp, $userName);

if (!$emailResult['success']) {
    error_log("Email sending failed: " . $emailResult['message']);
    header("Location: forgot-password.php?error=2");
    exit();
}

$_SESSION['reset_email'] = $email;
$_SESSION['otp_sent_time'] = time();

header("Location: verify-otp.php?sent=1");
exit();
?>