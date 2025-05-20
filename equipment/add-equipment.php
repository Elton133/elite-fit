<?php
include '../services/equipment-manager-logic.php'; 

// Initialize variables
$name = $type = $brand = $model = $serial_number = $purchase_date = $location = '';
$status = 'available'; // default status
$maintenance_notes = $last_maintenance_date = $next_maintenance_date = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input values
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $purchase_date = trim($_POST['purchase_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $maintenance_notes = trim($_POST['maintenance_notes'] ?? '');
    $last_maintenance_date = trim($_POST['last_maintenance_date'] ?? '');
    $next_maintenance_date = trim($_POST['next_maintenance_date'] ?? '');

    // Basic validation
    if (empty($name)) $errors[] = "Equipment name is required.";
    if (empty($type)) $errors[] = "Equipment type is required.";
    if (empty($location)) $errors[] = "Location is required.";

    if (empty($errors)) {
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
                $name,
                $type,
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
                $success = true;
                $name = $type = $brand = $model = $serial_number = $purchase_date = $location = '';
                $maintenance_notes = $last_maintenance_date = $next_maintenance_date = '';

                echo "<script>
                    localStorage.setItem('toastMessage', 'Equipment added successfully!');
                    window.location.href = 'manager-dashboard.php';
                </script>";
                exit;
            } else {
                $errors[] = "Database insert failed: " . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "SQL error: " . mysqli_error($conn);
        }
    }
}

// Optional: Close connection if no further queries
mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment - EliteFit Gym</title>
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
                <h3><i class="fas fa-plus"></i> Add New Equipment</h3>
                <a href="../equipment/manager-dashboard.php" class="view-all"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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
                            <strong>Success!</strong> The equipment has been added successfully.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="name">Equipment Name *</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>

        <div class="form-group">
            <label for="type">Equipment Type *</label>
            <input type="text" id="type" name="type" class="form-control" value="<?php echo htmlspecialchars($type); ?>" list="equipment-types" required>
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
            <input type="text" id="status" name="status" class="form-control" value="<?php echo htmlspecialchars($status ?? 'available'); ?>">
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
        <a href="index.php" class="action-btn secondary">Cancel</a>
        <button type="submit" class="action-btn">Add Equipment</button>
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