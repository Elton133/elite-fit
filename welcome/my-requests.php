<?php
session_start();
include_once "../datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all workout requests
$stmt = $conn->prepare("SELECT wr.*, u.first_name, u.last_name, u.profile_picture 
                       FROM workout_requests wr 
                       JOIN trainers td ON wr.trainer_id = td.trainer_id 
                       JOIN user_register_details u ON td.user_id = u.table_id 
                       WHERE wr.user_id = ? 
                       ORDER BY wr.request_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Workout Requests - EliteFit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .requests-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: white;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .action-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .request-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .request-card-header {
            background: rgba(30, 60, 114, 0.3);
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .request-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .request-status {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-approved {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .status-rejected {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .status-cancelled {
            background: rgba(149, 165, 166, 0.2);
            color: #95a5a6;
        }
        
        .request-card-body {
            padding: 15px;
        }
        
        .request-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .request-trainer {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .trainer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .trainer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .request-date {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .request-notes {
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .request-card-footer {
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
        }
        
        .empty-state {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 40px;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .empty-state-text {
            font-size: 18px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            position: relative;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: white;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .trainer-response {
            background: rgba(30, 60, 114, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid rgba(30, 60, 114, 0.2);
        }
        
        .response-header {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include '../welcome/sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="../register/uploads/default-avatar.jpg" alt="User Profile">
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="requests-container">
            <a href="workouts.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Workouts
            </a>
            
            <div class="section-header">
                <h2 class="section-title">My Workout Requests</h2>
                <a href="request-workout.php" class="action-btn">
                    <i class="fas fa-plus"></i> New Request
                </a>
            </div>
            
            <div class="tabs">
                <div class="tab active" data-tab="all">All Requests</div>
                <div class="tab" data-tab="pending">Pending</div>
                <div class="tab" data-tab="approved">Approved</div>
                <div class="tab" data-tab="rejected">Rejected</div>
            </div>
            
            <!-- All Requests Tab -->
            <div class="tab-content active" id="all">
                <?php if ($requests->num_rows > 0): ?>
                    <?php while ($request = $requests->fetch_assoc()): ?>
                        <div class="request-card" data-status="<?= $request['status'] ?>">
                            <div class="request-card-header">
                                <h3 class="request-title">Workout Request</h3>
                                <div class="request-status status-<?= $request['status'] ?>">
                                    <?= ucfirst($request['status']) ?>
                                </div>
                            </div>
                            <div class="request-card-body">
                                <div class="request-meta">
                                    <div class="request-trainer">
                                        <div class="trainer-avatar">
                                            <img src="<?= !empty($request['profile_picture']) ? '../register/uploads/' . htmlspecialchars($request['profile_picture']) : '../register/uploads/default-avatar.jpg' ?>" alt="Trainer">
                                        </div>
                                        <div>
                                            <div>Trainer</div>
                                            <div><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></div>
                                        </div>
                                    </div>
                                    <div class="request-date">
                                        <div>Requested</div>
                                        <div><?= date('M d, Y', strtotime($request['request_date'])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="request-notes">
                                    <strong>Your Request:</strong>
                                    <p><?= nl2br(htmlspecialchars($request['notes'])) ?></p>
                                </div>
                                
                                <?php if (!empty($request['trainer_response'])): ?>
                                    <div class="trainer-response">
                                        <div class="response-header">
                                            <i class="fas fa-reply"></i> Trainer Response:
                                        </div>
                                        <p><?= nl2br(htmlspecialchars($request['trainer_response'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="request-card-footer">
                                <?php if ($request['status'] === 'pending'): ?>
                                    <a href="cancel-request.php?id=<?= $request['id'] ?>" class="action-btn secondary" onclick="return confirm('Are you sure you want to cancel this request?')">
                                        <i class="fas fa-times"></i> Cancel Request
                                    </a>
                                <?php elseif ($request['status'] === 'approved'): ?>
                                    <a href="view-workout.php?id=<?= $request['workout_plan_id'] ?>" class="action-btn">
                                        <i class="fas fa-eye"></i> View Workout Plan
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="empty-state-text">
                            You haven't made any workout requests yet.
                        </div>
                        <a href="request-workout.php" class="action-btn">
                            <i class="fas fa-plus"></i> Request a Workout Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pending Requests Tab -->
            <div class="tab-content" id="pending">
                <div id="pending-requests-container">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Approved Requests Tab -->
            <div class="tab-content" id="approved">
                <div id="approved-requests-container">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Rejected Requests Tab -->
            <div class="tab-content" id="rejected">
                <div id="rejected-requests-container">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                    
                    // If this is a filtered tab, populate it
                    if (tabId !== 'all') {
                        populateFilteredTab(tabId);
                    }
                });
            });
            
            // Function to populate filtered tabs
            function populateFilteredTab(status) {
                const container = document.getElementById(status + '-requests-container');
                const allRequests = document.querySelectorAll('.request-card');
                let filteredHTML = '';
                let hasRequests = false;
                
                allRequests.forEach(request => {
                    if (request.getAttribute('data-status') === status) {
                        filteredHTML += request.outerHTML;
                        hasRequests = true;
                    }
                });
                
                if (!hasRequests) {
                    filteredHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="empty-state-text">
                                You don't have any ${status} workout requests.
                            </div>
                            <a href="request-workout.php" class="action-btn">
                                <i class="fas fa-plus"></i> Request a Workout Plan
                            </a>
                        </div>
                    `;
                }
                
                container.innerHTML = filteredHTML;
            }
            
            // Show toast message if exists
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
        });
    </script>
</body>
</html>
