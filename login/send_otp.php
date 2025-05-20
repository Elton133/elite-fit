<?php
include_once "../datacon.php";
session_start();

// Check if it's a resend request
$resend = isset($_GET['resend']) && $_GET['resend'] == 1;

// Get email from form or session
if ($resend && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
} else if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
} else {
    header("Location: forgot-password.php");
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: forgot-password.php?error=1");
    exit();
}

// Check if email exists in database
$stmt = $conn->prepare("SELECT email FROM user_register_details WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: forgot-password.php?error=1");
    exit();
}

// Generate OTP (6 digits)
$otp = sprintf("%06d", mt_rand(1, 999999));
$otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store OTP in database
// First check if there's an existing OTP for this email
$stmt = $conn->prepare("SELECT id FROM password_reset_otp WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing OTP
    $stmt = $conn->prepare("UPDATE password_reset_otp SET otp = ?, expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $otp_expiry, $email);
} else {
    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO password_reset_otp (email, otp, expiry) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $otp_expiry);
}

if (!$stmt->execute()) {
    header("Location: forgot-password.php?error=2");
    exit();
}

// Send OTP via email
$to = $email;
$subject = "EliteFit Gym - Password Reset OTP";
$message = "
<html>
<head>
<title>Password Reset OTP</title>
</head>
<body>
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
    <div style='text-align: center; margin-bottom: 20px;'>
        <img src='https://your-website.com/register/dumbbell.png' alt='EliteFit Logo' style='width: 80px; height: 80px;'>
        <h2 style='color: #1e3c72;'>EliteFit Gym</h2>
    </div>
    
    <p>Hello,</p>
    <p>You have requested to reset your password. Please use the following One-Time Password (OTP) to complete the process:</p>
    
    <div style='background-color: #f7f7f7; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
        <h2 style='margin: 0; color: #1e3c72; letter-spacing: 5px;'>$otp</h2>
    </div>
    
    <p>This OTP is valid for 5 minutes. If you did not request a password reset, please ignore this email.</p>
    
    <p>Best regards,<br>EliteFit Gym Team</p>
    
    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center;'>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</div>
</body>
</html>
";

// Set content-type header for sending HTML email
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: EliteFit Gym <noreply@elitefit.com>" . "\r\n";

// Send email
$mail_sent = mail($to, $subject, $message, $headers);

if (!$mail_sent) {
    // For development/testing, you can comment out this redirect and use the OTP directly
    // echo "OTP: " . $otp;
    header("Location: forgot-password.php?error=2");
    exit();
}

// Store email in session for next steps
$_SESSION['reset_email'] = $email;

// Redirect to OTP verification page
header("Location: verify-otp.php");
exit();
?>