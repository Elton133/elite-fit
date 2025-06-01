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

    // Equipment addition logic (adapted from equipment manager)
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

    // Fetch workout plan statistics
    $sql_workout_stats = "SELECT wp.workout_name, COUNT(ufd.table_id) as user_count
                        FROM workout_plan wp
                        LEFT JOIN (
                            SELECT table_id, preferred_workout_routine_1 as routine FROM user_fitness_details
                            UNION ALL
                            SELECT table_id, preferred_workout_routine_2 as routine FROM user_fitness_details
                            UNION ALL
                            SELECT table_id, preferred_workout_routine_3 as routine FROM user_fitness_details
                        ) ufd ON wp.table_id = ufd.routine
                        GROUP BY wp.workout_name
                        ORDER BY user_count DESC
                        LIMIT 5";
    $result_workout_stats = $conn->query($sql_workout_stats);
    $workout_stats = [];
    if ($result_workout_stats) {
        while ($row = $result_workout_stats->fetch_assoc()) {
            $workout_stats[] = $row;
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
        .equipment-form-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: white;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius-sm);
            color: white;
            font-family: var(--font-family);
            font-size: 16px;
            transition: all var(--transition-normal);
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius-sm);
            color: #fff;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius-sm);
            color: #fff;
        }
        
        .toggle-form-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-family: var(--font-family);
            font-weight: 500;
            transition: all var(--transition-normal);
            margin-bottom: 20px;
        }
        
        .toggle-form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .equipment-form {
            display: none;
        }
        
        .equipment-form.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
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
        
        <!-- Stats Cards -->
        <div class="stats-grid">
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
        </div>
        
        <!-- Equipment Management Section -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Equipment Management</h3>
            </div>
            <div class="card-content">
                <button type="button" class="toggle-form-btn" onclick="toggleEquipmentForm()">
                    <i class="fas fa-plus"></i> Add New Equipment
                </button>
                
                <div class="equipment-form-container equipment-form" id="equipmentForm">
                    <?php if (!empty($equipment_errors)): ?>
                        <div class="error-message">
                            <strong>Please correct the following errors:</strong>
                            <ul>
                                <?php foreach ($equipment_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($equipment_success): ?>
                        <div class="success-message">
                            <strong>Success!</strong> The equipment has been added successfully.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <input type="hidden" name="add_equipment" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="equipment_name">Equipment Name *</label>
                                <input type="text" id="equipment_name" name="equipment_name" class="form-control" value="<?php echo htmlspecialchars($equipment_name); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="equipment_type">Equipment Type *</label>
                                <input type="text" id="equipment_type" name="equipment_type" class="form-control" value="<?php echo htmlspecialchars($equipment_type); ?>" list="equipment-types" required>
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
                                <input type="text" id="brand" name="brand" class="form-control" value="<?php echo htmlspecialchars($brand); ?>">
                            </div>

                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" id="model" name="model" class="form-control" value="<?php echo htmlspecialchars($model); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="serial_number">Serial Number</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($serial_number); ?>">
                            </div>

                            <div class="form-group">
                                <label for="purchase_date">Purchase Date</label>
                                <input type="date" id="purchase_date" name="purchase_date" class="form-control" value="<?php echo htmlspecialchars($purchase_date); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="location">Location *</label>
                                <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" list="locations" required>
                                <datalist id="locations">
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>">
                                    <?php endforeach; ?>
                                </datalist>
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
                            <label for="maintenance_notes">Maintenance Notes</label>
                            <textarea id="maintenance_notes" name="maintenance_notes" class="form-control" rows="3"><?php echo htmlspecialchars($maintenance_notes); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="last_maintenance_date">Last Maintenance Date</label>
                                <input type="date" id="last_maintenance_date" name="last_maintenance_date" class="form-control" value="<?php echo htmlspecialchars($last_maintenance_date); ?>">
                            </div>

                            <div class="form-group">
                                <label for="next_maintenance_date">Next Maintenance Date</label>
                                <input type="date" id="next_maintenance_date" name="next_maintenance_date" class="form-control" value="<?php echo htmlspecialchars($next_maintenance_date); ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="action-btn secondary" onclick="toggleEquipmentForm()">Cancel</button>
                            <button type="submit" class="action-btn">Add Equipment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Equipment Stats -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Equipment Status Overview</h3>
            </div>
            <div class="card-content">
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
        </div>
        
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
            }
        }
        
        // Show form if there are errors
        <?php if (!empty($equipment_errors)): ?>
            document.getElementById('equipmentForm').classList.add('active');
            document.querySelector('.toggle-form-btn').innerHTML = '<i class="fas fa-minus"></i> Cancel';
        <?php endif; ?>
    </script>
    
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>