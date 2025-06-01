<?php 
session_start();
include '../datacon.php';

// Check if trainer is logged in
if (!isset($_SESSION['trainer_id'])) {
    header("Location: ../login.php");
    exit();
}

$trainer_id = $_SESSION['trainer_id'];
$plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;

if (!$plan_id) {
    header("Location: all-clients.php");
    exit();
}

// Get trainer data
$trainer_query = "SELECT * FROM trainers WHERE trainer_id = ?";
$trainer_stmt = $conn->prepare($trainer_query);
$trainer_stmt->bind_param("i", $trainer_id);
$trainer_stmt->execute();
$trainer_data = $trainer_stmt->get_result()->fetch_assoc();

// Get profile picture
$profile_pic = "../register/uploads/default-avatar.jpg";
if (!empty($trainer_data['profile_picture']) && file_exists("../register/uploads/" . $trainer_data['profile_picture'])) {
    $profile_pic = "../register/uploads/" . $trainer_data['profile_picture'];
}

// Get workout plan details
$plan_query = "
    SELECT 
        wp.*,
        u.first_name,
        u.last_name,
        u.email
    FROM workout_plans wp
    JOIN user_register_details u ON wp.user_id = u.table_id
    WHERE wp.plan_id = ? AND wp.trainer_id = ?
";

$plan_stmt = $conn->prepare($plan_query);
$plan_stmt->bind_param("ii", $plan_id, $trainer_id);
$plan_stmt->execute();
$plan_result = $plan_stmt->get_result();
$plan = $plan_result->fetch_assoc();

if (!$plan) {
    header("Location: all-clients.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = trim($_POST['plan_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    // Update workout plan
    $update_query = "
        UPDATE workout_plans 
        SET plan_name = ?, description = ?, start_date = ?, end_date = ?, status = ?, last_updated = NOW()
        WHERE plan_id = ? AND trainer_id = ?
    ";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssssii", $plan_name, $description, $start_date, $end_date, $status, $plan_id, $trainer_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Workout plan updated successfully!";
        header("Location: view-client.php?client_id=" . $plan['user_id']);
        exit();
    } else {
        $error_message = "Error updating workout plan. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Workout Plan - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .edit-plan-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
        }
        
        .plan-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .plan-header h2 {
            color: white;
            margin-bottom: 10px;
        }
        
        .client-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-save, .btn-cancel {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-save {
            background: #4CAF50;
            color: white;
        }
        
        .btn-cancel {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-save:hover, .btn-cancel:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .error-message {
            background: #f44336;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .back-button a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-button a:hover {
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include 'trainer-sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">0</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($trainer_data['first_name'] . ' ' . $trainer_data['last_name']) ?></h3>
                        <p class="user-status">Fitness Trainer</p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="dashboard">
            <div class="back-button">
                <a href="view-client.php?client_id=<?php echo $plan['user_id']; ?>">
                    <i class="fas fa-arrow-left"></i>
                    Back to Client Details
                </a>
            </div>
            
            <div class="edit-plan-container">
                <div class="plan-header">
                    <h2><i class="fas fa-edit"></i> Edit Workout Plan</h2>
                    <div class="client-info">
                        <p><strong>Client:</strong> <?php echo htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($plan['email']); ?></p>
                    </div>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="plan_name">Plan Name *</label>
                        <input type="text" id="plan_name" name="plan_name" value="<?php echo htmlspecialchars($plan['plan_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Describe the workout plan goals and approach..."><?php echo htmlspecialchars($plan['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $plan['start_date']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $plan['end_date']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Plan Status *</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo $plan['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="paused" <?php echo $plan['status'] === 'paused' ? 'selected' : ''; ?>>Paused</option>
                            <option value="completed" <?php echo $plan['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <a href="view-client.php?client_id=<?php echo $plan['user_id']; ?>" class="btn-cancel">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script>
        // Toast message handling
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
    </script>
</body>
</html>
