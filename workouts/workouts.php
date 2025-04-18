<?php include '../services/workouts-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workouts - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="workout-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
</body>
</html>