<?php include '../services/equipment-manager-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Manager Dashboard - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    .user-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem; /* adjust spacing as needed */
  list-style-type: none;
}
.user-actions a{
    text-decoration: none;
    margin-top: 5px;
}
</style>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include 'equipment-sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo count($maintenance_equipment); ?></span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($manager_data['first_name'] . ' ' . $manager_data['last_name']) ?></h3>
                        <p class="user-status">Equipment Manager</p>
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
                <h2><?php echo $greeting . ', ' . htmlspecialchars($manager_data['first_name'] ?? 'Manager'); ?>!</h2>
                <p>You have <?php echo $equipment_stats['maintenance']; ?> pieces of equipment currently under maintenance.</p>
            </div>
            <div class="quick-actions">
                <button class="action-btn"><i class="fas fa-plus"></i> Add Equipment</button>
                <button class="action-btn secondary"><i class="fas fa-tools"></i> Schedule Maintenance</button>
            </div>
        </div>
        
        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Equipment Status</h3>
                    </div>
                    <div class="card-content">
                        <div class="stat-item">
                            <div class="stat-icon bmi-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Available</h4>
                                <p class="stat-value"><?php echo $equipment_stats['available']; ?></p>
                                <p class="stat-label">Ready for use</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon weight-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h4>In Use</h4>
                                <p class="stat-value"><?php echo $equipment_stats['in_use']; ?></p>
                                <p class="stat-label">Currently being used</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon height-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Maintenance</h4>
                                <p class="stat-value"><?php echo $equipment_stats['maintenance']; ?></p>
                                <p class="stat-label">Under repair/service</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tools"></i> Equipment Under Maintenance</h3>
                        <a href="maintenance-log.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($maintenance_equipment) > 0): ?>
                            <ul class="user-list">
                                <?php foreach ($maintenance_equipment as $equipment): ?>
                                    <li class="user-item">
                                        <div class="user-avatar" style="background: rgba(231, 76, 60, 0.2);">
                                            <i class="fas fa-dumbbell" style="font-size: 20px; color: #e74c3c;"></i>
                                        </div>
                                        <div class="user-details">
                                            <h4 class="user-name"><?php echo htmlspecialchars($equipment['name']); ?></h4>
                                            <p class="user-email">Type: <?php echo htmlspecialchars($equipment['type']); ?></p>
                                            <p class="user-email">Location: <?php echo htmlspecialchars($equipment['location']); ?></p>
                                        </div>
                                        <div class="user-actions">
                                            <a href="update-status.php?id=<?php echo $equipment['equipment_id']; ?>" class="action-btn" style="padding: 7px 10px; font-size: 12px;">
                                                <i class="fas fa-check"></i> Mark Available
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No equipment currently under maintenance.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Equipment Usage</h3>
                        <a href="usage-log.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($recent_usage) > 0): ?>
                            <ul class="user-list">
                                <?php foreach ($recent_usage as $usage): ?>
                                    <li class="user-item">
                                        <div class="user-avatar" style="background: rgba(52, 152, 219, 0.2);">
                                            <i class="fas fa-dumbbell" style="font-size: 20px; color: #3498db;"></i>
                                        </div>
                                        <div class="user-details">
                                            <h4 class="user-name"><?php echo htmlspecialchars($usage['name']); ?></h4>
                                            <p class="user-email">Used by: <?php echo htmlspecialchars($usage['first_name'] . ' ' . $usage['last_name']); ?></p>
                                            <p class="user-email">Time: <?php echo date('M d, H:i', strtotime($usage['end_time'])); ?></p>
                                        </div>
                                        <div class="user-actions">
                                            <a href="equipment-details.php?id=<?php echo $usage['equipment_id']; ?>" class="action-btn" style="padding: 7px 10px; font-size: 12px; background: rgba(255,255,255,0.2);">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-history" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No recent equipment usage to display.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Upcoming Maintenance</h3>
                        <a href="maintenance-schedule.php" class="view-all">View Schedule</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($maintenance_schedule) > 0): ?>
                            <div class="class-list">
                                <?php foreach ($maintenance_schedule as $schedule): ?>
                                    <div class="class-item">
                                        <div class="class-time">
                                            <span class="time"><?php echo date('d', strtotime($schedule['next_maintenance_date'])); ?></span>
                                            <span class="day"><?php echo date('M', strtotime($schedule['next_maintenance_date'])); ?></span>
                                        </div>
                                        <div class="class-details">
                                            <h4><?php echo htmlspecialchars($schedule['name']); ?></h4>
                                            <p><?php echo htmlspecialchars($schedule['type']); ?> - <?php echo htmlspecialchars($schedule['location']); ?></p>
                                        </div>
                                        <button class="book-btn">Schedule</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-calendar-check" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No upcoming maintenance scheduled.</p>
                                <button class="action-btn" style="margin-top: 15px;">
                                    <i class="fas fa-plus"></i> Schedule Maintenance
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="equipment-sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
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
