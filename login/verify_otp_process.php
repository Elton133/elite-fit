<?php
include_once "../datacon.php";
session_start();

// Check if email is set in session
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Get OTP from form
if (!isset($_POST['otp_1']) || !isset($_POST['otp_2']) || !isset($_POST['otp_3']) || 
    !isset($_POST['otp_4']) || !isset($_POST['otp_5']) || !isset($_POST['otp_6'])) {
    header("Location: verify-otp.php?error=1");
    exit();
}

// Combine OTP digits
$submitted_otp = $_POST['otp_1'] . $_POST['otp_2'] . $_POST['otp_3'] . $_POST['otp_4'] . $_POST['otp_5'] . $_POST['otp_6'];

// Validate OTP format
if (!preg_match('/^\d{6}$/', $submitted_otp)) {
    header("Location: verify-otp.php?error=1");
    exit();
}

// Check OTP in database
$stmt = $conn->prepare("SELECT otp, expiry FROM password_reset_otp WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: verify-otp.php?error=1");
    exit();
}

$row = $result->fetch_assoc();
$stored_otp = $row['otp'];
$expiry = $row['expiry'];

// Check if OTP has expired
if (strtotime($expiry) < time()) {
    header("Location: verify-otp.php?error=2");
    exit();
}

// Verify OTP
if ($submitted_otp !== $stored_otp) {
    header("Location: verify-otp.php?error=1");
    exit();
}

// Mark as verified in session
$_SESSION['verified'] = true;

// Redirect to reset password page
header("Location: reset-password.php");
exit();
?>