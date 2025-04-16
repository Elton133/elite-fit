<?php
include_once "../datacon.php";

// Initialize variables for error handling
$error_message = "";
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    if ($error_code == 1) {
        $error_message = "Invalid email or password. Please try again.";
    } elseif ($error_code == 2) {
        $error_message = "Your account has been locked. Please contact support.";
    }
}

// Check if user was redirected after registration
$success_message = "";
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success_message = "Registration successful! Please log in with your credentials.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit Login</title>
    <link rel="stylesheet" href="../register/styles.css">
    <link rel="stylesheet" href="login-styles.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body>
    <div class="background"></div>
    <div class="form-container login-container">
        <div class="form-header">
            <div class="logo-container">
                <img class="logo-image" src="../register/dumbbell.png" alt="dumbbell">
            </div>
            <h2>EliteFit Gym</h2>
            <p class="form-subtitle">Welcome back to your fitness journey</p>
        </div>

        <?php if (!empty($error_message)): ?>

<?php endif; ?>

<?php if (!empty($success_message)): ?>
<?php endif; ?>


        <form action="login_process.php" method="POST">
            <div class="form-section">
                <div class="form-group">
                    <label>Email:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <div class="input-with-icon password-input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <div class="checkbox-container">
                        <input type="checkbox" name="remember_me" id="remember_me" value="1">
                        <label for="remember_me">Remember me</label>
                    </div>
                    <div class="forgot-password">
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
                </div>
                
                
            </div>

            <div class="btn-container login-btn-container">
                <button type="submit" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </div>
            
            <div class="register-link">
                Don't have an account? <a href="../register/index.php">Register now</a>
            </div>
        </form>
        
        <div class="social-login">
            <p>Or login with</p>
            <div class="social-buttons">
                <a href="#" >
                    <img style="height: 40px; width:40px;" src="../register/google.png" alt="google">
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
   


    <script>
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Background image rotation
        const backgrounds = [
            'url("https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1517836357463-d25dfeac3438?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80")'
        ];
        
        let currentBg = 0;
        const bgElement = document.querySelector('.background');
        
        function changeBackground() {
            bgElement.style.backgroundImage = backgrounds[currentBg];
            currentBg = (currentBg + 1) % backgrounds.length;
        }
        
        changeBackground(); // Set initial background
        setInterval(changeBackground, 5000); // Change every 5 seconds
    </script>
</body>
</html>

