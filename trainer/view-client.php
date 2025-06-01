<?php 
session_start();
include '../datacon.php';

// Check if trainer is logged in
if (!isset($_SESSION['trainer_id'])) {
    header("Location: ../login.php");
    exit();
}

$trainer_id = $_SESSION['trainer_id'];
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

if (!$client_id) {
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

// Get client details with workout plan
$client_query = "
    SELECT 
        u.*,
        wp.plan_id,
        wp.plan_name,
        wp.description as plan_description,
        wp.status as plan_status,
        wp.start_date as plan_start_date,
        wp.end_date as plan_end_date,
        wp.last_updated as plan_updated,
        DATEDIFF(CURDATE(), u.date_of_birth) / 365.25 AS age
    FROM user_register_details u
    LEFT JOIN workout_plans wp ON u.table_id = wp.user_id AND wp.trainer_id = ?
    WHERE u.table_id = ?
";

$client_stmt = $conn->prepare($client_query);
$client_stmt->bind_param("ii", $trainer_id, $client_id);
$client_stmt->execute();
$client_result = $client_stmt->get_result();
$client = $client_result->fetch_assoc();

if (!$client) {
    header("Location: all-clients.php");
    exit();
}

// Get client's training sessions
$sessions_query = "
    SELECT 
        ts.*,
        CASE 
            WHEN ts.session_date = CURDATE() THEN 'Today'
            WHEN ts.session_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Tomorrow'
            WHEN ts.session_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 'Yesterday'
            ELSE DATE_FORMAT(ts.session_date, '%M %d, %Y')
        END as formatted_date
    FROM training_sessions ts
    WHERE ts.user_id = ? AND ts.trainer_id = ?
    ORDER BY ts.session_date DESC, ts.start_time DESC
    LIMIT 10
";

$sessions_stmt = $conn->prepare($sessions_query);
$sessions_stmt->bind_param("ii", $client_id, $trainer_id);
$sessions_stmt->execute();
$sessions_result = $sessions_stmt->get_result();
$sessions = $sessions_result->fetch_all(MYSQLI_ASSOC);

// Get workout plan exercises
$exercises_query = "
    SELECT 
        wpe.*,
        wt.workout_name
    FROM workout_plan_exercises wpe
    JOIN workout_plan wt ON wpe.plan_id = wt.table_id
    WHERE wpe.plan_id = ?
    ORDER BY wpe.plan_id
";

$exercises = [];
if ($client['plan_id']) {
    $exercises_stmt = $conn->prepare($exercises_query);
    $exercises_stmt->bind_param("i", $client['plan_id']);
    $exercises_stmt->execute();
    $exercises_result = $exercises_stmt->get_result();
    $exercises = $exercises_result->fetch_all(MYSQLI_ASSOC);
}

// Get client statistics
$stats_query = "
    SELECT 
        COUNT(CASE WHEN ts.session_status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN ts.session_status = 'scheduled' THEN 1 END) as scheduled_sessions,
        COUNT(CASE WHEN ts.session_status = 'cancelled' THEN 1 END) as cancelled_sessions,
        COUNT(ts.session_id) as total_sessions,
        MAX(ts.session_date) as last_session,
        MIN(ts.session_date) as first_session
    FROM training_sessions ts
    WHERE ts.user_id = ? AND ts.trainer_id = ?
";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("ii", $client_id, $trainer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Calculate adherence rate
$adherence_rate = 0;
if ($stats['total_sessions'] > 0) {
    $adherence_rate = round(($stats['completed_sessions'] / $stats['total_sessions']) * 100);
}

// Get trainer reviews for this client
$reviews_query = "
    SELECT 
        tr.*
    FROM trainer_reviews tr
    WHERE tr.trainer_id = ? AND tr.user_id = ?
    ORDER BY tr.review_date DESC
    LIMIT 5
";

$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("ii", $trainer_id, $client_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);


if (!function_exists('getProfilePicture')) {
    function getProfilePicture($profile_picture_name) {
        $default_pic = "../register/uploads/default-avatar.jpg";
        
        if (empty($profile_picture_name)) {
            return $default_pic;
        }
        
        // Check multiple possible paths
        $possible_paths = [
            "../register/uploads/" . $profile_picture_name,
            "../register/" . $profile_picture_name,
            "uploads/" . $profile_picture_name,
            $profile_picture_name // In case it's already a full path
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return $default_pic;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?> - Client Details</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .client-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .client-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }
        
        .client-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .client-main-info h1 {
            margin: 0 0 10px 0;
            color: white;
            font-size: 28px;
        }
        
        .client-contact {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        
        .contact-item i {
            width: 16px;
            color: #4CAF50;
        }
        
        .client-actions-header {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-primary, .btn-secondary, .btn-success {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-success {
            background: #2196F3;
            color: white;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
        }
        
        .info-card h3 {
            margin: 0 0 20px 0;
            color: white;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .info-value {
            color: white;
            font-weight: 500;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #4CAF50;
            color: white;
        }
        
        .status-completed {
            background: #2196F3;
            color: white;
        }
        
        .status-paused {
            background: #FF9800;
            color: white;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: #4CAF50;
            transition: width 0.3s ease;
        }
        
        .sessions-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.3s ease;
        }
        
        .session-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .session-info h4 {
            margin: 0 0 5px 0;
            color: white;
            font-size: 14px;
        }
        
        .session-details {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
        }
        
        .session-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: #4CAF50;
            color: white;
        }
        
        .status-scheduled {
            background: #2196F3;
            color: white;
        }
        
        .status-cancelled {
            background: #f44336;
            color: white;
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
            .client-header {
                flex-direction: column;
                text-align: center;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .client-actions-header {
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
                        <?php 
                                    // Use the improved profile picture function
                                    $trainer_pic = getProfilePicture($trainer_data['profile_picture'] ?? '');
                                    ?>
                        <img src="<?php echo htmlspecialchars($trainer_pic); ?>" alt="Profile Picture">
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
                <a href="all-clients.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to All Clients
                </a>
            </div>
            
            <div class="client-header">
               <div class="client-avatar-large">
                <?php 
                // Use the improved profile picture function
                $client_pic = getProfilePicture($client['profile_picture'] ?? '');
                ?>
                <img src="<?php echo htmlspecialchars($client_pic); ?>" 
                    alt="<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?> Avatar"
                    onerror="this.src='../register/uploads/default-avatar.jpg'"
                    loading="lazy">
            </div>
                <div class="client-main-info">
                    <h1><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h1>
                    <div class="client-contact">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($client['email']); ?></span>
                        </div>
                        <?php if ($client['contact_number']): ?>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($client['contact_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="contact-item">
                            <i class="fas fa-birthday-cake"></i>
                            <span><?php echo floor($client['age']); ?> years old</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-calendar"></i>
                            <span>Member since <?php echo date('M Y', strtotime($client['date_of_registration'])); ?></span>
                        </div>
                    </div>
                    <div class="client-actions-header">
                        <?php if ($client['plan_id']): ?>
                        <a href="edit-plan.php?plan_id=<?php echo $client['plan_id']; ?>" class="btn-primary">
                            <i class="fas fa-edit"></i> Edit Plan
                        </a>
                        <?php else: ?>
                        <a href="create-plan.php?user_id=<?php echo $client['table_id']; ?>" class="btn-primary">
                            <i class="fas fa-plus"></i> Create Plan
                        </a>
                        <?php endif; ?>
                        <button class="btn-secondary" onclick="messageClient()">
                            <i class="fas fa-comment"></i> Send Message
                        </button>
                        <button class="btn-success" onclick="scheduleSession()">
                            <i class="fas fa-calendar-plus"></i> Schedule Session
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($client['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo $client['contact_number'] ? htmlspecialchars($client['contact_number']) : 'Not provided'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value"><?php echo $client['date_of_birth'] ? date('M d, Y', strtotime($client['date_of_birth'])) : 'Not provided'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Age</span>
                        <span class="info-value"><?php echo floor($client['age']); ?> years</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender</span>
                        <span class="info-value"><?php echo $client['gender'] ? ucfirst($client['gender']) : 'Not specified'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location</span>
                        <span class="info-value"><?php echo $client['location'] ? htmlspecialchars($client['location']) : 'Not provided'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($client['date_of_registration'])); ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-chart-line"></i> Training Statistics</h3>
                    <div class="info-row">
                        <span class="info-label">Total Sessions</span>
                        <span class="info-value"><?php echo $stats['total_sessions']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Completed Sessions</span>
                        <span class="info-value"><?php echo $stats['completed_sessions']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Scheduled Sessions</span>
                        <span class="info-value"><?php echo $stats['scheduled_sessions']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Adherence Rate</span>
                        <span class="info-value"><?php echo $adherence_rate; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $adherence_rate; ?>%"></div>
                    </div>
                    <?php if ($stats['first_session']): ?>
                    <div class="info-row">
                        <span class="info-label">First Session</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($stats['first_session'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($stats['last_session']): ?>
                    <div class="info-row">
                        <span class="info-label">Last Session</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($stats['last_session'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($client['plan_id']): ?>
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="fas fa-dumbbell"></i> Current Workout Plan</h3>
                    <div class="info-row">
                        <span class="info-label">Plan Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($client['plan_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="status-badge status-<?php echo $client['plan_status']; ?>">
                            <?php echo ucfirst($client['plan_status']); ?>
                        </span>
                    </div>
                    <?php if ($client['plan_start_date']): ?>
                    <div class="info-row">
                        <span class="info-label">Start Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($client['plan_start_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($client['plan_end_date']): ?>
                    <div class="info-row">
                        <span class="info-label">End Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($client['plan_end_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($client['plan_updated'])); ?></span>
                    </div>
                    <?php if ($client['plan_description']): ?>
                    <div class="info-row">
                        <span class="info-label">Description</span>
                        <span class="info-value"><?php echo htmlspecialchars($client['plan_description']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-history"></i> Recent Sessions</h3>
                    <div class="sessions-list">
                        <?php if (count($sessions) > 0): ?>
                            <?php foreach ($sessions as $session): ?>
                                <div class="session-item">
                                    <div class="session-info">
                                        <h4><?php echo htmlspecialchars($session['session_type']); ?></h4>
                                        <div class="session-details">
                                            <?php echo $session['formatted_date']; ?> at <?php echo date('g:i A', strtotime($session['start_time'])); ?>
                                            <?php if ($session['notes']): ?>
                                                <br><?php echo htmlspecialchars($session['notes']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="session-status status-<?php echo $session['session_status']; ?>">
                                        <?php echo ucfirst($session['session_status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: rgba(255, 255, 255, 0.7); text-align: center; padding: 20px;">
                                No sessions recorded yet.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (count($reviews) > 0): ?>
            <div class="info-card">
                <h3><i class="fas fa-star"></i> Client Reviews</h3>
                <div class="sessions-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="session-item">
                            <div class="session-info">
                                <h4>Rating: <?php echo $review['rating']; ?>/5 Stars</h4>
                                <div class="session-details">
                                    <?php echo htmlspecialchars($review['review_text']); ?>
                                    <br><small>Reviewed on <?php echo date('M d, Y', strtotime($review['review_date'])); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
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
        function messageClient() {
            Toastify({
                text: "Opening message composer...",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#2196F3",
                close: true
            }).showToast();
        }
        
        function scheduleSession() {
            Toastify({
                text: "Opening session scheduler...",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#4CAF50",
                close: true
            }).showToast();
        }
        
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
