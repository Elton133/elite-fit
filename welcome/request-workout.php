<?php
session_start();
include_once "../datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$selected_trainer = null;

// Check if a trainer was selected from the trainers page
if (isset($_GET['trainer_id'])) {
    $trainer_id = $_GET['trainer_id'];
    
    // Get trainer details
    $stmt = $conn->prepare("SELECT t.trainer_id, u.first_name, u.last_name, u.profile_picture 
                           FROM trainers t 
                           JOIN user_register_details u ON t.user_id = u.table_id 
                           WHERE t.trainer_id = ?");
    $stmt->bind_param("i", $trainer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_trainer = $result->fetch_assoc();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['trainer_id']) || empty($_POST['trainer_id'])) {
        $error = "Please select a trainer for your workout request.";
    } elseif (!isset($_POST['notes']) || empty($_POST['notes'])) {
        $error = "Please describe your fitness goals or workout needs.";
    } else {
        $trainer_id = $_POST['trainer_id'];
        $notes = $_POST['notes'];
        $status = 'pending'; // Initial status
        $request_date = date('Y-m-d H:i:s');
        
        // Insert workout request
        $stmt = $conn->prepare("INSERT INTO workout_requests (user_id, trainer_id, notes, status, request_date) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $trainer_id, $notes, $status, $request_date);
        
        if ($stmt->execute()) {
            // Set success message in session
            $_SESSION['toast_message'] = "Your workout request has been submitted successfully!";
            
            // Redirect to workouts page
            header("Location: workouts.php");
            exit();
        } else {
            $error = "Failed to submit workout request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Workout - EliteFit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .request-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: white;
        }
        
        .request-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .request-card-header {
            background: rgba(30, 60, 114, 0.3);
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .request-card-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .request-card-body {
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
        
        .trainer-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px;
        }
        
        .trainer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .trainer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .trainer-info {
            flex: 1;
        }
        
        .trainer-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .trainer-specialties {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .action-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            color: #fff;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            color: #fff;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include '../welcome/sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="../register/uploads/default-avatar.jpg" alt="User Profile">
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="request-container">
            <a href="workouts.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Workouts
            </a>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <div class="request-card">
                <div class="request-card-header">
                    <h2 class="request-card-title">Request a Workout Plan</h2>
                </div>
                <div class="request-card-body">
                    <form method="POST" action="request-workout.php">
                        <div class="form-group">
                            <label for="notes">Describe your fitness goals and workout needs</label>
                            <textarea id="notes" name="notes" class="form-control" rows="6" placeholder="Example: I want to build muscle and strength. I can work out 4 days a week and have access to a full gym. I've been training for about 6 months."><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Select a Trainer</label>
                            
                            <?php if ($selected_trainer): ?>
                                <div class="trainer-card">
                                    <div class="trainer-avatar">
                                        <img src="<?= !empty($selected_trainer['profile_picture']) ? '../register/uploads/' . htmlspecialchars($selected_trainer['profile_picture']) : '../register/uploads/default-avatar.jpg' ?>" alt="Trainer">
                                    </div>
                                    <div class="trainer-info">
                                        <div class="trainer-name"><?= htmlspecialchars($selected_trainer['first_name'] . ' ' . $selected_trainer['last_name']) ?></div>
                                    </div>
                                    <a href="../trainer/trainers.php?context=workout_request" class="action-btn secondary">
                                        Change
                                    </a>
                                </div>
                                <input type="hidden" name="trainer_id" value="<?= $selected_trainer['trainer_id'] ?>">
                            <?php else: ?>
                                <div class="trainer-selection">
                                    <p>Please select a trainer who will create your workout plan.</p>
                                    <a href="../trainer/trainers.php?context=workout_request" class="action-btn">
                                        <i class="fas fa-user"></i> Browse Trainers
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <a href="workouts.php" class="action-btn secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="action-btn" <?= !$selected_trainer ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
