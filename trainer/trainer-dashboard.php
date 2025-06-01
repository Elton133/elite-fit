<?php include '../services/trainer-dashboard-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <span class="notification-badge"><?php echo $trainer_stats['pending_requests']; ?></span>
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
        
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2><?php echo $greeting . ', ' . htmlspecialchars($trainer_data['first_name'] ?? 'Trainer'); ?>!</h2>
                <p>You have <?php echo $trainer_stats['pending_requests']; ?> pending workout plan requests to review today.</p>
            </div>
            <div class="quick-actions">
                <!-- <a href="create-plan.php" class="action-btn"><i class="fas fa-plus"></i> Create Workout Plan</a> -->
                <a href="#session-card" class="action-btn secondary"><i class="fas fa-calendar-alt"></i> Scheduled Sessions</a>
            </div>
        </div>
        
        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Trainer Statistics</h3>
                    </div>
                    <div class="card-content">
                        <div class="stat-item">
                            <div class="stat-icon bmi-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Active Clients</h4>
                                <p class="stat-value"><?php echo $trainer_stats['active_plans']; ?></p>
                                <p class="stat-label">Total: <?php echo $trainer_stats['total_clients']; ?> clients</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon weight-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Pending Requests</h4>
                                <p class="stat-value"><?php echo $trainer_stats['pending_requests']; ?></p>
                                <p class="stat-label">Awaiting your review</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon height-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Rating</h4>
                                <p class="stat-value"><?php echo $avg_rating; ?>/5.0</p>
                                <p class="stat-label">From <?php echo $trainer_stats['total_reviews']; ?> reviews</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Pending Requests</h3>
                        <a href="all-requests.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($pending_requests) > 0): ?>
                            <ul class="user-list">
                                <?php foreach ($pending_requests as $request): ?>
                                    <li class="user-item">
                                        <div class="user-avatar">
                    <img src="<?php echo htmlspecialchars($request['user_profile_pic']); ?>" 
                         alt="<?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>" 
                         onerror="this.src='../register/uploads/default-avatar.jpg'">
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                    <p><?php echo htmlspecialchars($request['email']); ?></p>
                </div>
                                        <div class="user-details">
                                            <h4 class="user-name"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                                            <p class="user-email">Goal: <?php echo htmlspecialchars($request['fitness_goal_1']); ?></p>
                                            <p class="user-email">Experience: Level <?php echo htmlspecialchars($request['experience_level']); ?></p>
                                        </div>
                                        <div class="user-actions">
                                            <a href="create-plan.php?request_id=<?php echo $request['request_id']; ?>" class="action-btn" style="padding: 7px 18px; font-size: 12px;">
                                                <i class="fas fa-dumbbell"></i> Create Plan
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No pending requests at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Active Clients</h3>
                        <a href="all-clients.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($active_clients) > 0): ?>
                            <ul class="user-list">
                                <?php foreach ($active_clients as $client): ?>
                                    <li class="user-item">
                                       <div class="user-avatar">
                                                <img src="<?php echo htmlspecialchars($client['user_profile_pic']); ?>" 
                                                    alt="<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>" 
                                                    onerror="this.src='../register/uploads/default-avatar.jpg'">
                                            </div>
                                            <div class="client-info">
                                                <h4><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($client['plan_name']); ?></p>
                                            </div>
                                        <div class="user-details">
                                            <h4 class="user-name"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
                                            <p class="user-email">Plan: <?php echo htmlspecialchars($client['plan_name']); ?></p>
                                            <p class="user-email">Updated: <?php echo date('M d, Y', strtotime($client['last_updated'])); ?></p>
                                        </div>
                                        <div class="user-actions">
                                            <a href="view-client.php?client_id=<?php echo $client['table_id']; ?>" class="action-btn" style="padding: 7px 18px; font-size: 12px; background: rgba(255,255,255,0.2);">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="edit-plan.php?plan_id=<?php echo $client['plan_id']; ?>" class="action-btn" style="padding: 7px 18px; font-size: 12px;">
                                                <i class="fas fa-edit"></i> Edit Plan
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No active clients at the moment.</p>
                                <a href="find-clients.php" class="action-btn" style="margin-top: 15px; display: inline-block;">
                                    <i class="fas fa-search"></i> Find Clients
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="session-card" class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h3>
                        <!-- <a href="schedule.php" class="view-all">View Schedule</a> -->
                    </div>
                    <div class="card-content">
                        <div class="class-list">
                            <?php if (count($sessions) > 0): ?>
                                <?php foreach ($sessions as $session): ?>
                                    <?php
                                        $sessionDate = date("Y-m-d");
                                        $dayLabel = $session['session_date'] === $sessionDate ? "Today" :
                                                    ($session['session_date'] === date("Y-m-d", strtotime("+1 day")) ? "Tomorrow" :
                                                    date("D, M j", strtotime($session['session_date'])));

                                        $userName = "Client #" . $session['user_id'];
                                    ?>
                                    <div class="class-item">
                                        <div class="class-time">
                                            <span class="time"><?php echo date("H:i", strtotime($session['start_time'])); ?></span>
                                            <span class="day"><?php echo $dayLabel; ?></span>
                                        </div>
                                        <div class="class-details">
                                            <h4><?php echo htmlspecialchars($session['session_type']) . " - " . $userName; ?></h4>
                                            <p><?php echo htmlspecialchars($session['notes']); ?></p>
                                        </div>
                                        <button class="book-btn">
                                            <?php echo $session['session_date'] === $sessionDate ? "Start" : "Prepare"; ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="padding: 1rem;">No upcoming sessions found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

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