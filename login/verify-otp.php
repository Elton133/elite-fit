<?php
include_once "../datacon.php";

// Check if email is set in the session
session_start();
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Initialize variables for error handling
$error_message = "";
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    if ($error_code == 1) {
        $error_message = "Invalid OTP. Please try again.";
    } elseif ($error_code == 2) {
        $error_message = "OTP has expired. Please request a new one.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit - Verify OTP</title>
    <link rel="stylesheet" href="../register/styles.css">
    <link rel="stylesheet" href="login-styles.css">
    <link rel="stylesheet" href="forgot-password-styles.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body>
    <div class="background"></div>
    <div class="form-container forgot-password-container">
        <div class="form-header">
            <div class="logo-container">
                <img class="logo-image" src="../register/dumbbell.png" alt="dumbbell">
            </div>
            <h2>EliteFit Gym</h2>
            <p class="form-subtitle">Verify OTP</p>
        </div>

        <?php if (!empty($error_message)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Toastify({
                    text: "<?php echo addslashes($error_message); ?>",
                    duration: 3000,
                    gravity: "top", // `top` or `bottom`
                    position: "right", // `left`, `center` or `right`
                    stopOnFocus: true,
                    close: true,
                    style: {
                        background: "linear-gradient(to right, #ff4b2b, #ff416c)",
                    }
                }).showToast();
            });
        </script>
        <?php endif; ?>

        <div class="reset-steps">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div class="step-text">Enter Email</div>
            </div>
            <div class="step-connector completed"></div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-text">Verify OTP</div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Reset Password</div>
            </div>
        </div>

        <form action="verify_otp_process.php" method="POST">
            <div class="form-section">
                <div class="form-info">
                    <p>We've sent a verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                </div>
                
                <div class="form-group">
                    <label>Enter OTP:</label>
                    <div class="otp-input-container">
                        <input type="text" name="otp_1" class="otp-input" maxlength="1" required>
                        <input type="text" name="otp_2" class="otp-input" maxlength="1" required>
                        <input type="text" name="otp_3" class="otp-input" maxlength="1" required>
                        <input type="text" name="otp_4" class="otp-input" maxlength="1" required>
                        <input type="text" name="otp_5" class="otp-input" maxlength="1" required>
                        <input type="text" name="otp_6" class="otp-input" maxlength="1" required>
                    </div>
                </div>
                
                <div class="form-info">
                    <p>Didn't receive the code? <a href="send_otp.php?resend=1" class="resend-link">Resend OTP</a></p>
                    <p class="timer">Code expires in: <span id="countdown">05:00</span></p>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" id="verifyOtpBtn">
                    <i class="fas fa-check-circle"></i> Verify OTP
                </button>
            </div>
            
            <div class="register-link">
                <a href="forgot-password.php"><i class="fas fa-arrow-left"></i> Back to Email Entry</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
    <script>
        // OTP input auto-tab functionality
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('keyup', (e) => {
                if (e.key >= '0' && e.key <= '9') {
                    // Move to next input
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                } else if (e.key === 'Backspace') {
                    // Move to previous input on backspace
                    if (index > 0 && input.value === '') {
                        otpInputs[index - 1].focus();
                    }
                }
            });
        });

        // Countdown timer
        function startCountdown() {
            let minutes = 5;
            let seconds = 0;
            const countdownEl = document.getElementById('countdown');
            
            const interval = setInterval(() => {
                if (seconds === 0) {
                    if (minutes === 0) {
                        clearInterval(interval);
                        countdownEl.textContent = "Expired";
                        countdownEl.style.color = "var(--danger-color)";
                        return;
                    }
                    minutes--;
                    seconds = 59;
                } else {
                    seconds--;
                }
                
                countdownEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }
        
        document.addEventListener('DOMContentLoaded', startCountdown);
    </script>
</body>
</html>