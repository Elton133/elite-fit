<?php
// verify_otp_page.php - OTP verification page

session_start();
require_once 'email_verification.php';
include_once 'datacon.php';

$emailVerification = new EmailVerification($conn);
$message = '';
$success = false;

// Check if user came from registration
if (!isset($_SESSION['verification_email'])) {
    header('Location: register/index.php');
    exit();
}

$email = $_SESSION['verification_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp = mysqli_real_escape_string($conn, $_POST['otp']);
        
        if (strlen($otp) == 6 && is_numeric($otp)) {
            $result = $emailVerification->verifyOTP($email, $otp);
            $success = $result['success'];
            $message = $result['message'];
            
            if ($success) {
                // Clear session and redirect to login
                unset($_SESSION['verification_email']);
                $_SESSION['verification_success'] = true;
            }
        } else {
            $message = 'Please enter a valid 6-digit code.';
        }
    } elseif (isset($_POST['resend_otp'])) {
        $result = $emailVerification->resendOTP($email);
        $message = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - EliteFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }

        .form-container {
            width: 500px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .logo-container {
            width: 70px;
            height: 70px;
            background: rgba(30, 60, 114, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .logo-text {
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        h2 {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .email-display {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #fff;
            font-size: 14px;
        }

        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: #333;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: rgba(30, 60, 114, 0.8);
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.2);
            transform: translateY(-2px);
        }

        .single-otp-input {
            width: 100%;
            padding: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: #333;
            transition: all 0.3s ease;
        }

        .single-otp-input:focus {
            outline: none;
            border-color: rgba(30, 60, 114, 0.8);
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.2);
            transform: translateY(-2px);
        }

        button {
            background: rgba(30, 60, 114, 0.9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            margin-bottom: 10px;
        }

        button:hover {
            background: rgba(30, 60, 114, 1);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        button i {
            margin-right: 8px;
        }

        .resend-btn {
            background: rgba(108, 117, 125, 0.8);
        }

        .resend-btn:hover {
            background: rgba(108, 117, 125, 0.9);
        }

        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .success {
            background: rgba(40, 167, 69, 0.2);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .timer {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-top: 15px;
        }

        @media (max-width: 576px) {
            .form-container {
                width: 90%;
                padding: 25px 20px;
            }
            
            .otp-input {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo-container">
            <div class="logo-text">EF</div>
        </div>
        
        <h2>Verify Your Email</h2>
        <p class="form-subtitle">Enter the 6-digit code sent to your email</p>
        
        <div class="email-display">
            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <script>
                setTimeout(function() {
                    window.location.href = 'login/index.php';
                }, 2000);
            </script>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="otp">Verification Code</label>
                    <input type="text" 
                           id="otp" 
                           name="otp" 
                           class="single-otp-input"
                           placeholder="000000"
                           maxlength="6" 
                           required
                           autocomplete="off">
                </div>
                
                <button type="submit" name="verify_otp">
                    <i class="fas fa-check"></i>
                    Verify Code
                </button>
                
                <button type="submit" name="resend_otp" class="resend-btn">
                    <i class="fas fa-paper-plane"></i>
                    Resend Code
                </button>
            </form>
            
            <div class="timer">
                Code expires in 10 minutes
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-format OTP input
        document.getElementById('otp').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 6) value = value.slice(0, 6); // Limit to 6 digits
            e.target.value = value;
        });
        
        // Auto-submit when 6 digits entered
        document.getElementById('otp').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                // Optional: auto-submit after a short delay
                setTimeout(function() {
                    if (confirm('Submit verification code?')) {
                        document.querySelector('form').submit();
                    }
                }, 500);
            }
        });
    </script>
</body>
</html>