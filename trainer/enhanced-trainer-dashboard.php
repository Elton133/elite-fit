<?php include '../services/trainer-dashboard-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="enhanced-dashboard-styles.css">
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
            
            <div class="header-search">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search clients, plans, sessions..." id="globalSearch">
                </div>
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
                <p>You have <?php echo $trainer_stats['pending_requests']; ?> pending workout plan requests and <?php echo count($sessions); ?> sessions today.</p>
            </div>
            <div class="quick-actions">
                <a href="create-plan.php" class="action-btn"><i class="fas fa-plus"></i> Create Plan</a>
                <a href="#session-card" class="action-btn secondary"><i class="fas fa-calendar-alt"></i> Today's Sessions</a>
                <a href="client-progress.php" class="action-btn tertiary"><i class="fas fa-chart-line"></i> Progress Reports</a>
            </div>
        </div>
        
        <div class="dashboard">
            <!-- Enhanced Stats Row -->
            <div class="dashboard-row">
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Performance Overview</h3>
                        <div class="time-filter">
                            <select id="statsTimeFilter">
                                <option value="week">This Week</option>
                                <option value="month" selected>This Month</option>
                                <option value="quarter">This Quarter</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon clients-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Active Clients</h4>
                                    <p class="stat-value"><?php echo $trainer_stats['active_plans']; ?></p>
                                    <p class="stat-change positive">+3 this week</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon sessions-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Sessions This Month</h4>
                                    <p class="stat-value">47</p>
                                    <p class="stat-change positive">+12% vs last month</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon rating-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Average Rating</h4>
                                    <p class="stat-value"><?php echo $avg_rating; ?>/5.0</p>
                                    <p class="stat-change neutral">From <?php echo $trainer_stats['total_reviews']; ?> reviews</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon earnings-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Monthly Earnings</h4>
                                    <p class="stat-value">$2,340</p>
                                    <p class="stat-change positive">+8% vs last month</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="dashboard-row">
                <!-- Enhanced Active Clients -->
                <div class="dashboard-card clients-card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Active Clients</h3>
                        <div class="header-actions">
                            <div class="client-search">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search clients..." id="clientSearch">
                            </div>
                            <div class="client-filter">
                                <select id="clientFilter">
                                    <option value="all">All Clients</option>
                                    <option value="recent">Recently Active</option>
                                    <option value="needs_attention">Needs Attention</option>
                                    <option value="high_progress">High Progress</option>
                                </select>
                            </div>
                            <a href="all-clients.php" class="view-all">View All</a>
                        </div>
                    </div>
                    <div class="card-content">
                        <?php if (count($active_clients) > 0): ?>
                            <div class="clients-list" id="clientsList">
                                <?php foreach ($active_clients as $client): ?>
                                    <div class="client-item" data-client-id="<?php echo $client['table_id']; ?>">
                                        <div class="client-avatar">
                                            <?php 
                                            $client_pic = "../register/uploads/default-avatar.jpg";
                                            if (!empty($client['profile_picture']) && file_exists("../register/uploads/" . $client['profile_picture'])) {
                                                $client_pic = "../register/uploads/" . $client['profile_picture'];
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($client_pic); ?>" alt="Client Avatar">
                                            <div class="status-indicator active"></div>
                                        </div>
                                        <div class="client-details">
                                            <div class="client-header">
                                                <h4 class="client-name"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
                                                <span class="client-progress">
                                                    <i class="fas fa-chart-line"></i>
                                                    <span class="progress-text">85% Complete</span>
                                                </span>
                                            </div>
                                            <div class="client-info">
                                                <span class="plan-name">
                                                    <i class="fas fa-clipboard-list"></i>
                                                    <?php echo htmlspecialchars($client['plan_name']); ?>
                                                </span>
                                                <span class="last-session">
                                                    <i class="fas fa-clock"></i>
                                                    Last session: <?php echo date('M d', strtotime($client['last_updated'])); ?>
                                                </span>
                                                <span class="next-session">
                                                    <i class="fas fa-calendar"></i>
                                                    Next: Tomorrow 2:00 PM
                                                </span>
                                            </div>
                                            <div class="client-metrics">
                                                <div class="metric">
                                                    <span class="metric-label">Adherence</span>
                                                    <div class="metric-bar">
                                                        <div class="metric-fill" style="width: 85%"></div>
                                                    </div>
                                                    <span class="metric-value">85%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="client-actions">
                                            <button class="action-btn-small message" title="Send Message">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                            <button class="action-btn-small progress" title="View Progress">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <a href="view-client.php?client_id=<?php echo $client['table_id']; ?>" class="action-btn-small view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-plan.php?plan_id=<?php echo $client['plan_id']; ?>" class="action-btn-small edit" title="Edit Plan">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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

                <!-- Recent Activity Feed -->
                <div class="dashboard-card activity-card">
                    <div class="card-header">
                        <h3><i class="fas fa-activity"></i> Recent Activity</h3>
                        <a href="activity-log.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <div class="activity-feed">
                            <div class="activity-item">
                                <div class="activity-icon completed">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Sarah Johnson</strong> completed workout session</p>
                                    <span class="activity-time">2 hours ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon new">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Mike Chen</strong> requested a new workout plan</p>
                                    <span class="activity-time">4 hours ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon progress">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Emma Davis</strong> achieved weight loss goal</p>
                                    <span class="activity-time">1 day ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon message">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>John Smith</strong> sent you a message</p>
                                    <span class="activity-time">2 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests and Sessions Row -->
            <div class="dashboard-row">
                <!-- Enhanced Pending Requests -->
                <div class="dashboard-card requests-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Pending Requests</h3>
                        <div class="header-badge">
                            <span class="priority-badge high"><?php echo $trainer_stats['pending_requests']; ?> Urgent</span>
                        </div>
                        <a href="all-requests.php" class="view-all">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (count($pending_requests) > 0): ?>
                            <div class="requests-list">
                                <?php foreach ($pending_requests as $request): ?>
                                    <div class="request-item">
                                        <div class="request-avatar">
                                            <?php 
                                            $user_pic = "../register/uploads/default-avatar.jpg";
                                            if (!empty($request['profile_picture']) && file_exists("../register/uploads/" . $request['profile_picture'])) {
                                                $user_pic = "../register/uploads/" . $request['profile_picture'];
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User Avatar">
                                            <div class="request-priority high"></div>
                                        </div>
                                        <div class="request-details">
                                            <div class="request-header">
                                                <h4 class="request-name"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                                                <span class="request-time">2 days ago</span>
                                            </div>
                                            <div class="request-info">
                                                <div class="request-goal">
                                                    <i class="fas fa-target"></i>
                                                    <span>Goal: <?php echo htmlspecialchars($request['fitness_goal_1']); ?></span>
                                                </div>
                                                <div class="request-experience">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <span>Experience: Level <?php echo htmlspecialchars($request['experience_level']); ?></span>
                                                </div>
                                                <div class="request-urgency">
                                                    <i class="fas fa-clock"></i>
                                                    <span>Wants to start: ASAP</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="request-actions">
                                            <a href="create-plan.php?request_id=<?php echo $request['request_id']; ?>" class="action-btn primary">
                                                <i class="fas fa-dumbbell"></i> Create Plan
                                            </a>
                                            <button class="action-btn secondary" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No pending requests at the moment.</p>
                                <p class="sub-text">Great job staying on top of your requests!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Sessions -->
                <div id="session-card" class="dashboard-card sessions-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Today's Sessions</h3>
                        <div class="session-summary">
                            <span class="session-count"><?php echo count($sessions); ?> sessions</span>
                            <span class="session-time">Next in 30 min</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="sessions-timeline">
                            <?php if (count($sessions) > 0): ?>
                                <?php foreach ($sessions as $session): ?>
                                    <?php
                                        $sessionDate = date("Y-m-d");
                                        $isToday = $session['session_date'] === $sessionDate;
                                        $timeUntil = $isToday ? "In " . date("H:i", strtotime($session['start_time'])) : date("M j", strtotime($session['session_date']));
                                        $userName = "Client #" . $session['user_id'];
                                    ?>
                                    <div class="session-item <?php echo $isToday ? 'today' : 'upcoming'; ?>">
                                        <div class="session-time">
                                            <span class="time"><?php echo date("H:i", strtotime($session['start_time'])); ?></span>
                                            <span class="duration">60 min</span>
                                        </div>
                                        <div class="session-details">
                                            <div class="session-header">
                                                <h4><?php echo htmlspecialchars($session['session_type']); ?></h4>
                                                <span class="session-status <?php echo $isToday ? 'upcoming' : 'scheduled'; ?>">
                                                    <?php echo $isToday ? 'Upcoming' : 'Scheduled'; ?>
                                                </span>
                                            </div>
                                            <p class="session-client">
                                                <i class="fas fa-user"></i>
                                                <?php echo $userName; ?>
                                            </p>
                                            <p class="session-notes"><?php echo htmlspecialchars($session['notes']); ?></p>
                                        </div>
                                        <div class="session-actions">
                                            <?php if ($isToday): ?>
                                                <button class="action-btn primary">
                                                    <i class="fas fa-play"></i> Start
                                                </button>
                                                <button class="action-btn secondary">
                                                    <i class="fas fa-comment"></i> Message
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn secondary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-sessions">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>No sessions scheduled for today</p>
                                    <button class="action-btn" onclick="openScheduleModal()">
                                        <i class="fas fa-plus"></i> Schedule Session
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Panel -->
            <div class="dashboard-row">
                <div class="dashboard-card quick-actions-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-actions-grid">
                            <button class="quick-action-btn" onclick="openCreatePlanModal()">
                                <i class="fas fa-plus-circle"></i>
                                <span>Create New Plan</span>
                            </button>
                            <button class="quick-action-btn" onclick="openScheduleModal()">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Schedule Session</span>
                            </button>
                            <button class="quick-action-btn" onclick="openMessageModal()">
                                <i class="fas fa-envelope"></i>
                                <span>Send Message</span>
                            </button>
                            <button class="quick-action-btn" onclick="openProgressModal()">
                                <i class="fas fa-chart-line"></i>
                                <span>Update Progress</span>
                            </button>
                            <button class="quick-action-btn" onclick="openNutritionModal()">
                                <i class="fas fa-apple-alt"></i>
                                <span>Nutrition Plan</span>
                            </button>
                            <button class="quick-action-btn" onclick="generateReport()">
                                <i class="fas fa-file-alt"></i>
                                <span>Generate Report</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>

    <!-- Modals -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Message to Client</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="messageForm">
                    <div class="form-group">
                        <label for="clientSelect">Select Client:</label>
                        <select id="clientSelect" required>
                            <option value="">Choose a client...</option>
                            <?php foreach ($active_clients as $client): ?>
                                <option value="<?php echo $client['table_id']; ?>">
                                    <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="messageText">Message:</label>
                        <textarea id="messageText" rows="4" placeholder="Type your message here..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-cancel">Cancel</button>
                        <button type="submit" class="btn-send">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="enhanced-dashboard.js"></script>
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
