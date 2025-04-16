<?php
    session_start();
    require_once('../datacon.php');

    // Redirect if session variables are not set or user is not an equipment manager
    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || $_SESSION['role'] !== 'equipment_manager') {
        header("Location: ../login/index.php");
        exit();
    }

    $email = $_SESSION['email'];
    $table_id = $_SESSION['table_id'];

    // Fetch manager data
    $sql_manager = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ? AND role = 'equipment_manager'";
    $stmt_manager = $conn->prepare($sql_manager);
    $stmt_manager->bind_param("s", $email);
    $stmt_manager->execute();
    $result_manager = $stmt_manager->get_result();
    $manager_data = $result_manager->fetch_assoc();
    $stmt_manager->close();


    if (!empty($manager_data['profile_picture']) && file_exists("../register/uploads/" . $manager_data['profile_picture'])) {
        $profile_pic = "../register/uploads/" . $manager_data['profile_picture'];
    }

    // Fetch equipment statistics
    $sql_stats = "SELECT 
                COUNT(*) as total_equipment,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                FROM equipment_inventory";
    $result_stats = $conn->query($sql_stats);
    $equipment_stats = $result_stats->fetch_assoc();

    // Fetch equipment under maintenance
    $sql_maintenance = "SELECT equipment_id, name, type, location, maintenance_notes, last_maintenance_date, next_maintenance_date
                    FROM equipment_inventory
                    WHERE status = 'maintenance'
                    ORDER BY next_maintenance_date ASC
                    LIMIT 5";
    $result_maintenance = $conn->query($sql_maintenance);
    $maintenance_equipment = [];
    while ($row = $result_maintenance->fetch_assoc()) {
        $maintenance_equipment[] = $row;
    }

    // Fetch recently used equipment
    $sql_recent = "SELECT ei.equipment_id, ei.name, ei.type, ei.location, eu.user_id, eu.start_time, eu.end_time,
                u.first_name, u.last_name
                FROM equipment_usage eu
                JOIN equipment_inventory ei ON eu.equipment_id = ei.equipment_id
                JOIN user_register_details u ON eu.user_id = u.table_id
                ORDER BY eu.end_time DESC
                LIMIT 5";
    $result_recent = $conn->query($sql_recent);
    $recent_usage = [];
    while ($row = $result_recent->fetch_assoc()) {
        $recent_usage[] = $row;
    }

    // Fetch maintenance schedule
    $sql_schedule = "SELECT equipment_id, name, type, location, next_maintenance_date
                    FROM equipment_inventory
                    WHERE next_maintenance_date IS NOT NULL
                    ORDER BY next_maintenance_date ASC
                    LIMIT 5";
    $result_schedule = $conn->query($sql_schedule);
    $maintenance_schedule = [];
    while ($row = $result_schedule->fetch_assoc()) {
        $maintenance_schedule[] = $row;
    }

    // greeting
    $hour = date("H");

    // Determine the time of day
    if ($hour >= 5 && $hour < 12) {
        $greeting = "Good morning";
    } elseif ($hour >= 12 && $hour < 17) {
        $greeting = "Good afternoon";
    } elseif ($hour >= 17 && $hour < 21) {
        $greeting = "Good evening";
    } else {
        $greeting = "Good night";
    }
?>

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
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        const backgrounds = [
            'url("https://images.unsplash.com/photo-1689514226761-336eaf77e311?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D")',
            'url("https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1470&q=80")'
        ];
        
        let currentBg = 0;
        const bgElement = document.querySelector('.background');
        
        function changeBackground() {
            bgElement.style.backgroundImage = backgrounds[currentBg];
            currentBg = (currentBg + 1) % backgrounds.length;
        }
        
        changeBackground();
        setInterval(changeBackground, 8000);
        
        // Toggle dropdown menu
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
