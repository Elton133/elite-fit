<?php
// VERIFY OTP PROCESSOR
// File: login/verify_otp_process.php
// REPLACE your existing verify_otp_process.php with this code

require_once "../datacon.php";
require_once "includes/otp-manager.php";

session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

$otpFields = ['otp_1', 'otp_2', 'otp_3', 'otp_4', 'otp_5', 'otp_6'];
$submittedOTP = '';

foreach ($otpFields as $field) {
    if (!isset($_POST[$field]) || !ctype_digit($_POST[$field]) || strlen($_POST[$field]) != 1) {
        header("Location: verify-otp.php?error=1");
        exit();
    }
    $submittedOTP .= $_POST[$field];
}

$otpManager = new OTPManager($conn);

$verificationResult = $otpManager->verifyOTP($email, $submittedOTP);

if (!$verificationResult['valid']) {
    $errorCode = 1;
    if (strpos($verificationResult['message'], 'expired') !== false) {
        $errorCode = 2;
    }
    header("Location: verify-otp.php?error=$errorCode");
    exit();
}

$_SESSION['verified'] = true;
$_SESSION['verification_time'] = time();

header("Location: reset-password.php");
exit();
?>