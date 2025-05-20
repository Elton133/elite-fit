<?php
include_once "../datacon.php";

// Check if email and token are set in the session
session_start();
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verified'])) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Initialize variables for error handling
$error_message = "";
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    if ($error_code == 1) {
        $error_message = "Passwords do not match.";
    } elseif ($error_code == 2) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($error_code == 3) {
        $error_message = "Failed to update password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit - Reset Password</title>
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
            <p class="form-subtitle">Create New Password</p>
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
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div class="step-text">Verify OTP</div>
            </div>
            <div class="step-connector completed"></div>
            <div class="step active">
                <div class="step-number">3</div>
                <div class="step-text">Reset Password</div>
            </div>
        </div>

        <form action="reset_password_process.php" method="POST">
            <div class="form-section">
                <div class="form-info">
                    <p>Create a new password for <strong><?php echo htmlspecialchars($email); ?></strong></p>
                </div>
                
                <div class="form-group">
                    <label>New Password:</label>
                    <div class="input-with-icon password-input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                        <i class="fas fa-eye toggle-password" id="toggleNewPassword"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password:</label>
                    <div class="input-with-icon password-input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    </div>
                </div>
                
                <div class="password-strength">
                    <div class="strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Password strength</div>
                </div>
                
                <div class="password-requirements">
                    <p>Password must:</p>
                    <ul>
                        <li id="length"><i class="fas fa-times-circle"></i> Be at least 8 characters long</li>
                        <li id="uppercase"><i class="fas fa-times-circle"></i> Include an uppercase letter</li>
                        <li id="lowercase"><i class="fas fa-times-circle"></i> Include a lowercase letter</li>
                        <li id="number"><i class="fas fa-times-circle"></i> Include a number</li>
                        <li id="special"><i class="fas fa-times-circle"></i> Include a special character</li>
                    </ul>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" id="resetPasswordBtn">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('new_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password strength checker
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        // Password requirement elements
        const lengthReq = document.getElementById('length');
        const uppercaseReq = document.getElementById('uppercase');
        const lowercaseReq = document.getElementById('lowercase');
        const numberReq = document.getElementById('number');
        const specialReq = document.getElementById('special');
        
        newPassword.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            // Update requirement indicators
            updateRequirement(lengthReq, hasLength);
            updateRequirement(uppercaseReq, hasUppercase);
            updateRequirement(lowercaseReq, hasLowercase);
            updateRequirement(numberReq, hasNumber);
            updateRequirement(specialReq, hasSpecial);
            
            // Calculate strength
            if (hasLength) strength += 20;
            if (hasUppercase) strength += 20;
            if (hasLowercase) strength += 20;
            if (hasNumber) strength += 20;
            if (hasSpecial) strength += 20;
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update strength text and color
            if (strength <= 20) {
                strengthBar.style.backgroundColor = '#ff4b2b';
                strengthText.textContent = 'Very Weak';
                strengthText.style.color = '#ff4b2b';
            } else if (strength <= 40) {
                strengthBar.style.backgroundColor = '#ff9800';
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#ff9800';
            } else if (strength <= 60) {
                strengthBar.style.backgroundColor = '#ffeb3b';
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#ffeb3b';
            } else if (strength <= 80) {
                strengthBar.style.backgroundColor = '#8bc34a';
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#8bc34a';
            } else {
                strengthBar.style.backgroundColor = '#4caf50';
                strengthText.textContent = 'Very Strong';
                strengthText.style.color = '#4caf50';
            }
            
            // Check if passwords match
            if (confirmPassword.value && confirmPassword.value !== password) {
                confirmPassword.style.borderColor = 'var(--danger-color)';
            } else if (confirmPassword.value) {
                confirmPassword.style.borderColor = 'var(--success-color)';
            }
        });
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                this.style.borderColor = 'var(--danger-color)';
            } else {
                this.style.borderColor = 'var(--success-color)';
            }
        });
        
        function updateRequirement(element, isValid) {
            if (isValid) {
                element.querySelector('i').className = 'fas fa-check-circle';
                element.querySelector('i').style.color = 'var(--success-color)';
            } else {
                element.querySelector('i').className = 'fas fa-times-circle';
                element.querySelector('i').style.color = 'var(--danger-color)';
            }
        }
    </script>
</body>
</html>