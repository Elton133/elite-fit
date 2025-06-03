<?php
// email_verification_otp.php - Handle OTP email verification with MySQLi

require_once '../login/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailVerification {
    private $conn;
    private $mailer;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->setupMailer();
        $this->createVerificationTable();
    }
    
    private function setupMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings - adjust these to match your existing config
            $this->mailer->isSMTP();
            $this->mailer->Host       = 'smtp.gmail.com'; // Your SMTP server
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = 'eltonmorden029@gmail.com'; // Your email
            $this->mailer->Password   = 'qbmx havj kmwx wcug'; // Your app password
     $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            $this->mailer->Port       = 465;
            
            // Set default from address
            $this->mailer->setFrom('eltonmorden029@gmail.com', 'EliteFit Team');
        } catch (Exception $e) {
            error_log("Mailer setup failed: " . $e->getMessage());
        }
    }
    
    private function createVerificationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS email_verification_otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            otp_code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            verified_at DATETIME NULL,
            attempts INT DEFAULT 0,
            INDEX idx_otp (otp_code),
            INDEX idx_email (email),
            INDEX idx_user_id (user_id)
        )";
        
        mysqli_query($this->conn, $sql);
    }
    
    public function generateOTP() {
        // Generate 6-digit OTP
        return sprintf("%06d", mt_rand(100000, 999999));
    }
    
    public function storeOTP($user_id, $email, $otp) {
        try {
            // Delete any existing OTPs for this user
            $delete_query = "DELETE FROM email_verification_otps WHERE user_id = '$user_id' OR email = '$email'";
            mysqli_query($this->conn, $delete_query);
            
            // Calculate expiry time (10 minutes from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Insert new OTP
            $insert_query = "INSERT INTO email_verification_otps (user_id, email, otp_code, expires_at) 
                           VALUES ('$user_id', '$email', '$otp', '$expires_at')";
            
            return mysqli_query($this->conn, $insert_query);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendOTPEmail($email, $username, $otp) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $username);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your EliteFit Verification Code';
            
            // Get the email template
            $emailBody = $this->getOTPEmailTemplate($username, $otp);
            $this->mailer->Body = $emailBody;
            
            // Plain text version
            $this->mailer->AltBody = "Hi $username,\n\nYour EliteFit verification code is: $otp\n\nThis code expires in 10 minutes.\n\nBest regards,\nEliteFit Team";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function getOTPEmailTemplate($username, $otp) {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Your EliteFit Verification Code</title>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: "Poppins", sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }
                
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 40px 20px;
                }
                
                .email-card {
                    background: rgba(255, 255, 255, 0.15);
                    backdrop-filter: blur(20px);
                    -webkit-backdrop-filter: blur(20px);
                    border-radius: 24px;
                    padding: 40px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                    border: 1px solid rgba(255, 255, 255, 0.18);
                    text-align: center;
                }
                
                .logo-container {
                    width: 80px;
                    height: 80px;
                    background: rgba(30, 60, 114, 0.9);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    border: 2px solid rgba(255, 255, 255, 0.3);
                }
                
                .logo-text {
                    color: white;
                    font-weight: 700;
                    font-size: 18px;
                }
                
                h1 {
                    color: #fff;
                    font-size: 28px;
                    font-weight: 700;
                    margin-bottom: 10px;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                
                .welcome-text {
                    color: rgba(255, 255, 255, 0.9);
                    font-size: 16px;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                
                .otp-container {
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 16px;
                    padding: 30px;
                    margin: 30px 0;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                }
                
                .otp-label {
                    color: rgba(255, 255, 255, 0.8);
                    font-size: 14px;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                
                .otp-code {
                    font-size: 36px;
                    font-weight: 700;
                    color: #fff;
                    letter-spacing: 8px;
                    margin: 10px 0;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                }
                
                .footer-text {
                    color: rgba(255, 255, 255, 0.7);
                    font-size: 14px;
                    margin-top: 30px;
                    line-height: 1.6;
                }
                
                .warning-text {
                    color: rgba(255, 255, 255, 0.8);
                    font-size: 12px;
                    margin-top: 20px;
                    padding: 15px;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 12px;
                    border-left: 4px solid rgba(255, 193, 7, 0.8);
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-card">
                    <div class="logo-container">
                        <div class="logo-text">EF</div>
                    </div>
                    
                    <h1>Verify Your Account</h1>
                    
                    <p class="welcome-text">
                        Hi <strong>' . htmlspecialchars($username) . '</strong>,<br><br>
                        Welcome to EliteFit! To complete your registration, please enter the verification code below on our website.
                    </p>
                    
                    <div class="otp-container">
                        <div class="otp-label">Your Verification Code</div>
                        <div class="otp-code">' . $otp . '</div>
                    </div>
                    
                    <p class="footer-text">
                        This code will expire in 10 minutes for security reasons.<br>
                        If you didn\'t create an account with EliteFit, please ignore this email.
                    </p>
                    
                    <div class="warning-text">
                        <strong>Security Note:</strong> Never share this code with anyone. EliteFit will never ask for your verification code via phone or email.
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
    
    public function verifyOTP($email, $otp) {
        try {
            // Clean inputs
            $email = mysqli_real_escape_string($this->conn, $email);
            $otp = mysqli_real_escape_string($this->conn, $otp);
            
            $query = "SELECT user_id, email, expires_at, attempts 
                     FROM email_verification_otps 
                     WHERE email = '$email' AND otp_code = '$otp' AND verified_at IS NULL";
            $result = mysqli_query($this->conn, $query);
            
            if (mysqli_num_rows($result) == 0) {
                // Increment attempts for this email
                $update_attempts = "UPDATE email_verification_otps 
                                  SET attempts = attempts + 1 
                                  WHERE email = '$email' AND verified_at IS NULL";
                mysqli_query($this->conn, $update_attempts);
                
                return ['success' => false, 'message' => 'Invalid verification code.'];
            }
            
            $row = mysqli_fetch_assoc($result);
            
            // Check if too many attempts
            if ($row['attempts'] >= 5) {
                return ['success' => false, 'message' => 'Too many failed attempts. Please request a new code.'];
            }
            
            // Check if OTP has expired
            if (strtotime($row['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
            }
            
            // Mark OTP as verified
            $update_otp = "UPDATE email_verification_otps 
                          SET verified_at = NOW() 
                          WHERE email = '$email' AND otp_code = '$otp'";
            mysqli_query($this->conn, $update_otp);
            
            // Update user as verified
            $update_user = "UPDATE user_register_details 
                          SET email_verified = 1, email_verified_at = NOW() 
                          WHERE table_id = '" . $row['table_id'] . "'";
            mysqli_query($this->conn, $update_user);
            
            return [
                'success' => true, 
                'message' => 'Email verified successfully!',
                'table_id' => $row['table_id'],
                'email' => $row['email']
            ];
            
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred.'];
        }
    }
    
    public function resendOTP($email) {
        try {
            $email = mysqli_real_escape_string($this->conn, $email);
            
            // Get user details
            $query = "SELECT id, first_name FROM user_register_details WHERE email = '$email' AND email_verified = 0";
            $result = mysqli_query($this->conn, $query);
            
            if (mysqli_num_rows($result) == 0) {
                return ['success' => false, 'message' => 'User not found or already verified.'];
            }
            
            $user = mysqli_fetch_assoc($result);
            
            // Generate new OTP
            $otp = $this->generateOTP();
            
            // Store OTP
            if ($this->storeOTP($user['id'], $email, $otp)) {
                // Send email
                if ($this->sendOTPEmail($email, $user['first_name'], $otp)) {
                    return ['success' => true, 'message' => 'New verification code sent successfully.'];
                } else {
                    return ['success' => false, 'message' => 'Failed to send verification code.'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to generate verification code.'];
            }
            
        } catch (Exception $e) {
            error_log("Resend OTP error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred.'];
        }
    }
}
?>