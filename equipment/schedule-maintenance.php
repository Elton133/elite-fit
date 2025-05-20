<?php
// DB connection (make sure this file connects with mysqli)
include '../services/equipment-manager-logic.php';

$equipment_id = $_GET['id'] ?? null;
$maintenance_date = '';
$performed_by = '';
$description = '';
$cost = '';
$next_maintenance_date = '';
$errors = [];
$success = false;
$equipment = null;

// Get equipment details if ID is provided
if ($equipment_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM equipment_inventory WHERE equipment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $equipment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $equipment = mysqli_fetch_assoc($result);

    if (!$equipment) {
        $errors[] = "Equipment not found";
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = $_POST['equipment_id'] ?? null;
    $maintenance_date = trim($_POST['maintenance_date'] ?? '');
    $performed_by = trim($_POST['performed_by'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cost = trim($_POST['cost'] ?? '');
    $next_maintenance_date = trim($_POST['next_maintenance_date'] ?? '');

    // Validation
    if (empty($equipment_id)) $errors[] = "Equipment ID is required";
    if (empty($maintenance_date)) $errors[] = "Maintenance date is required";
    if (empty($performed_by)) $errors[] = "Performed By is required";

    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Insert into maintenance_log
            $stmt = mysqli_prepare($conn, "
                INSERT INTO maintenance_log (
                    equipment_id, maintenance_date, performed_by, description, cost, next_maintenance_date
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "isssds",
                $equipment_id,
                $maintenance_date,
                $performed_by,
                $description,
                $cost,
                $next_maintenance_date
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to insert into maintenance_log: " . mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);

            // If maintenance is today, update status
            if (date('Y-m-d') == $maintenance_date) {
                $stmt = mysqli_prepare($conn, "UPDATE equipment_inventory SET status = 'maintenance' WHERE equipment_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $equipment_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update equipment status: " . mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
            }

            // Update next_maintenance_date
            $stmt = mysqli_prepare($conn, "UPDATE equipment_inventory SET next_maintenance_date = ? WHERE equipment_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $next_maintenance_date, $equipment_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update next maintenance date: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            $success = true;

            echo "<script>
                localStorage.setItem('toastMessage', 'Maintenance scheduled successfully!');
                window.location.href = 'manager-dashboard.php';
            </script>";
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Get all equipment if no specific ID provided
$all_equipment = [];
if (!$equipment_id) {
    $result = mysqli_query($conn, "SELECT equipment_id, name, type, location FROM equipment_inventory ORDER BY name");
    while ($row = mysqli_fetch_assoc($result)) {
        $all_equipment[] = $row;
    }
}

// Define maintenance types for frontend (can still be used if needed)
$maintenance_types = ['Regular Service', 'Repair', 'Inspection', 'Cleaning', 'Parts Replacement', 'Calibration', 'Other'];

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Maintenance - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
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
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius-sm);
            color: #fff;
        }
        
        .error-message ul {
            margin: 10px 0 0 20px;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius-sm);
        }
        
        .equipment-info {
            background: rgba(52, 152, 219, 0.1);
            border-radius: var(--border-radius-sm);
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .equipment-info h4 {
            margin-bottom: 10px;
            color: #3498db;
        }
        
        .equipment-info p {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .action-btn {
                width: 100%;
            }
        }
    </style>
</head>
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
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-tools"></i> Schedule Maintenance</h3>
                <a href="index.php" class="view-all"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            <div class="card-content">
                <div class="form-container">
                    <?php if (!empty($errors)): ?>
                        <div class="error-message">
                            <strong>Please correct the following errors:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success-message">
                            <strong>Success!</strong> The maintenance has been scheduled successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($equipment): ?>
                        <div class="equipment-info">
                            <h4>Equipment Details</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($equipment['name']); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($equipment['type']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($equipment['location']); ?></p>
                            <p><strong>Current Status:</strong> <?php echo htmlspecialchars(ucfirst($equipment['status'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <?php if (!$equipment): ?>
                            <div class="form-group">
                                <label for="equipment_id">Select Equipment *</label>
                                <select id="equipment_id" name="equipment_id" class="form-control" required>
                                    <option value="">-- Select Equipment --</option>
                                    <?php foreach ($all_equipment as $eq): ?>
                                        <option value="<?php echo $eq['equipment_id']; ?>">
                                            <?php echo htmlspecialchars($eq['name'] . ' (' . $eq['type'] . ' - ' . $eq['location'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="equipment_id" value="<?php echo $equipment['equipment_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="maintenance_date">Maintenance Date *</label>
                                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($maintenance_date ?: date('Y-m-d')); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="performed_by">Performed By *</label>
                                <input type="text" id="performed_by" name="performed_by" class="form-control" 
                                       value="<?php echo htmlspecialchars($performed_by); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="cost">Maintenance Cost</label>
                           <input type="text" id="cost" name="cost" class="form-control" 
                                  value="<?php echo htmlspecialchars($cost); ?>" placeholder="0.00">
                        </div>
                        
                        <div class="form-actions">
                            <a href="index.php" class="action-btn secondary">Cancel</a>
                            <button type="submit" class="action-btn">Schedule Maintenance</button>
                        </div>
                    </form>
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
</body>
</html>