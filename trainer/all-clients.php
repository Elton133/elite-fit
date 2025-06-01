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
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build query based on filters
$where_conditions = ["wp.trainer_id = ?"];
$params = [$trainer_id];
$param_types = "i";

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR wp.plan_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= "sss";
}

// Filter conditions
switch ($filter) {
    case 'active':
        $where_conditions[] = "wp.status = 'active'";
        break;
    case 'completed':
        $where_conditions[] = "wp.status = 'completed'";
        break;
    case 'recent':
        $where_conditions[] = "wp.last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
}

// Sort conditions
$order_by = "u.first_name ASC";
switch ($sort) {
    case 'name':
        $order_by = "u.first_name ASC";
        break;
    case 'plan':
        $order_by = "wp.plan_name ASC";
        break;
    case 'date':
        $order_by = "wp.last_updated DESC";
        break;
    case 'status':
        $order_by = "wp.status ASC";
        break;
}

$where_clause = implode(" AND ", $where_conditions);

// Get all clients with their workout plans
$clients_query = "
    SELECT 
        wp.*,
        u.first_name,
        u.last_name,
        u.email,
        u.profile_picture,
        u.contact_number,
        u.date_of_birth,
        DATEDIFF(CURDATE(), u.date_of_birth) / 365.25 AS age,
        COUNT(CASE WHEN ws.session_status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(ws.session_id) as total_sessions,
        MAX(ws.session_date) as last_session_date
    FROM workout_plans wp
    JOIN user_register_details u ON wp.user_id = u.table_id
    LEFT JOIN training_sessions ws ON wp.user_id = ws.user_id AND ws.trainer_id = wp.trainer_id
    WHERE $where_clause
    GROUP BY wp.plan_id, u.table_id
    ORDER BY $order_by
";

$clients_stmt = $conn->prepare($clients_query);
if (!empty($params)) {
    $clients_stmt->bind_param($param_types, ...$params);
}
$clients_stmt->execute();
$clients_result = $clients_stmt->get_result();
$all_clients = $clients_result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_clients,
        COUNT(CASE WHEN wp.status = 'active' THEN 1 END) as active_clients,
        COUNT(CASE WHEN wp.status = 'completed' THEN 1 END) as completed_clients,
        COUNT(CASE WHEN wp.last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_activity
    FROM workout_plans wp
    WHERE wp.trainer_id = ?
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $trainer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

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
    <title>All Clients - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .clients-header {
            background: rgba(255, 255, 255, 0.14);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .clients-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
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
        
        .filters-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid rgba(255, 255, 255, 0.23);
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
            background: rgba(255, 255, 255, 0.09);
            color: white;
            font-size: 14px;
        }
        .filter-select option{
            color: black;
        }
        .sort-select option{
            color: black;
        }
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .client-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .client-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .client-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .client-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .client-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .client-info h3 {
            margin: 0 0 5px 0;
            color: white;
            font-size: 18px;
        }
        
        .client-email {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin: 0;
        }
        
        .client-details {
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .detail-value {
            color: white;
            font-weight: 500;
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
        
        .client-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-view, .btn-edit, .btn-message {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-view {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-edit {
            background: #4CAF50;
            color: white;
        }
        
        .btn-message {
            background: #2196F3;
            color: white;
        }
        
        .btn-view:hover, .btn-edit:hover, .btn-message:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .no-clients {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-clients i {
            font-size: 64px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .clients-grid {
                grid-template-columns: 1fr;
            }
            
            .client-actions {
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
        
        <div class="clients-header">
            <h2><i class="fas fa-users"></i> All Clients</h2>
            <div class="clients-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_clients']; ?></div>
                    <div class="stat-label">Total Clients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_clients']; ?></div>
                    <div class="stat-label">Active Clients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed_clients']; ?></div>
                    <div class="stat-label">Completed Programs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['recent_activity']; ?></div>
                    <div class="stat-label">Recent Activity</div>
                </div>
            </div>
            
            <div class="filters-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search clients by name, email, or plan..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <select class="filter-select" id="filterSelect">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Clients</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                    <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="recent" <?php echo $filter === 'recent' ? 'selected' : ''; ?>>Recent Activity</option>
                </select>
                
                <select class="sort-select" id="sortSelect">
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                    <option value="plan" <?php echo $sort === 'plan' ? 'selected' : ''; ?>>Sort by Plan</option>
                    <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Sort by Date</option>
                    <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Sort by Status</option>
                </select>
            </div>
        </div>
        
        <div class="dashboard">
            <?php if (count($all_clients) > 0): ?>
                <div class="clients-grid">
                    <?php foreach ($all_clients as $client): ?>
                        <div class="client-card">
                            <div class="client-header">
                                 <div class="client-avatar">
                                    <?php 
                                    // Use the improved profile picture function
                                    $client_pic = getProfilePicture($client['profile_picture'] ?? '');
                                    ?>
                                    <img src="<?php echo htmlspecialchars($client_pic); ?>" 
                                        alt="<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?> Avatar"
                                        onerror="this.src='../register/uploads/default-avatar.jpg'"
                                        loading="lazy">
                                </div>
                                <div class="client-info">
                                    <h3><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h3>
                                    <p class="client-email"><?php echo htmlspecialchars($client['email']); ?></p>
                                </div>
                            </div>
                            
                            <div class="client-details">
                                <div class="detail-row">
                                    <span class="detail-label">Plan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($client['plan_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="status-badge status-<?php echo $client['status']; ?>">
                                        <?php echo ucfirst($client['status']); ?>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Age:</span>
                                    <span class="detail-value"><?php echo floor($client['age']); ?> years</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Sessions:</span>
                                    <span class="detail-value"><?php echo $client['completed_sessions']; ?>/<?php echo $client['total_sessions']; ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Last Updated:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($client['last_updated'])); ?></span>
                                </div>
                                <?php if ($client['last_session_date']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Last Session:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($client['last_session_date'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="client-actions">
                                <a href="view-client.php?client_id=<?php echo $client['user_id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit-plan.php?plan_id=<?php echo $client['plan_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit Plan
                                </a>
                                <!-- <button class="btn-message" onclick="messageClient(<?php echo $client['user_id']; ?>)">
                                    <i class="fas fa-comment"></i> Message
                                </button> -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-clients">
                    <i class="fas fa-users"></i>
                    <h3>No clients found</h3>
                    <p>No clients match your current search criteria.</p>
                    <a href="all-clients.php" class="action-btn" style="margin-top: 20px; display: inline-block;">
                        <i class="fas fa-refresh"></i> Show All Clients
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
            if (sort !== 'name') params.append('sort', sort);
            
            const url = 'all-clients.php' + (params.toString() ? '?' + params.toString() : '');
            window.location.href = url;
        }
        
        function messageClient(clientId) {
            Toastify({
                text: "Opening message for client...",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#2196F3",
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
