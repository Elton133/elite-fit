<?php
session_start();
require_once('../datacon.php');

if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$table_id = $_SESSION['table_id'];

// Fetch user data
$sql_user = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$stmt_user->close();

// Fetch workout plans
$sql_plans = "SELECT * FROM workout_plans WHERE user_id = ?";
$stmt_plans = $conn->prepare($sql_plans);
$stmt_plans->bind_param("i", $user_data['table_id']);
$stmt_plans->execute();
$result_plans = $stmt_plans->get_result();
$plans = $result_plans->fetch_all(MYSQLI_ASSOC);
$stmt_plans->close();

// Fetch exercises for all plans
$exercises = [];
if (!empty($plans)) {
    $plan_ids = array_column($plans, 'plan_id');
    $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));

    $sql_exercises = "SELECT * FROM workout_plan_exercises WHERE plan_id IN ($placeholders)";
    $stmt_exercises = $conn->prepare($sql_exercises);
    $stmt_exercises->bind_param(str_repeat('i', count($plan_ids)), ...$plan_ids);
    $stmt_exercises->execute();
    $result_exercises = $stmt_exercises->get_result();
    $exercises = $result_exercises->fetch_all(MYSQLI_ASSOC);
    $stmt_exercises->close();
}

$profile_pic = "../register/uploads/default-avatar.jpg";
if (!empty($user_data['profile_picture']) && file_exists("../register/uploads/" . $user_data['profile_picture'])) {
    $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
}

$hour = date("H");
$greeting = ($hour >= 5 && $hour < 12) ? "Good morning" : (($hour < 17) ? "Good afternoon" : (($hour < 21) ? "Good evening" : "Good night"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workouts - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .workouts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 15px;
        }
        .plan-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .plan-card h3 {
            color: #2c3e50;
        }
        .plan-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .status-active {
            background: #c8e6c9;
            color: #256029;
        }
        .status-completed {
            background: #ffcdd2;
            color: #c63737;
        }
        .no-workouts {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <?php include '../welcome/sidebar.php'; ?>

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
                    <div class="user-info">
                        <p class="user-status">Member</p>
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

        <div class="welcome-banner">
            <div class="welcome-text">
                <h2><?php echo $greeting . ', ' . htmlspecialchars($user_data['first_name']); ?>!</h2>
                <p>Your workout plans and exercises</p>
            </div>
        </div>

        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Workout Plans</h3>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($plans)): ?>
                            <div class="workouts-grid">
                                <?php foreach ($plans as $plan): ?>
                                    <div class="plan-card">
                                        <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                        <div class="plan-meta">
                                            <span class="plan-status <?php echo strtolower($plan['status']) === 'active' ? 'status-active' : 'status-completed'; ?>">
                                                <?php echo htmlspecialchars($plan['status']); ?>
                                            </span>
                                            <span class="plan-dates" style="color: #666">
                                                <?php echo date('M j, Y', strtotime($plan['start_date'])); ?> -
                                                <?php echo $plan['end_date'] ? date('M j, Y', strtotime($plan['end_date'])) : 'Ongoing'; ?>
                                            </span>
                                        </div>
                                        <p class="plan-description" style="color: #666"><?php echo htmlspecialchars($plan['description']); ?></p>
                                        <div class="plan-stats" style="color: #666">
                                            <div class="stat-item">
                                                <i class="fas fa-dumbbell"></i>
                                                <span><?php echo count(array_filter($exercises, fn($ex) => $ex['plan_id'] == $plan['plan_id'])); ?> Exercises</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-workouts">
                                <p>You don't have any workout plans yet!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../welcome/sidebar-script.js"></script>
    <script>
        document.querySelector('.dropdown-menu').addEventListener('click', function() {
            this.querySelector('.dropdown-content').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.dropdown-menu') && !event.target.matches('.fa-chevron-down')) {
                const dropdowns = document.querySelectorAll('.dropdown-content');
                dropdowns.forEach(dropdown => {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>