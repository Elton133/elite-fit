<?php
// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer (make sure you have installed it via Composer or downloaded the files)
require_once('../login/vendor/autoload.php'); // If using Composer
// OR if you downloaded PHPMailer manually:
// require_once('../PHPMailer/src/Exception.php');
// require_once('../PHPMailer/src/PHPMailer.php');
// require_once('../PHPMailer/src/SMTP.php');

class EmailService {
    private $smtp_host;
    private $smtp_username;
    private $smtp_password;
    private $smtp_port;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Email configuration - Update these with your actual email settings
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_username = 'eltonmorden029@gmail.com'; // Replace with your email
        $this->smtp_password = 'kuvgzbgicognutho'; // Replace with your app password
        $this->smtp_port = 587;
        $this->from_email = 'eltonmorden029@gmail.com'; // Replace with your email
        $this->from_name = 'EliteFit Gym';
    }
    
    /**
     * Send workout request notification to trainer
     */
    public function sendWorkoutRequestNotification($trainer_data, $user_data, $workout_notes, $request_id) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($trainer_data['email'], $trainer_data['first_name'] . ' ' . $trainer_data['last_name']);
            $mail->addReplyTo($user_data['email'], $user_data['first_name'] . ' ' . $user_data['last_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Workout Request - EliteFit Gym';
            
            // Get the HTML email template
            $mail->Body = $this->getWorkoutRequestEmailTemplate($trainer_data, $user_data, $workout_notes, $request_id);
            
            // Plain text version
            $mail->AltBody = $this->getWorkoutRequestPlainText($trainer_data, $user_data, $workout_notes, $request_id);
            
            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email sending failed: {$mail->ErrorInfo}");
            return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
        }
    }
    
    /**
     * Get HTML email template for workout request
     */
    private function getWorkoutRequestEmailTemplate($trainer_data, $user_data, $workout_notes, $request_id) {
        $trainer_name = htmlspecialchars($trainer_data['first_name'] . ' ' . $trainer_data['last_name']);
        $user_name = htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']);
        $user_email = htmlspecialchars($user_data['email']);
        $user_contact = htmlspecialchars($user_data['contact_number'] ?? 'Not provided');
        $workout_notes = htmlspecialchars($workout_notes);
        $current_date = date('F j, Y \a\t g:i A');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>New Workout Request</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    background: white;
                    border-radius: 15px;
                    padding: 0;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                }
                .header p {
                    margin: 10px 0 0 0;
                    opacity: 0.9;
                    font-size: 16px;
                }
                .content {
                    padding: 30px;
                }
                .greeting {
                    font-size: 18px;
                    margin-bottom: 20px;
                    color: #333;
                }
                .info-card {
                    background: #f8f9fa;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 20px 0;
                    border-left: 4px solid #667eea;
                }
                .info-label {
                    font-weight: 700;
                    color: #667eea;
                    margin-bottom: 10px;
                    font-size: 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 8px 0;
                    padding: 5px 0;
                    border-bottom: 1px solid #eee;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .info-row strong {
                    color: #555;
                    min-width: 100px;
                }
                .workout-notes {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                    border: 1px solid #ffd93d;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .workout-notes .info-label {
                    color: #856404;
                    margin-bottom: 15px;
                }
                .workout-notes p {
                    margin: 0;
                    color: #856404;
                    font-size: 15px;
                    line-height: 1.6;
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    border: 1px solid #f0e68c;
                }
                .action-buttons {
                    text-align: center;
                    margin: 30px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 10px;
                }
                .btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 50px;
                    font-weight: 600;
                    margin: 10px;
                    font-size: 14px;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }
                .btn-secondary {
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
                }
                .footer {
                    background: #f8f9fa;
                    padding: 25px;
                    text-align: center;
                    border-top: 1px solid #eee;
                }
                .footer h3 {
                    margin: 0 0 10px 0;
                    color: #667eea;
                    font-size: 20px;
                }
                .footer p {
                    margin: 5px 0;
                    color: #666;
                }
                .footer .small {
                    font-size: 12px;
                    color: #999;
                    margin-top: 15px;
                }
                .request-id {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-weight: 600;
                    font-size: 14px;
                    display: inline-block;
                }
                @media (max-width: 600px) {
                    body {
                        padding: 10px;
                    }
                    .content {
                        padding: 20px;
                    }
                    .info-row {
                        flex-direction: column;
                        gap: 5px;
                    }
                    .btn {
                        display: block;
                        margin: 10px 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏋️ New Workout Request</h1>
                    <p>EliteFit Gym Training Request System</p>
                </div>
                
                <div class='content'>
                    <div class='greeting'>
                        Hello <strong>$trainer_name</strong>,
                    </div>
                    
                    <p>You have received a new workout request from one of our members. Here are the complete details:</p>
                    
                    <div class='info-card'>
                        <div class='info-label'>
                            👤 Member Information
                        </div>
                        <div class='info-row'>
                            <strong>Name:</strong>
                            <span>$user_name</span>
                        </div>
                        <div class='info-row'>
                            <strong>Email:</strong>
                            <span>$user_email</span>
                        </div>
                        <div class='info-row'>
                            <strong>Contact:</strong>
                            <span>$user_contact</span>
                        </div>
                        <div class='info-row'>
                            <strong>Request ID:</strong>
                            <span class='request-id'>#$request_id</span>
                        </div>
                    </div>
                    
                    <div class='workout-notes'>
                        <div class='info-label'>
                            📝 Fitness Goals & Workout Requirements
                        </div>
                        <p>$workout_notes</p>
                    </div>
                    
                    <div class='info-card'>
                        <div class='info-label'>
                            📅 Request Details
                        </div>
                        <div class='info-row'>
                            <strong>Submitted:</strong>
                            <span>$current_date</span>
                        </div>
                        <div class='info-row'>
                            <strong>Status:</strong>
                            <span style='color: #ffc107; font-weight: 600;'>⏳ Pending Review</span>
                        </div>
                    </div>
                    
                    <div class='action-buttons'>
                        <a href='http://yourwebsite.com/trainer/workout-requests.php' class='btn'>
                            📋 View All Requests
                        </a>
                        <a href='mailto:$user_email?subject=Re: Workout Request #$request_id&body=Hello $user_name,%0D%0A%0D%0AThank you for your workout request. I have reviewed your fitness goals and would like to discuss...' class='btn btn-secondary'>
                            📧 Reply to Member
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <h3>EliteFit Gym</h3>
                    <p><strong>Professional Fitness Training Services</strong></p>
                    <p>Helping you achieve your fitness goals with expert guidance</p>
                    <div class='small'>
                        This is an automated notification from the EliteFit Gym training request system.<br>
                        Please do not reply directly to this email. Use the action buttons above to respond.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get plain text version of workout request email
     */
    private function getWorkoutRequestPlainText($trainer_data, $user_data, $workout_notes, $request_id) {
        $trainer_name = $trainer_data['first_name'] . ' ' . $trainer_data['last_name'];
        $user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
        $user_email = $user_data['email'];
        $user_contact = $user_data['contact_number'] ?? 'Not provided';
        $current_date = date('F j, Y \a\t g:i A');
        
        return "
NEW WORKOUT REQUEST - ELITEFIT GYM
=====================================

Hello $trainer_name,

You have received a new workout request from one of our members.

MEMBER INFORMATION:
------------------
Name: $user_name
Email: $user_email
Contact: $user_contact
Request ID: #$request_id

FITNESS GOALS & WORKOUT REQUIREMENTS:
------------------------------------
$workout_notes

REQUEST DETAILS:
---------------
Submitted: $current_date
Status: Pending Review

NEXT STEPS:
----------
1. Log in to your trainer dashboard to view all requests
2. Contact the member directly to discuss their requirements
3. Update the request status once you've responded

Please respond to this request as soon as possible to provide the best service to our members.

Best regards,
EliteFit Gym Team

---
This is an automated notification from the EliteFit Gym training request system.
        ";
    }
    
    /**
     * Send confirmation email to user
     */
    public function sendRequestConfirmationToUser($user_data, $trainer_data, $workout_notes, $request_id) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($user_data['email'], $user_data['first_name'] . ' ' . $user_data['last_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Workout Request Confirmation - EliteFit Gym';
            
            $user_name = htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']);
            $trainer_name = htmlspecialchars($trainer_data['first_name'] . ' ' . $trainer_data['last_name']);
            $current_date = date('F j, Y \a\t g:i A');
            
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Workout Request Confirmation</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #f5f5f5;
                    }
                    .container {
                        background: white;
                        border-radius: 15px;
                        padding: 0;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                        overflow: hidden;
                    }
                    .header {
                        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                        color: white;
                        padding: 30px;
                        text-align: center;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 28px;
                        font-weight: 700;
                    }
                    .content {
                        padding: 30px;
                    }
                    .success-icon {
                        text-align: center;
                        font-size: 48px;
                        margin: 20px 0;
                    }
                    .info-card {
                        background: #f8f9fa;
                        border-radius: 10px;
                        padding: 20px;
                        margin: 20px 0;
                        border-left: 4px solid #28a745;
                    }
                    .footer {
                        background: #f8f9fa;
                        padding: 25px;
                        text-align: center;
                        border-top: 1px solid #eee;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>✅ Request Confirmed</h1>
                        <p>Your workout request has been submitted successfully</p>
                    </div>
                    
                    <div class='content'>
                        <div class='success-icon'>🎉</div>
                        
                        <p>Hello <strong>$user_name</strong>,</p>
                        
                        <p>Thank you for submitting your workout request! We've successfully received your request and have notified your selected trainer.</p>
                        
                        <div class='info-card'>
                            <h3>Request Details:</h3>
                            <p><strong>Request ID:</strong> #$request_id</p>
                            <p><strong>Assigned Trainer:</strong> $trainer_name</p>
                            <p><strong>Submitted:</strong> $current_date</p>
                            <p><strong>Status:</strong> Pending Review</p>
                        </div>
                        
                        <h3>What happens next?</h3>
                        <ul>
                            <li>Your trainer will review your fitness goals and requirements</li>
                            <li>They will contact you within 24-48 hours to discuss your program</li>
                            <li>You can track your request status in your member dashboard</li>
                        </ul>
                        
                        <p>If you have any questions or need to make changes to your request, please contact us or log in to your member dashboard.</p>
                    </div>
                    
                    <div class='footer'>
                        <h3>EliteFit Gym</h3>
                        <p>Thank you for choosing EliteFit for your fitness journey!</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->send();
            return ['success' => true, 'message' => 'Confirmation email sent to user'];
            
        } catch (Exception $e) {
            error_log("User confirmation email failed: {$mail->ErrorInfo}");
            return ['success' => false, 'message' => "Confirmation email could not be sent"];
        }
    }
}
?>