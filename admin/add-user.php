<?php
include_once "../datacon.php";
include_once "../services/admin-logic.php";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $location = trim($_POST['location']);
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT email FROM user_register_details WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists in the system.";
    }
    
    // Validate password
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // Process profile picture if uploaded
    $profile_picture = "default-avatar.jpg"; // Default
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (in_array(strtolower($filetype), $allowed)) {
            // Create unique filename
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = "../register/uploads/" . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed.";
        }
    }
    
    // If no errors, insert user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert into user_register_details
            $stmt = $conn->prepare("INSERT INTO user_register_details (first_name, last_name, contact_number, email, location, gender, date_of_birth, profile_picture, date_of_registration, role, user_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param("ssssssssss", $first_name, $last_name, $contact_number, $email, $location, $gender, $date_of_birth, $profile_picture, $role, $hashed_password);
            $stmt->execute();
            
            // Insert into user_login_details
            $stmt = $conn->prepare("INSERT INTO user_login_details (username, user_password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hashed_password);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Set success message
            $success = "User added successfully!";
            
            // Redirect to admin dashboard with success message
            echo "<script>
                localStorage.setItem('toastMessage', 'User added successfully!');
                window.location.href = 'admin-dashboard.php';
            </script>";
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            max-width: 800px;
            margin: 30px auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .form-title p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-family: var(--font-family);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.3);
        }
        
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: var(--font-family);
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #fff;
        }
        
        .error-message ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #fff;
        }
        
        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .file-upload-label:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .file-upload-label i {
            font-size: 36px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .file-upload-label span {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
        }
        
        .file-preview {
            margin-top: 15px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            display: none;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        
        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-meter {
            height: 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin-bottom: 5px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 5px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-text {
            font-size: 12px;
            text-align: right;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-actions button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include 'admin-sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Admin Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($admin_data['first_name'] ?? 'Admin') . ' ' . htmlspecialchars($admin_data['last_name'] ?? '') ?></h3>
                        <p class="user-status">Administrator</p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="form-container">
            <div class="form-title">
                <h2><i class="fas fa-user-plus"></i> Add New User</h2>
                <p>Create a new user account for EliteFit Gym</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" required value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" required value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($gender) && $gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($gender) && $gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($gender) && $gender == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required value="<?php echo isset($date_of_birth) ? htmlspecialchars($date_of_birth) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">User Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="member" <?php echo (isset($role) && $role == 'member') ? 'selected' : ''; ?>>Member</option>
                            <option value="trainer" <?php echo (isset($role) && $role == 'trainer') ? 'selected' : ''; ?>>Trainer</option>
                            <option value="equipment_manager" <?php echo (isset($role) && $role == 'equipment_manager') ? 'selected' : ''; ?>>Equipment Manager</option>
                            <option value="admin" <?php echo (isset($role) && $role == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <div class="file-upload">
                            <label for="profile_picture" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload or drag and drop</span>
                                <span>JPG, PNG or GIF (max. 2MB)</span>
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                            <div class="file-preview" id="imagePreview">
                                <img src="#" alt="Profile Preview">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <a href="admin-dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add User
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="admin-sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // File upload preview
        const fileInput = document.getElementById('profile_picture');
        const imagePreview = document.getElementById('imagePreview');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.style.display = 'block';
                    imagePreview.querySelector('img').src = e.target.result;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Calculate strength
            if (password.length >= 8) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^A-Za-z0-9]/)) strength += 20;
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update strength text and color
            if (strength <= 20) {
                strengthBar.style.backgroundColor = '#e74c3c';
                strengthText.textContent = 'Very Weak';
                strengthText.style.color = '#e74c3c';
            } else if (strength <= 40) {
                strengthBar.style.backgroundColor = '#e67e22';
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#e67e22';
            } else if (strength <= 60) {
                strengthBar.style.backgroundColor = '#f1c40f';
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#f1c40f';
            } else if (strength <= 80) {
                strengthBar.style.backgroundColor = '#2ecc71';
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#2ecc71';
            } else {
                strengthBar.style.backgroundColor = '#27ae60';
                strengthText.textContent = 'Very Strong';
                strengthText.style.color = '#27ae60';
            }
            
            // Check if passwords match
            if (confirmPasswordInput.value && confirmPasswordInput.value !== password) {
                confirmPasswordInput.style.borderColor = '#e74c3c';
            } else if (confirmPasswordInput.value) {
                confirmPasswordInput.style.borderColor = '#2ecc71';
            }
        });
        
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#2ecc71';
            }
        });
    </script>
</body>
</html>