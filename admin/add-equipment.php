<?php
    session_start();
    require_once('../datacon.php');

    // Redirect if session variables are not set or user is not admin
    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login/index.php");
        exit();
    }

    $email = $_SESSION['email'] ?? '';
    $table_id = $_SESSION['table_id'] ?? 0;

    // Fetch admin data
    $sql_admin = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ? AND role = 'admin'";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("s", $email);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    $admin_data = $result_admin->fetch_assoc();
    $stmt_admin->close();

    // Handle profile picture
    $profile_pic = "../register/uploads/default-avatar.jpg"; 
    if (!empty($admin_data['profile_picture'])) {
        if (file_exists("../register/uploads/" . $admin_data['profile_picture'])) {
            $profile_pic = "../register/uploads/" . $admin_data['profile_picture'];
        } elseif (file_exists("../register/" . $admin_data['profile_picture'])) {
            $profile_pic = "../register/" . $admin_data['profile_picture'];
        }
    }

    // Equipment addition logic
    $equipment_name = $equipment_type = $brand = $model = $serial_number = $purchase_date = $location = '';
    $status = 'available'; // default status
    $maintenance_notes = $last_maintenance_date = $next_maintenance_date = '';
    $equipment_errors = [];
    $equipment_success = false;

    // Equipment types and locations for datalist
    $equipment_types = ['Treadmill', 'Elliptical', 'Stationary Bike', 'Weight Machine', 'Free Weights', 'Bench', 'Rowing Machine', 'Cable Machine'];
    $locations = ['Main Floor', 'Upper Level', 'Cardio Section', 'Weight Room', 'Free Weight Area', 'Functional Training', 'Storage'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equipment'])) {
        // Input values for equipment
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $equipment_type = trim($_POST['equipment_type'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $maintenance_notes = trim($_POST['maintenance_notes'] ?? '');
        $last_maintenance_date = trim($_POST['last_maintenance_date'] ?? '');
        $next_maintenance_date = trim($_POST['next_maintenance_date'] ?? '');

        // Basic validation
        if (empty($equipment_name)) $equipment_errors[] = "Equipment name is required.";
        if (empty($equipment_type)) $equipment_errors[] = "Equipment type is required.";
        if (empty($location)) $equipment_errors[] = "Location is required.";

        if (empty($equipment_errors)) {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO equipment_inventory (
                    name, type, brand, model, serial_number, 
                    purchase_date, location, status, 
                    maintenance_notes, last_maintenance_date, next_maintenance_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $purchase = $purchase_date ?: null;
            $last_maint = $last_maintenance_date ?: null;
            $next_maint = $next_maintenance_date ?: null;
            
            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssssssss",
                    $equipment_name,
                    $equipment_type,
                    $brand,
                    $model,
                    $serial_number,
                    $purchase,
                    $location,
                    $status,
                    $maintenance_notes,
                    $last_maint,
                    $next_maint
                );

                if (mysqli_stmt_execute($stmt)) {
                    $equipment_success = true;
                    $equipment_name = $equipment_type = $brand = $model = $serial_number = $purchase_date = $location = '';
                    $maintenance_notes = $last_maintenance_date = $next_maintenance_date = '';
                    
                    echo "<script>
                        localStorage.setItem('toastMessage', 'Equipment added successfully!');
                        window.location.href = 'index.php';
                    </script>";
                    exit;
                } else {
                    $equipment_errors[] = "Database insert failed: " . mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
            } else {
                $equipment_errors[] = "SQL error: " . mysqli_error($conn);
            }
        }
    }

    // Fetch total users count
    $sql_users_count = "SELECT COUNT(*) as total_users FROM user_register_details WHERE role = 'user'";
    $result_users_count = $conn->query($sql_users_count);
    $users_count = $result_users_count ? $result_users_count->fetch_assoc()['total_users'] : 0;

    // Fetch total trainers count
    $sql_trainers_count = "SELECT COUNT(*) as total_trainers FROM user_register_details WHERE role = 'trainer'";
    $result_trainers_count = $conn->query($sql_trainers_count);
    $trainers_count = $result_trainers_count ? $result_trainers_count->fetch_assoc()['total_trainers'] : 0;

    // Fetch total equipment managers count
    $sql_managers_count = "SELECT COUNT(*) as total_managers FROM user_register_details WHERE role = 'equipment_manager'";
    $result_managers_count = $conn->query($sql_managers_count);
    $managers_count = $result_managers_count ? $result_managers_count->fetch_assoc()['total_managers'] : 0;

    // Fetch recent users (5 most recent)
    $sql_recent_users = "SELECT table_id, first_name, last_name, email, profile_picture, 
                        IFNULL(DATE(date_of_registration), CURRENT_DATE) as join_date 
                        FROM user_register_details 
                        WHERE role = 'user' 
                        ORDER BY date_of_registration DESC 
                        LIMIT 5";
    $result_recent_users = $conn->query($sql_recent_users);
    $recent_users = [];
    if ($result_recent_users && $result_recent_users->num_rows > 0) {
        while ($row = $result_recent_users->fetch_assoc()) {
            $recent_users[] = $row;
        }
    }

    // Fetch equipment inventory summary
    $sql_equipment = "SELECT 
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                        COUNT(*) as total
                    FROM equipment_inventory";
    $result_equipment = $conn->query($sql_equipment);
    $equipment_stats = $result_equipment ? $result_equipment->fetch_assoc() : [
        'available' => 0,
        'in_use' => 0,
        'maintenance' => 0,
        'total' => 0
    ];

    // Fetch recent equipment (5 most recent)
    $sql_recent_equipment = "SELECT equipment_id, name, type, location, status, purchase_date
                            FROM equipment_inventory 
                            ORDER BY equipment_id DESC 
                            LIMIT 5";
    $result_recent_equipment = $conn->query($sql_recent_equipment);
    $recent_equipment = [];
    if ($result_recent_equipment && $result_recent_equipment->num_rows > 0) {
        while ($row = $result_recent_equipment->fetch_assoc()) {
            $recent_equipment[] = $row;
        }
    }

    // Greeting logic
    $hour = date("H");
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
    <title>Admin Dashboard - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Enhanced Dashboard Styles */
        .dashboard-container {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dashboard-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            background:white;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dashboard-title p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        .dashboard-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* Enhanced Form Styles */
        .equipment-form-container {
            background: rgba(40, 40, 40, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-header h3 {
            font-size: 24px;
            margin: 0;
            color: white;
        }

        .form-header .form-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: white;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e74c3c;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-family: var(--font-family);
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-row-triple {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 25px;
        }

        /* Enhanced Button Styles */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: var(--font-family);
            font-size: 14px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.4);
        }

        .toggle-form-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-family: var(--font-family);
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .toggle-form-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Enhanced Alert Styles */
        .alert {
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.15);
            border-left-color: #e74c3c;
            color: #fff;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.15);
            border-left-color: #2ecc71;
            color: #fff;
        }

        .alert-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .alert-content h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .alert-content ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .alert-content li {
            margin-bottom: 5px;
        }

        /* Enhanced Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);;
        }

        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
            color: white;
        }

        .stat-content p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 500;
        }

        /* Equipment Form Toggle */
        .equipment-form {
            display: none;
            animation: slideDown 0.3s ease-out;
        }

        .equipment-form.active {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Recent Equipment Table */
        .recent-equipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .recent-equipment-table th {
            text-align: left;
            padding: 15px;
            background: rgba(102, 126, 234, 0.1);
            color: white;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }

        .recent-equipment-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .recent-equipment-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }

        .equipment-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-available {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .status-in_use {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }

        .status-maintenance {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .dashboard-actions {
                width: 100%;
            }

            .form-row, .form-row-triple {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px;
                margin: 20px 0;
            }

            .equipment-form-container {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the admin sidebar -->
    <?php include 'admin-sidebar.php'; ?>
    
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
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($admin_data['first_name'] . ' ' . $admin_data['last_name']) ?></h3>
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
        
        <div class="welcome-section">
            <h1><?php echo $greeting; ?>, <?= htmlspecialchars($admin_data['first_name']) ?>!</h1>
            <p>Welcome to your admin dashboard. Here's an overview of your gym's performance.</p>
        </div>
        
        <!-- Enhanced Stats Cards -->
        <!-- <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $users_count; ?></h3>
                    <p>Total Members</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $trainers_count; ?></h3>
                    <p>Active Trainers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $managers_count; ?></h3>
                    <p>Equipment Managers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $equipment_stats['total']; ?></h3>
                    <p>Total Equipment</p>
                </div>
            </div>
        </div> -->
        
        <!-- Enhanced Equipment Management Section -->
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h2><i class="fas fa-plus-circle"></i> Equipment Management</h2>
                    <p>Add and manage gym equipment efficiently</p>
                </div>
                <div class="dashboard-actions">
                    <a href="all-equipments.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> View All Equipment
                    </a>
                </div>
            </div>
            
            <button type="button" class="toggle-form-btn" onclick="toggleEquipmentForm()">
                <i class="fas fa-plus"></i> Add New Equipment
            </button>
            
            <div class="equipment-form-container equipment-form" id="equipmentForm">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div>
                        <h3>Add New Equipment</h3>
                        <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">Fill in the equipment details below</p>
                    </div>
                </div>

                <?php if (!empty($equipment_errors)): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h4>Please correct the following errors:</h4>
                            <ul>
                                <?php foreach ($equipment_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($equipment_success): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="alert-content">
                            <h4>Success!</h4>
                            <p>The equipment has been added successfully.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <input type="hidden" name="add_equipment" value="1">
                    
                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="equipment_name">Equipment Name<span class="required">*</span></label>
                                <input type="text" id="equipment_name" name="equipment_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($equipment_name); ?>" 
                                       placeholder="Enter equipment name" required>
                            </div>

                            <div class="form-group">
                                <label for="equipment_type">Equipment Type<span class="required">*</span></label>
                                <input type="text" id="equipment_type" name="equipment_type" class="form-control" 
                                       value="<?php echo htmlspecialchars($equipment_type); ?>" 
                                       list="equipment-types" placeholder="Select or enter type" required>
                                <datalist id="equipment-types">
                                    <?php foreach ($equipment_types as $eq_type): ?>
                                        <option value="<?php echo htmlspecialchars($eq_type); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="brand">Brand</label>
                                <input type="text" id="brand" name="brand" class="form-control" 
                                       value="<?php echo htmlspecialchars($brand); ?>" 
                                       placeholder="Equipment brand">
                            </div>

                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" id="model" name="model" class="form-control" 
                                       value="<?php echo htmlspecialchars($model); ?>" 
                                       placeholder="Equipment model">
                            </div>
                        </div>
                    </div>

                    <!-- Technical Details Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-cog"></i>
                            Technical Details
                        </div>
                        
                        <div class="form-row-triple">
                            <div class="form-group">
                                <label for="serial_number">Serial Number</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($serial_number); ?>" 
                                       placeholder="Serial number">
                            </div>

                            <div class="form-group">
                                <label for="purchase_date">Purchase Date</label>
                                <input type="date" id="purchase_date" name="purchase_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($purchase_date); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="available" <?php echo ($status === 'available') ? 'selected' : ''; ?>>Available</option>
                                    <option value="in_use" <?php echo ($status === 'in_use') ? 'selected' : ''; ?>>In Use</option>
                                    <option value="maintenance" <?php echo ($status === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">Location<span class="required">*</span></label>
                            <input type="text" id="location" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($location); ?>" 
                                   list="locations" placeholder="Select or enter location" required>
                            <datalist id="locations">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>

                    <!-- Maintenance Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-wrench"></i>
                            Maintenance Information
                        </div>
                        
                        <div class="form-group">
                            <label for="maintenance_notes">Maintenance Notes</label>
                            <textarea id="maintenance_notes" name="maintenance_notes" class="form-control" rows="3" 
                                      placeholder="Enter any maintenance notes or special instructions"><?php echo htmlspecialchars($maintenance_notes); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="last_maintenance_date">Last Maintenance Date</label>
                                <input type="date" id="last_maintenance_date" name="last_maintenance_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($last_maintenance_date); ?>">
                            </div>

                            <div class="form-group">
                                <label for="next_maintenance_date">Next Maintenance Date</label>
                                <input type="date" id="next_maintenance_date" name="next_maintenance_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($next_maintenance_date); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="toggleEquipmentForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Equipment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Enhanced Equipment Stats -->
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h2><i class="fas fa-chart-bar"></i> Equipment Status Overview</h2>
                    <p>Current status of all gym equipment</p>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $equipment_stats['available']; ?></h3>
                        <p>Available</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $equipment_stats['in_use']; ?></h3>
                        <p>In Use</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $equipment_stats['maintenance']; ?></h3>
                        <p>Maintenance</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Equipment -->
        <?php if (!empty($recent_equipment)): ?>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h2><i class="fas fa-clock"></i> Recently Added Equipment</h2>
                    <p>Latest equipment additions to the gym</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="recent-equipment-table">
                    <thead>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Added Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_equipment as $equipment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['type']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                                <td>
                                    <span class="equipment-status-badge status-<?php echo htmlspecialchars($equipment['status']); ?>">
                                        <?php echo htmlspecialchars(str_replace('_', ' ', $equipment['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($equipment['purchase_date'])) {
                                        echo date('M d, Y', strtotime($equipment['purchase_date']));
                                    } else {
                                        echo 'Not specified';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        function toggleEquipmentForm() {
            const form = document.getElementById('equipmentForm');
            const btn = document.querySelector('.toggle-form-btn');
            
            if (form.classList.contains('active')) {
                form.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-plus"></i> Add New Equipment';
            } else {
                form.classList.add('active');
                btn.innerHTML = '<i class="fas fa-minus"></i> Cancel';
                // Scroll to form
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        
        // Show form if there are errors
        <?php if (!empty($equipment_errors)): ?>
            document.getElementById('equipmentForm').classList.add('active');
            document.querySelector('.toggle-form-btn').innerHTML = '<i class="fas fa-minus"></i> Cancel';
        <?php endif; ?>

        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to form controls
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                control.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Auto-hide success messages
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        successAlert.remove();
                    }, 300);
                }, 5000);
            }
        });
    </script>
    
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>