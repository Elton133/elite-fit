<?php
// PHPMAILER EMAIL SENDER CLASS
// File: login/includes/phpmailer-sender.php

require_once 'vendor/autoload.php';
require_once 'config/email-config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class PHPMailerSender {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_ENCRYPTION;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->setFrom(FROM_EMAIL, FROM_NAME);
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    public function sendOTP($email, $otp, $userName = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'EliteFit Gym - Password Reset OTP';
            
            $greeting = $userName ? "Hello $userName," : "Hello,";
            $htmlBody = $this->getOTPEmailTemplate($otp, $greeting);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = "Your EliteFit Gym password reset OTP is: $otp. This code expires in " . OTP_EXPIRY_MINUTES . " minutes.";
            
            $result = $this->mailer->send();
            return ['success' => true, 'message' => 'OTP sent successfully'];
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
        }
    }
    
    public function sendPasswordResetConfirmation($email, $userName = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'EliteFit Gym - Password Reset Successful';
            
            $greeting = $userName ? "Hello $userName," : "Hello,";
            $htmlBody = $this->getPasswordResetConfirmationTemplate($greeting);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = "Your EliteFit Gym password has been successfully reset.";
            
            $result = $this->mailer->send();
            return ['success' => true, 'message' => 'Confirmation email sent successfully'];
        } catch (Exception $e) {
            error_log("PHPMailer Confirmation Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send confirmation email: ' . $e->getMessage()];
        }
    }
    
    private function getOTPEmailTemplate($otp, $greeting) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; color: white; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 40px; }
                .otp-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
                .otp-code { color: white; font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: monospace; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏋️ EliteFit Gym</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <h2>Password Reset OTP</h2>
                    <p>$greeting</p>
                    <p>Use this code to reset your password:</p>
                    <div class='otp-box'>
                        <div class='otp-code'>$otp</div>
                    </div>
                    <div class='warning'>
                        <strong>⚠️ Important:</strong>
                        <ul>
                            <li>This code expires in " . OTP_EXPIRY_MINUTES . " minutes</li>
                            <li>Never share this code with anyone</li>
                            <li>If you didn't request this, ignore this email</li>
                        </ul>
                    </div>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetConfirmationTemplate($greeting) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px; text-align: center; color: white; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 40px; }
                .success-box { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; color: white; }
                .notice { background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 5px; padding: 15px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Password Reset Successful</h1>
                </div>
                <div class='content'>
                    <h2>Password Updated Successfully</h2>
                    <p>$greeting</p>
                    <p>Your EliteFit Gym password has been successfully reset.</p>
                    <div class='success-box'>
                        <h3>🔐 Password Updated</h3>
                        <p>You can now log in with your new password</p>
                    </div>
                    <div class='notice'>
                        <strong>🛡️ Security Notice:</strong> If you didn't make this change, contact us immediately at support@elitefit.com
                    </div>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>