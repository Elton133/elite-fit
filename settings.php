<?php
session_start();
include_once "datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM user_register_details WHERE table_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get user role for conditional display
$user_role = $user_data['role'] ?? 'member';

// Get notification settings
$stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Default settings if not found
$settings = [
    'email_notifications' => 1,
    'push_notifications' => 1,
    'sms_notifications' => 0,
    'dark_mode' => 1,
    'language' => 'en',
    'privacy_profile' => 'public',
    'privacy_activity' => 'friends',
    'privacy_stats' => 'private',
    'two_factor_auth' => 0
];

if ($result->num_rows > 0) {
    $user_settings = $result->fetch_assoc();
    $settings = array_merge($settings, $user_settings);
}

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['update_profile'])) {
        // Profile update logic
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $contact_number = trim($_POST['contact_number']);
        $location = trim($_POST['location']);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format";
        } else {
            // Check if email exists for another user
            $stmt = $conn->prepare("SELECT table_id FROM user_register_details WHERE email = ? AND table_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email already in use by another account";
            } else {
                // Update profile
                $stmt = $conn->prepare("UPDATE user_register_details SET first_name = ?, last_name = ?, email = ?, contact_number = ?, location = ? WHERE table_id = ?");
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $contact_number, $location, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully";
                    
                    // Update session data
                    $_SESSION['user_email'] = $email;
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM user_register_details WHERE table_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                } else {
                    $error_message = "Failed to update profile: " . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['update_password'])) {
        // Password update logic
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password
        if (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT user_password FROM user_register_details WHERE table_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['user_password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in user_register_details
                $stmt = $conn->prepare("UPDATE user_register_details SET user_password = ? WHERE table_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                
                // Update password in user_login_details
                $stmt = $conn->prepare("UPDATE user_login_details SET user_password = ? WHERE username = ?");
                $stmt->bind_param("ss", $hashed_password, $user_data['email']);
                
                if ($stmt->execute()) {
                    $success_message = "Password updated successfully";
                } else {
                    $error_message = "Failed to update password: " . $conn->error;
                }
            } else {
                $error_message = "Current password is incorrect";
            }
        }
    } elseif (isset($_POST['update_notifications'])) {
        // Notification settings update logic
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        
        // Check if settings record exists
        $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE user_settings SET email_notifications = ?, push_notifications = ?, sms_notifications = ? WHERE user_id = ?");
            $stmt->bind_param("iiii", $email_notifications, $push_notifications, $sms_notifications, $user_id);
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO user_settings (user_id, email_notifications, push_notifications, sms_notifications) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $user_id, $email_notifications, $push_notifications, $sms_notifications);
        }
        
        if ($stmt->execute()) {
            $success_message = "Notification settings updated successfully";
            $settings['email_notifications'] = $email_notifications;
            $settings['push_notifications'] = $push_notifications;
            $settings['sms_notifications'] = $sms_notifications;
        } else {
            $error_message = "Failed to update notification settings: " . $conn->error;
        }
    } elseif (isset($_POST['update_privacy'])) {
        // Privacy settings update logic
        $privacy_profile = $_POST['privacy_profile'];
        $privacy_activity = $_POST['privacy_activity'];
        $privacy_stats = $_POST['privacy_stats'];
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        
        // Check if settings record exists
        $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE user_settings SET privacy_profile = ?, privacy_activity = ?, privacy_stats = ?, two_factor_auth = ? WHERE user_id = ?");
            $stmt->bind_param("sssii", $privacy_profile, $privacy_activity, $privacy_stats, $two_factor_auth, $user_id);
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO user_settings (user_id, privacy_profile, privacy_activity, privacy_stats, two_factor_auth) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $user_id, $privacy_profile, $privacy_activity, $privacy_stats, $two_factor_auth);
        }
        
        if ($stmt->execute()) {
            $success_message = "Privacy settings updated successfully";
            $settings['privacy_profile'] = $privacy_profile;
            $settings['privacy_activity'] = $privacy_activity;
            $settings['privacy_stats'] = $privacy_stats;
            $settings['two_factor_auth'] = $two_factor_auth;
        } else {
            $error_message = "Failed to update privacy settings: " . $conn->error;
        }
    } elseif (isset($_POST['update_appearance'])) {
        // Appearance settings update logic
        $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
        $language = $_POST['language'];
        
        // Check if settings record exists
        $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE user_settings SET dark_mode = ?, language = ? WHERE user_id = ?");
            $stmt->bind_param("isi", $dark_mode, $language, $user_id);
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO user_settings (user_id, dark_mode, language) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $dark_mode, $language);
        }
        
        if ($stmt->execute()) {
            $success_message = "Appearance settings updated successfully";
            $settings['dark_mode'] = $dark_mode;
            $settings['language'] = $language;
        } else {
            $error_message = "Failed to update appearance settings: " . $conn->error;
        }
    } elseif (isset($_POST['update_profile_picture'])) {
        // Profile picture update logic
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
                    // Update profile picture in database
                    $stmt = $conn->prepare("UPDATE user_register_details SET profile_picture = ? WHERE table_id = ?");
                    $stmt->bind_param("si", $new_filename, $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Profile picture updated successfully";
                        
                        // Delete old profile picture if it's not the default
                        if ($user_data['profile_picture'] != 'default-avatar.jpg' && file_exists("../register/uploads/" . $user_data['profile_picture'])) {
                            unlink("../register/uploads/" . $user_data['profile_picture']);
                        }
                        
                        // Refresh user data
                        $stmt = $conn->prepare("SELECT * FROM user_register_details WHERE table_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user_data = $result->fetch_assoc();
                    } else {
                        $error_message = "Failed to update profile picture in database: " . $conn->error;
                    }
                } else {
                    $error_message = "Failed to upload profile picture";
                }
            } else {
                $error_message = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed.";
            }
        } else {
            $error_message = "No file uploaded or error in upload";
        }
    }
}

// Get profile picture path
$profile_pic = "../register/uploads/default-avatar.jpg";
if (!empty($user_data['profile_picture'])) {
    if (file_exists("../register/uploads/" . $user_data['profile_picture'])) {
        $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
    } elseif (file_exists("../register/" . $user_data['profile_picture'])) {
        $profile_pic = "../register/" . $user_data['profile_picture'];
    }
}

// Determine which sidebar to include based on user role
$sidebar_file = 'sidebar.php';
if ($user_role === 'admin') {
    $sidebar_file = '../elitefit/admin/admin-sidebar.php';
} elseif ($user_role === 'trainer') {
    $sidebar_file = '../elitefit/trainer/trainer-sidebar.php';
} elseif ($user_role === 'user') {
    $sidebar_file = '../elitefit/welcome/sidebar.php';
} elseif ($user_role === 'equipment_manager') {
    $sidebar_file = '../elitefit/equipment/equipment-sidebar.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EliteFit Gym</title>
    <link rel="stylesheet" href="../elitefit/welcome/welcome-styles.css">
    <link rel="stylesheet" href="../elitefit/welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Settings Page Specific Styles */
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .settings-header {
            background: rgba(30, 60, 114, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .settings-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .settings-header p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .settings-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .settings-nav {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 30px;
            height: fit-content;
        }
        
        .settings-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .settings-nav-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .settings-nav-item:last-child {
            border-bottom: none;
        }
        
        .settings-nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .settings-nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .settings-nav-link.active {
            background: rgba(30, 60, 114, 0.3);
            color: white;
            border-left: 3px solid var(--primary-color);
        }
        
        .settings-nav-icon {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .settings-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .settings-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .settings-card-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(30, 60, 114, 0.2);
        }
        
        .settings-card-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
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
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.3);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 15px;
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
        
        .btn-danger {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(90deg, #c0392b, #e74c3c);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            color: #fff;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            color: #fff;
        }
        
        .switch-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .switch-label {
            margin-left: 10px;
            flex: 1;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary-color);
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .radio-option input[type="radio"] {
            cursor: pointer;
        }
        
        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 20px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-upload {
            position: relative;
            overflow: hidden;
            margin: 10px 0;
            text-align: center;
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            margin: 0;
            padding: 0;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            filter: alpha(opacity=0);
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
        
        .danger-zone {
            background: rgba(231, 76, 60, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .danger-zone h4 {
            color: #e74c3c;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .danger-zone p {
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Mobile styles */
        @media (max-width: 992px) {
            .settings-layout {
                grid-template-columns: 1fr;
            }
            
            .settings-nav {
                position: static;
                margin-bottom: 30px;
            }
            
            .settings-nav-list {
                display: flex;
                flex-wrap: wrap;
            }
            
            .settings-nav-item {
                border-bottom: none;
                border-right: 1px solid rgba(255, 255, 255, 0.1);
                flex: 1;
                min-width: 120px;
                text-align: center;
            }
            
            .settings-nav-item:last-child {
                border-right: none;
            }
            
            .settings-nav-link {
                flex-direction: column;
                padding: 15px 10px;
                gap: 10px;
            }
            
            .settings-nav-icon {
                margin-right: 0;
                width: auto;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .settings-nav-list {
                flex-direction: column;
            }
            
            .settings-nav-item {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .settings-nav-link {
                flex-direction: row;
                padding: 15px 20px;
            }
            
            .settings-nav-icon {
                margin-right: 15px;
                width: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the appropriate sidebar -->
    <?php include $sidebar_file; ?>
    
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
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="User Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($user_data['first_name'] ?? 'User') . ' ' . htmlspecialchars($user_data['last_name'] ?? '') ?></h3>
                        <p class="user-status"><?= ucfirst(htmlspecialchars($user_role)) ?></p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="settings-container">
            <div class="settings-header">
                <div>
                    <h2><i class="fas fa-cog"></i> Settings</h2>
                    <p>Manage your account settings and preferences</p>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="settings-layout">
                <div class="settings-nav">
                    <ul class="settings-nav-list">
                        <li class="settings-nav-item">
                            <a href="#account" class="settings-nav-link active" data-target="account-settings">
                                <span class="settings-nav-icon"><i class="fas fa-user"></i></span>
                                Account
                            </a>
                        </li>
                        <li class="settings-nav-item">
                            <a href="#security" class="settings-nav-link" data-target="security-settings">
                                <span class="settings-nav-icon"><i class="fas fa-lock"></i></span>
                                Security
                            </a>
                        </li>
                        <li class="settings-nav-item">
                            <a href="#notifications" class="settings-nav-link" data-target="notification-settings">
                                <span class="settings-nav-icon"><i class="fas fa-bell"></i></span>
                                Notifications
                            </a>
                        </li>
                        <li class="settings-nav-item">
                            <a href="#privacy" class="settings-nav-link" data-target="privacy-settings">
                                <span class="settings-nav-icon"><i class="fas fa-shield-alt"></i></span>
                                Privacy
                            </a>
                        </li>
                        <li class="settings-nav-item">
                            <a href="#appearance" class="settings-nav-link" data-target="appearance-settings">
                                <span class="settings-nav-icon"><i class="fas fa-palette"></i></span>
                                Appearance
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="settings-content">
                    <!-- Account Settings -->
                    <div class="settings-card" id="account-settings">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-user"></i> Account Settings</h3>
                        </div>
                        <div class="settings-card-body">
                            <form action="settings.php" method="POST" enctype="multipart/form-data">
                                <div class="profile-picture-container">
                                    <div class="profile-picture">
                                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" id="profilePreview">
                                    </div>
                                    <div class="file-upload">
                                        <input type="file" name="profile_picture" id="profilePicture" accept="image/*">
                                        <button type="submit" name="update_profile_picture" class="btn btn-secondary">
                                            <i class="fas fa-camera"></i> Change Profile Picture
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <form action="settings.php" method="POST">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="contact_number">Contact Number</label>
                                        <input type="tel" id="contact_number" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user_data['contact_number'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Security Settings -->
                    <div class="settings-card" id="security-settings" style="display: none;">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-lock"></i> Security Settings</h3>
                        </div>
                        <div class="settings-card-body">
                            <form action="settings.php" method="POST">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <div class="password-strength">
                                        <div class="strength-meter">
                                            <div class="strength-bar" id="strengthBar"></div>
                                        </div>
                                        <div class="strength-text" id="strengthText">Password strength</div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button type="submit" name="update_password" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Password
                                    </button>
                                </div>
                            </form>
                            
                            <div class="danger-zone">
                                <h4>Danger Zone</h4>
                                <p>Once you delete your account, there is no going back. Please be certain.</p>
                                <button type="button" class="btn btn-danger" id="deleteAccountBtn">
                                    <i class="fas fa-trash-alt"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div class="settings-card" id="notification-settings" style="display: none;">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-bell"></i> Notification Settings</h3>
                        </div>
                        <div class="settings-card-body">
                            <form action="settings.php" method="POST">
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="checkbox" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="switch-label">
                                        <strong>Email Notifications</strong>
                                        <p>Receive notifications via email</p>
                                    </div>
                                </div>
                                
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="checkbox" name="push_notifications" <?php echo $settings['push_notifications'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="switch-label">
                                        <strong>Push Notifications</strong>
                                        <p>Receive notifications in your browser</p>
                                    </div>
                                </div>
                                
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="checkbox" name="sms_notifications" <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="switch-label">
                                        <strong>SMS Notifications</strong>
                                        <p>Receive notifications via SMS</p>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button type="submit" name="update_notifications" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Privacy Settings -->
                    <div class="settings-card" id="privacy-settings" style="display: none;">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-shield-alt"></i> Privacy Settings</h3>
                        </div>
                        <div class="settings-card-body">
                            <form action="settings.php" method="POST">
                                <div class="form-group">
                                    <label>Profile Visibility</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_profile" value="public" <?php echo $settings['privacy_profile'] === 'public' ? 'checked' : ''; ?>>
                                            Public
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_profile" value="friends" <?php echo $settings['privacy_profile'] === 'friends' ? 'checked' : ''; ?>>
                                            Friends Only
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_profile" value="private" <?php echo $settings['privacy_profile'] === 'private' ? 'checked' : ''; ?>>
                                            Private
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Activity Visibility</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_activity" value="public" <?php echo $settings['privacy_activity'] === 'public' ? 'checked' : ''; ?>>
                                            Public
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_activity" value="friends" <?php echo $settings['privacy_activity'] === 'friends' ? 'checked' : ''; ?>>
                                            Friends Only
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_activity" value="private" <?php echo $settings['privacy_activity'] === 'private' ? 'checked' : ''; ?>>
                                            Private
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Fitness Stats Visibility</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_stats" value="public" <?php echo $settings['privacy_stats'] === 'public' ? 'checked' : ''; ?>>
                                            Public
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_stats" value="friends" <?php echo $settings['privacy_stats'] === 'friends' ? 'checked' : ''; ?>>
                                            Friends Only
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="privacy_stats" value="private" <?php echo $settings['privacy_stats'] === 'private' ? 'checked' : ''; ?>>
                                            Private
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="checkbox" name="two_factor_auth" <?php echo $settings['two_factor_auth'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="switch-label">
                                        <strong>Two-Factor Authentication</strong>
                                        <p>Add an extra layer of security to your account</p>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button type="submit" name="update_privacy" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Appearance Settings -->
                    <div class="settings-card" id="appearance-settings" style="display: none;">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-palette"></i> Appearance Settings</h3>
                        </div>
                        <div class="settings-card-body">
                            <form action="settings.php" method="POST">
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="checkbox" name="dark_mode" <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="switch-label">
                                        <strong>Dark Mode</strong>
                                        <p>Use dark theme for the application</p>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="language">Language</label>
                                    <select id="language" name="language" class="form-control">
                                        <option value="en" <?php echo $settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                        <option value="es" <?php echo $settings['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                        <option value="fr" <?php echo $settings['language'] === 'fr' ? 'selected' : ''; ?>>French</option>
                                        <option value="de" <?php echo $settings['language'] === 'de' ? 'selected' : ''; ?>>German</option>
                                        <option value="zh" <?php echo $settings['language'] === 'zh' ? 'selected' : ''; ?>>Chinese</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button type="submit" name="update_appearance" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <!-- Delete Account Confirmation Modal -->
    <div class="modal" id="deleteAccountModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div style="background: rgba(40, 40, 40, 0.95); border-radius: 15px; padding: 30px; max-width: 500px; width: 90%; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                <h3 style="font-size: 24px; margin: 0; color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Delete Account</h3>
                <button id="closeDeleteModal" style="background: none; border: none; color: rgba(255, 255, 255, 0.7); font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <p>Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.</p>
                <div style="margin-top: 20px;">
                    <label for="deleteConfirm" style="display: block; margin-bottom: 10px;">Please type "DELETE" to confirm:</label>
                    <input type="text" id="deleteConfirm" style="width: 100%; padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(255, 255, 255, 0.05); color: white; font-family: var(--font-family); font-size: 16px;">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px; padding-top: 15px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <button id="cancelDelete" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button id="confirmDelete" class="btn btn-danger" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
    
    <script src="../elitefit/welcome/sidebar-script.js"></script>
    <script src="../elitefit/scripts/background.js"></script>
    <script src="../elitefit/scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Tab navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.settings-nav-link');
            const settingsPanels = document.querySelectorAll('.settings-card');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    navLinks.forEach(link => link.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Hide all panels
                    settingsPanels.forEach(panel => panel.style.display = 'none');
                    
                    // Show the target panel
                    const targetPanel = document.getElementById(this.getAttribute('data-target'));
                    targetPanel.style.display = 'block';
                    
                    // Update URL hash
                    window.location.hash = this.getAttribute('href');
                });
            });
            
            // Check for hash in URL
            if (window.location.hash) {
                const hash = window.location.hash;
                const link = document.querySelector(`.settings-nav-link[href="${hash}"]`);
                if (link) link.click();
            }
            
            // Profile picture preview
            const profilePicture = document.getElementById('profilePicture');
            const profilePreview = document.getElementById('profilePreview');
            
            if (profilePicture && profilePreview) {
                profilePicture.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            profilePreview.src = e.target.result;
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Password strength meter
            const passwordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (passwordInput && strengthBar && strengthText) {
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
            }
            
            // Delete account modal
            const deleteAccountBtn = document.getElementById('deleteAccountBtn');
            const deleteAccountModal = document.getElementById('deleteAccountModal');
            const closeDeleteModal = document.getElementById('closeDeleteModal');
            const cancelDelete = document.getElementById('cancelDelete');
            const confirmDelete = document.getElementById('confirmDelete');
            const deleteConfirm = document.getElementById('deleteConfirm');
            
            if (deleteAccountBtn && deleteAccountModal) {
                deleteAccountBtn.addEventListener('click', function() {
                    deleteAccountModal.style.display = 'flex';
                });
                
                closeDeleteModal.addEventListener('click', function() {
                    deleteAccountModal.style.display = 'none';
                });
                
                cancelDelete.addEventListener('click', function() {
                    deleteAccountModal.style.display = 'none';
                });
                
                deleteConfirm.addEventListener('input', function() {
                    confirmDelete.disabled = this.value !== 'DELETE';
                });
                
                confirmDelete.addEventListener('click', function() {
                    if (deleteConfirm.value === 'DELETE') {
                        // Submit form to delete account
                        window.location.href = 'delete-account.php';
                    }
                });
                
                // Close modal when clicking outside
                window.addEventListener('click', function(event) {
                    if (event.target === deleteAccountModal) {
                        deleteAccountModal.style.display = 'none';
                    }
                });
            }
            
            // Show toast message if exists
            const msg = localStorage.getItem('toastMessage');
            if (msg) {
                Toastify({
                    text: msg,
                    duration: 5000,
                    gravity: "top",
                    position: "center",
                    backgroundColor: "#28a745",
                    close: true
                }).showToast();
                localStorage.removeItem('toastMessage');
            }
        });
    </script>
</body>
</html>
