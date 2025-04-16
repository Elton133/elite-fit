<?php
    session_start();
    require_once('../datacon.php');

    // Redirect if session variables are not set or user is not a trainer
    // if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || $_SESSION['role'] !== 'trainer') {
    //     header("Location: ../login/index.php");
    //     exit();
    // }

    $email = $_SESSION['email'];
    $table_id = $_SESSION['table_id'];

    // Fetch trainer data
    $sql_trainer = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ? AND role = 'trainer'";
    $stmt_trainer = $conn->prepare($sql_trainer);
    $stmt_trainer->bind_param("s", $email);
    $stmt_trainer->execute();
    $result_trainer = $stmt_trainer->get_result();
    $trainer_data = $result_trainer->fetch_assoc();
    $stmt_trainer->close();

    // Handle profile picture
    $profile_pic = "../register/uploads/default-avatar.jpg"; 
    if (!empty($trainer_data['profile_picture']) && file_exists("../register/uploads/" . $trainer_data['profile_picture'])) {
        $profile_pic = "../register/uploads/" . $trainer_data['profile_picture'];
    }

    // Fetch pending workout plan requests
    $sql_pending = "SELECT wr.request_id, wr.user_id, wr.request_date, wr.status, 
                    u.first_name, u.last_name, u.profile_picture,
                    ufd.fitness_goal_1, ufd.fitness_goal_2, ufd.fitness_goal_3, ufd.experience_level
                    FROM workout_requests wr
                    JOIN user_register_details u ON wr.user_id = u.table_id
                    JOIN user_fitness_details ufd ON wr.user_id = ufd.table_id
                    WHERE wr.trainer_id = ? AND wr.status = 'pending'
                    ORDER BY wr.request_date DESC
                    LIMIT 5";
    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param("i", $trainer_data['table_id']);
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();
    $pending_requests = [];
    while ($row = $result_pending->fetch_assoc()) {
        $pending_requests[] = $row;
    }
    $stmt_pending->close();

    // Fetch active clients
    $sql_active = "SELECT u.table_id, u.first_name, u.last_name, u.profile_picture,
                wp.plan_id, wp.plan_name, wp.start_date, wp.end_date, wp.last_updated
                FROM workout_plans wp
                JOIN user_register_details u ON wp.user_id = u.table_id
                WHERE wp.trainer_id = ? AND wp.status = 'active'
                ORDER BY wp.last_updated DESC
                LIMIT 5";
    $stmt_active = $conn->prepare($sql_active);
    $stmt_active->bind_param("i", $trainer_data['table_id']);
    $stmt_active->execute();
    $result_active = $stmt_active->get_result();
    $active_clients = [];
    while ($row = $result_active->fetch_assoc()) {
        $active_clients[] = $row;
    }
    $stmt_active->close();

    // Fetch trainer stats
    $sql_stats = "SELECT 
                (SELECT COUNT(*) FROM workout_plans WHERE trainer_id = ? AND status = 'active') as active_plans,
                (SELECT COUNT(*) FROM workout_requests WHERE trainer_id = ? AND status = 'pending') as pending_requests,
                (SELECT COUNT(DISTINCT user_id) FROM workout_plans WHERE trainer_id = ?) as total_clients,
                (SELECT COUNT(*) FROM trainer_reviews WHERE trainer_id = ?) as total_reviews";
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("iiii", $trainer_data['table_id'], $trainer_data['table_id'], $trainer_data['table_id'], $trainer_data['table_id']);
    $stmt_stats->execute();
    $trainer_stats = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();

    // Calculate average rating
    $sql_rating = "SELECT AVG(rating) as avg_rating FROM trainer_reviews WHERE trainer_id = ?";
    $stmt_rating = $conn->prepare($sql_rating);
    $stmt_rating->bind_param("i", $trainer_data['table_id']);
    $stmt_rating->execute();
    $avg_rating = $stmt_rating->get_result()->fetch_assoc()['avg_rating'] ?? 0;
    $avg_rating = number_format($avg_rating, 1);
    $stmt_rating->close();

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
    <title>Trainer Dashboard - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    .user-list{
        list-style: none;
        padding: 0;
        margin: 0;
        text-decoration: none;
    }
    .user-actions{
        display: flex;
        gap:10px;
        
    }
    .user-actions a{
        text-decoration: none;
        
    }
</style>
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
                <a href="create-plan.php" class="action-btn"><i class="fas fa-plus"></i> Create Workout Plan</a>
                <button class="action-btn secondary"><i class="fas fa-calendar-alt"></i> Schedule Session</button>
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
                                            <?php 
                                            $user_pic = "../register/uploads/default-avatar.jpg";
                                            if (!empty($request['profile_picture']) && file_exists("../register/uploads/" . $request['profile_picture'])) {
                                                $user_pic = "../register/uploads/" . $request['profile_picture'];
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User Avatar">
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
                                            <?php 
                                            $client_pic = "../register/uploads/default-avatar.jpg";
                                            if (!empty($client['profile_picture']) && file_exists("../register/uploads/" . $client['profile_picture'])) {
                                                $client_pic = "../register/uploads/" . $client['profile_picture'];
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($client_pic); ?>" alt="Client Avatar">
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
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h3>
                        <a href="schedule.php" class="view-all">View Schedule</a>
                    </div>
                    <div class="card-content">
                        <div class="class-list">
                            <div class="class-item">
                                <div class="class-time">
                                    <span class="time">09:00</span>
                                    <span class="day">Today</span>
                                </div>
                                <div class="class-details">
                                    <h4>Personal Training - Sophia Chen</h4>
                                    <p>Focus: Upper Body Strength</p>
                                </div>
                                <button class="book-btn">Start</button>
                            </div>
                            
                            <div class="class-item">
                                <div class="class-time">
                                    <span class="time">11:30</span>
                                    <span class="day">Today</span>
                                </div>
                                <div class="class-details">
                                    <h4>Plan Review - Michael Johnson</h4>
                                    <p>Monthly Progress Check</p>
                                </div>
                                <button class="book-btn">Start</button>
                            </div>
                            
                            <div class="class-item">
                                <div class="class-time">
                                    <span class="time">15:00</span>
                                    <span class="day">Today</span>
                                </div>
                                <div class="class-details">
                                    <h4>Group HIIT Class</h4>
                                    <p>8 participants</p>
                                </div>
                                <button class="book-btn">Start</button>
                            </div>
                            
                            <div class="class-item">
                                <div class="class-time">
                                    <span class="time">09:30</span>
                                    <span class="day">Tomorrow</span>
                                </div>
                                <div class="class-details">
                                    <h4>Initial Consultation - New Client</h4>
                                    <p>Fitness Assessment</p>
                                </div>
                                <button class="book-btn" style="background: rgba(255,255,255,0.2);">Prepare</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        const backgrounds = [
            'url("https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1470&q=80")',
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