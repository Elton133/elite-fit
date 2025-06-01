<?php
include_once "../datacon.php";

// Initialize variables for error handling
$error_message = "";
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    if ($error_code == 1) {
        $error_message = "Email not found in our records.";
    } elseif ($error_code == 2) {
        $error_message = "Failed to send OTP. Please try again.";
    }
}

// Check if user was redirected after successful OTP request
$success_message = "";
if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $success_message = "OTP has been sent to your email. Please check your inbox.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit - Forgot Password</title>
    <link rel="stylesheet" href="../register/styles.css">
    <link rel="stylesheet" href="login-styles.css">
    <link rel="stylesheet" href="forgot-password-styles.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .step-number, .step-text{
            color: #fff;
        }
        .step-text1{
            color: #fff;
            font-size: 12px;
        }
        .form-info{
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="form-container forgot-password-container">
        <div class="form-header">
            <div class="logo-container">
                <img class="logo-image" src="../register/dumbbell.png" alt="dumbbell">
            </div>
            <h2>EliteFit Gym</h2>
            <p class="form-subtitle">Reset your password</p>
        </div>

        <?php if (!empty($error_message)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Toastify({
                    text: "<?php echo addslashes($error_message); ?>",
                    duration: 3000,
                    gravity: "top", 
                    position: "right", 
                    stopOnFocus: true,
                    close: true,
                    style: {
                        background: "linear-gradient(to right, #ff4b2b, #ff416c)",
                    }
                }).showToast();
            });
        </script>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Toastify({
                    text: "<?php echo addslashes($success_message); ?>",
                    duration: 3000,
                    gravity: "top", 
                    position: "right", 
                    stopOnFocus: true,
                    close: true,
                    style: {
                        background: "linear-gradient(to right, #28a745, #20c997)",
                    }
                }).showToast();
            });
        </script>
        <?php endif; ?>

        <div class="reset-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-text1">Enter Email</div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">Verify OTP</div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Reset Password</div>
            </div>
        </div>

        <form action="send_otp.php" method="POST">
            <div class="form-section">
                <div class="form-group">
                    <label>Email:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your registered email address" required>
                    </div>
                </div>
                
                <div class="form-info">
                    <p>We'll send a one-time password (OTP) to your registered email address.</p>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" id="sendOtpBtn">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
            </div>
            
            <div class="register-link">
                Remember your password? <a href="index.php">Login now</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
</body>
</html>