<?php 
session_start();
include '../datacon.php';

// Check if trainer is logged in
if (!isset($_SESSION['trainer_id'])) {
    header("Location: ../login.php");
    exit();
}

$trainer_id = $_SESSION['trainer_id'];

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

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';

// Build query based on filters
$where_conditions = ["we.trainer_id = ?"];
$params = [$trainer_id];
$param_types = "i";

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR we.notes LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= "sss";
}

// Filter conditions
switch ($filter) {
    case 'pending':
        $where_conditions[] = "we.status = 'pending'";
        break;
    case 'urgent':
        $where_conditions[] = "we.request_date >= DATE_SUB(NOW(), INTERVAL 3 DAY) AND we.status = 'pending'";
        break;
}

// Sort conditions
$order_by = "we.request_date DESC";
switch ($sort) {
    case 'date':
        $order_by = "we.request_date DESC";
        break;
    case 'name':
        $order_by = "u.first_name ASC";
        break;
}

$where_clause = implode(" AND ", $where_conditions);

// Get all workout requests
$requests_query = "
    SELECT 
        we.*,
        u.first_name,
        u.last_name,
        u.email,
        u.profile_picture,
        u.contact_number,
        u.date_of_birth,
        DATEDIFF(CURDATE(), u.date_of_birth) / 365.25 AS age,
        DATEDIFF(CURDATE(), we.request_date) as days_ago
    FROM workout_requests we
    JOIN user_register_details u ON we.user_id = u.table_id
    WHERE $where_clause
    ORDER BY $order_by
";

$requests_stmt = $conn->prepare($requests_query);
if (!empty($params)) {
    $requests_stmt->bind_param($param_types, ...$params);
}
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
$all_requests = $requests_result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN we.status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN we.status = 'approved' THEN 1 END) as approved_requests,
        COUNT(CASE WHEN we.request_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_requests,
        COUNT(CASE WHEN we.request_date >= DATE_SUB(NOW(), INTERVAL 3 DAY) AND we.status = 'pending' THEN 1 END) as urgent_requests
    FROM workout_requests we
    WHERE we.trainer_id = ?
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $trainer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Function to get priority based on days
function getPriority($days_ago) {
    if ($days_ago >= 5) return 'high';
    if ($days_ago >= 2) return 'medium';
    return 'low';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Requests - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .requests-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .requests-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .urgent {
            border-left: 4px solid #f44336;
        }
        
        .filters-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
          .filter-select option{
            color: black;
        }
        .sort-select option{
            color: black;
        }
        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }
        
        .filter-select, .sort-select {
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
        }
        
        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .request-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        .request-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .request-card.urgent {
            border-left: 4px solid #f44336;
        }
        
        .request-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .request-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            position: relative;
        }
        
        .request-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .priority-indicator {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .priority-high {
            background: #f44336;
        }
        
        .priority-medium {
            background: #ff9800;
        }
        
        .priority-low {
            background: #4caf50;
        }
        
        .request-info h3 {
            margin: 0 0 5px 0;
            color: white;
            font-size: 18px;
        }
        
        .request-email {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin: 0 0 5px 0;
        }
        
        .request-time {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }
        
        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
        }
        
        .detail-title {
            color: #4CAF50;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .detail-content {
            color: white;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .request-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .btn-create-plan, .btn-view-details, .btn-decline {
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
            font-size: 14px;
        }
        
        .btn-create-plan {
            background: #4CAF50;
            color: white;
        }
        
        .btn-view-details {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-decline {
            background: #f44336;
            color: white;
        }
        
        .btn-create-plan:hover, .btn-view-details:hover, .btn-decline:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .no-requests {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-requests i {
            font-size: 64px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.3);
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
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .request-header {
                flex-direction: column;
                text-align: center;
            }
            
            .request-details {
                grid-template-columns: 1fr;
            }
            
            .request-actions {
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
                    <span class="notification-badge"><?php echo $stats['pending_requests']; ?></span>
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
        
        <div class="dashboard">
            <div class="back-button">
                <a href="trainers.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
            
            <div class="requests-header">
                <h2><i class="fas fa-bell"></i> All Workout Plan Requests</h2>
                <div class="requests-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card urgent">
                        <div class="stat-number"><?php echo $stats['urgent_requests']; ?></div>
                        <div class="stat-label">Urgent (3+ days)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['approved_requests']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['recent_requests']; ?></div>
                        <div class="stat-label">This Week</div>
                    </div>
                </div>
                
                <div class="filters-section">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, email, or notes..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <select class="filter-select" id="filterSelect">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Requests</option>
                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                        <option value="urgent" <?php echo $filter === 'urgent' ? 'selected' : ''; ?>>Urgent (3+ days)</option>
                    </select>
                    
                    <select class="sort-select" id="sortSelect">
                        <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Sort by Date</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                    </select>
                </div>
            </div>
            
            <?php if (count($all_requests) > 0): ?>
                <div class="requests-list">
                    <?php foreach ($all_requests as $request): ?>
                        <?php 
                        $priority = getPriority($request['days_ago']);
                        ?>
                        <div class="request-card <?php echo $request['days_ago'] >= 3 ? 'urgent' : ''; ?>">
                            <div class="request-header">
                                <div class="request-avatar">
                                    <?php 
                                    $user_pic = "../register/uploads/default-avatar.jpg";
                                    if (!empty($request['profile_picture']) && file_exists("../register/uploads/" . $request['profile_picture'])) {
                                        $user_pic = "../register/uploads/" . $request['profile_picture'];
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User Avatar">
                                    <div class="priority-indicator priority-<?php echo $priority; ?>"></div>
                                </div>
                                <div class="request-info">
                                    <h3><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                                    <p class="request-email"><?php echo htmlspecialchars($request['email']); ?></p>
                                    <p class="request-time">
                                        Requested <?php echo $request['days_ago']; ?> day<?php echo $request['days_ago'] != 1 ? 's' : ''; ?> ago
                                        <?php if ($request['days_ago'] >= 3): ?>
                                            <span style="color: #f44336; font-weight: 600;"> - URGENT</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="request-details">
                                <div class="detail-section">
                                    <div class="detail-title">Request Information</div>
                                    <div class="detail-content">
                                        <strong>Status:</strong> <?php echo ucfirst($request['status']); ?><br>
                                        <strong>Request Date:</strong> <?php echo date('M d, Y', strtotime($request['request_date'])); ?><br>
                                        <?php if ($request['notes']): ?>
                                            <strong>Notes:</strong> <?php echo htmlspecialchars($request['notes']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="detail-section">
                                    <div class="detail-title">Personal Information</div>
                                    <div class="detail-content">
                                        <strong>Age:</strong> <?php echo floor($request['age']); ?> years<br>
                                        <?php if ($request['contact_number']): ?>
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($request['contact_number']); ?><br>
                                        <?php endif; ?>
                                        <strong>Member Since:</strong> <?php echo date('M Y', strtotime($request['request_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="request-actions">
                                <!-- <button class="btn-view-details" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Full Details
                                </button> -->
                                <a href="create-plan.php?request_id=<?php echo $request['request_id']; ?>" class="btn-create-plan">
                                    <i class="fas fa-dumbbell"></i> Create Workout Plan
                                </a>
                                <button class="btn-decline" onclick="declineRequest(<?php echo $request['request_id']; ?>)">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-requests">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No requests found</h3>
                    <p>No workout plan requests match your current search criteria.</p>
                    <a href="all-requests.php" class="btn-view-details" style="margin-top: 20px; display: inline-block;">
                        <i class="fas fa-refresh"></i> Show All Requests
                    </a>
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
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            updateFilters();
        });
        
        document.getElementById('filterSelect').addEventListener('change', function() {
            updateFilters();
        });
        
        document.getElementById('sortSelect').addEventListener('change', function() {
            updateFilters();
        });
        
        function updateFilters() {
            const search = document.getElementById('searchInput').value;
            const filter = document.getElementById('filterSelect').value;
            const sort = document.getElementById('sortSelect').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (filter !== 'all') params.append('filter', filter);
            if (sort !== 'date') params.append('sort', sort);
            
            const url = 'all-requests.php' + (params.toString() ? '?' + params.toString() : '');
            window.location.href = url;
        }
        
        function viewRequestDetails(requestId) {
            Toastify({
                text: "Opening detailed request view...",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#2196F3",
                close: true
            }).showToast();
        }
        
        function declineRequest(requestId) {
            if (confirm('Are you sure you want to decline this workout plan request?')) {
                Toastify({
                    text: "Request declined successfully",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#f44336",
                    close: true
                }).showToast();
                
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
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
