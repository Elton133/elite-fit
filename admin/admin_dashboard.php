<?php include '../services/admin-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-stats {
        display: grid;
          grid-template-columns: repeat(4, 1fr); /* 4 equal-width columns */
        gap: 1rem; /* spacing between the cards */
         margin: 2rem 0;
       }

        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(30, 60, 114, 0.2);
        }
        
        .card-content {
            padding: 3px;
        }
        
        /* Enhanced Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 10px;
            border-radius: 12px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;

        }
        
    
        
        .stat-icon {
            margin-top: 15px;
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;


        }
        
        /* Enhanced User List */
        .user-list {
            list-style: none;
            padding: 0;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s ease;
        }
        
        .user-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-details {
            flex-grow: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin: 0;
            font-size: 16px;
        }
        
        .user-email {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin: 5px 0 0 0;
        }
        
        .user-date {
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            margin-right: 15px;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .user-actions button {
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.3s ease, transform 0.3s ease;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-actions button:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
        }
        
        /* Enhanced Equipment Status */
        .equipment-status {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            gap: 10px;
        }
        
        .status-item {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            flex: 1;
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status-item:hover {
            transform: translateY(-5px);
        }
        
        .status-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .status-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .available {
            color: #2ecc71;
        }
        
        .in-use {
            color: #3498db;
        }
        
        .maintenance {
            color: #e74c3c;
        }
        
        /* Enhanced Workout Stats */
        .workout-stats {
            margin-top: 15px;
        }
        
        .workout-item {
            margin-bottom: 20px;
        }
        
        .workout-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .workout-progress {
            height: 100%;
            background: linear-gradient(90deg, #4facfe, #00f2fe);
            border-radius: 5px;
            transition: width 1s ease;
        }
        
        /* Enhanced Event List */
        .event-list {
            margin-top: 15px;
        }
        
        .event-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .event-item:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .event-item h4 {
            margin: 0;
            font-size: 16px;
            color: white;
        }
        
        .event-item span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .event-item p {
            margin: 10px 0 0 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        /* Enhanced Action Buttons */
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .action-btn.danger {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        
        .action-btn.danger:hover {
            background: linear-gradient(90deg, #c0392b, #e74c3c);
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 30px 0;
            color: rgba(255, 255, 255, 0.5);
            font-size: 16px;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Admin Banner */
        .admin-banner {
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
        .equipment-actions{
            display: flex;
            gap: 10px;
            margin-top: 10px;
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
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="admin-banner">
            <div class="welcome-text">
                <h2><?php echo $greeting . ', ' . htmlspecialchars($admin_data['first_name'] ?? 'Admin'); ?>!</h2>
                <p>Welcome to the EliteFit Gym administration panel</p>
            </div>
            <div class="quick-actions">
                <button class="action-btn"><i class="fas fa-user-plus"></i> Add User</button>
                <button class="action-btn secondary"><i class="fas fa-dumbbell"></i> Add Equipment</button>
                <button class="action-btn danger"><i class="fas fa-exclamation-triangle"></i> Reports</button>
            </div>
        </div>
        
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $users_count; ?></div>
                <div class="stat-label">Total Members</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-value"><?php echo $trainers_count; ?></div>
                <div class="stat-label">Trainers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value"><?php echo $managers_count; ?></div>
                <div class="stat-label">Equipment Managers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="stat-value"><?php echo $equipment_stats['total']; ?></div>
                <div class="stat-label">Total Equipment</div>
            </div>
        </div>
        
        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Recent Members</h3>
                        <a href="all-users.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($recent_users) > 0): ?>
                            <ul class="user-list">
                                <?php foreach ($recent_users as $user): ?>
                                    <li class="user-item">
                                        <div class="user-avatar">
                                            <?php 
                                            $user_pic = "../register/uploads/default-avatar.jpg";
                                            if (!empty($user['profile_picture'])) {
                                                if (file_exists("../register/uploads/" . $user['profile_picture'])) {
                                                    $user_pic = "../register/uploads/" . $user['profile_picture'];
                                                } elseif (file_exists("../register/" . $user['profile_picture'])) {
                                                    $user_pic = "../register/" . $user['profile_picture'];
                                                }
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User Avatar">
                                        </div>
                                        <div class="user-details">
                                            <h4 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                            <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                        <div class="user-date">
                                            Joined: <?php echo htmlspecialchars($user['join_date']); ?>
                                        </div>
                                        <div class="user-actions">
                                            <button title="View Profile"><i class="fas fa-eye"></i></button>
                                            <button title="Edit User"><i class="fas fa-edit"></i></button>
                                            <button title="Delete User"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-users"></i>
                                <p>No recent members to display.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-dumbbell"></i> Equipment Status</h3>
                        <a href="equipment.php" class="view-all">Manage Equipment</a>
                    </div>
                    <div class="card-content">
                        <div class="equipment-status">
                            <div class="status-item">
                                <div class="status-value available"><?php echo $equipment_stats['available']; ?></div>
                                <div class="status-label">Available</div>
                            </div>
                            <div class="status-item">
                                <div class="status-value in-use"><?php echo $equipment_stats['in_use']; ?></div>
                                <div class="status-label">In Use</div>
                            </div>
                            <div class="status-item">
                                <div class="status-value maintenance"><?php echo $equipment_stats['maintenance']; ?></div>
                                <div class="status-label">Maintenance</div>
                            </div>
                        </div>
                        
                        <div class="equipment-actions">
                            <button class="action-btn"><i class="fas fa-plus"></i> Add Equipment</button>
                            <button class="action-btn secondary"><i class="fas fa-tools"></i> Maintenance Report</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Popular Workout Plans</h3>
                        <a href="workout-plans.php" class="view-all">Manage Plans</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($workout_stats) > 0): ?>
                            <div class="workout-stats">
                                <?php 
                                $max_count = !empty($workout_stats) ? max(array_column($workout_stats, 'user_count')) : 0;
                                $max_count = $max_count > 0 ? $max_count : 1; 
                                foreach ($workout_stats as $workout): 
                                    $percentage = ($workout['user_count'] / $max_count) * 100;
                                ?>
                                    <div class="workout-item">
                                        <div style="display: flex; justify-content: space-between;">
                                            <span><?php echo htmlspecialchars($workout['workout_name']); ?></span>
                                            <span><?php echo $workout['user_count']; ?> users</span>
                                        </div>
                                        <div class="workout-bar">
                                            <div class="workout-progress" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-bar"></i>
                                <p>No workout plan data available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Upcoming Events</h3>
                        <a href="events.php" class="view-all">Manage Events</a>
                    </div>
                    <div class="card-content">
                        <div class="event-list">
                            <div class="event-item">
                                <div style="display: flex; justify-content: space-between;">
                                    <h4>Fitness Challenge</h4>
                                    <span>May 15, 2025</span>
                                </div>
                                <p>Annual fitness competition with prizes for top performers</p>
                            </div>
                            
                            <div class="event-item">
                                <div style="display: flex; justify-content: space-between;">
                                    <h4>Nutrition Workshop</h4>
                                    <span>May 22, 2025</span>
                                </div>
                                <p>Learn about proper nutrition for optimal fitness results</p>
                            </div>
                            
                            <div class="event-item">
                                <div style="display: flex; justify-content: space-between;">
                                    <h4>New Equipment Demo</h4>
                                    <span>June 5, 2025</span>
                                </div>
                                <p>Introduction to new gym equipment and proper usage techniques</p>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="action-btn"><i class="fas fa-plus"></i> Add New Event</button>
                        </div>
                    </div>
                </div>
            </div>
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
        
        // Initialize animations for workout progress bars
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to workout progress bars
            const progressBars = document.querySelectorAll('.workout-progress');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.querySelector('.stat-icon').style.transform = 'scale(1.1)';
                });
                card.addEventListener('mouseleave', function() {
                    this.querySelector('.stat-icon').style.transform = 'scale(1)';
                });
            });
        });

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