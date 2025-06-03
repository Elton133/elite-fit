<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'eltonmorden029@gmail.com'; // your Gmail
    $mail->Password = 'qbmx havj kmwx wcug'; // your App Password
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // Sender & Recipient
    $mail->setFrom('eltonmorden029@gmail.com', 'Elton Test');
    $mail->addAddress('eltonmorden029@gmail.com', 'Test Recipient'); // put your real address here

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body    = 'This is a test email sent using PHPMailer and Gmail SMTP.';

    $mail->send();
    echo '✅ Test email sent successfully!';
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
}
