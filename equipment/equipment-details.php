<?php
include '../services/equipment-manager-logic.php';

$equipment_id = $_GET['id'] ?? null;
$equipment = null;
$maintenance_history = [];
$usage_history = [];
$error = null;

// Fetch equipment details
if ($equipment_id) {
    // Get equipment details
    $stmt = mysqli_prepare($conn, "SELECT * FROM equipment_inventory WHERE equipment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $equipment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $equipment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$equipment) {
        $error = "Equipment not found";
    } else {
        // Get maintenance history
        $stmt = mysqli_prepare($conn, "SELECT * FROM maintenance_log WHERE equipment_id = ? ORDER BY maintenance_date DESC");
        mysqli_stmt_bind_param($stmt, "i", $equipment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $maintenance_history[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Get usage history with user names
        // $stmt = mysqli_prepare($conn, "
        //     SELECT eu.*, CONCAT(urd.first_name, ' ', urd.last_name) AS user_name
        //     FROM equipment_usage eu
        //     LEFT JOIN user_register_details urd ON eu.user_id = urd.user_id
        //     WHERE eu.equipment_id = ?
        //     ORDER BY eu.start_time DESC
        //     LIMIT 10
        // ");
        // mysqli_stmt_bind_param($stmt, "i", $equipment_id);
        // mysqli_stmt_execute($stmt);
        // $result = mysqli_stmt_get_result($stmt);
        // while ($row = mysqli_fetch_assoc($result)) {
        //     $usage_history[] = $row;
        // }
        // mysqli_stmt_close($stmt);
    }
} else {
    $error = "No equipment ID provided";
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';

    if ($equipment_id && in_array($new_status, ['available', 'in_use', 'maintenance'])) {
        // Update status
        $stmt = mysqli_prepare($conn, "UPDATE equipment_inventory SET status = ? WHERE equipment_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $equipment_id);

        if (!mysqli_stmt_execute($stmt)) {
            $error = "Failed to update status: " . mysqli_stmt_error($stmt);
        } else {
            mysqli_stmt_close($stmt);

            // If set to maintenance, log a default record
            if ($new_status === 'maintenance') {
                $performed_by = "System Auto"; // You can change this
                $description = "Status changed to maintenance";
                $cost = 0.00;
                $next_maintenance_date = date('Y-m-d', strtotime('+30 days')); // Default future date

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO maintenance_log (
                        equipment_id, maintenance_date, performed_by, description, cost, next_maintenance_date
                    ) VALUES (?, CURDATE(), ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param(
                    $stmt,
                    "issdss",
                    $equipment_id,
                    $performed_by,
                    $description,
                    $cost,
                    $next_maintenance_date
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $error = "Failed to log maintenance: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }

            if (!$error) {
                echo "<script>
                    localStorage.setItem('toastMessage', 'Equipment status updated successfully!');
                    window.location.href = 'equipment-details.php?id=" . $equipment_id . "';
                </script>";
                exit;
            }
        }
    }
}

mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Details - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .equipment-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .equipment-icon {
            width: 80px;
            height: 80px;
            border-radius: var(--border-radius-sm);
            background: rgba(30, 60, 114, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .equipment-title h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .equipment-title p {
            color: var(--text-muted);
            font-size: 16px;
        }
        
        .equipment-status {
            margin-left: auto;
            padding: 8px 15px;
            border-radius: var(--border-radius-lg);
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-available {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .status-in-use {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .status-maintenance {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .equipment-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--border-color);
        }
        
        .detail-item h4 {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }
        
        .detail-item p {
            font-size: 16px;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .history-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .history-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all var(--transition-normal);
        }
        
        .history-tab.active {
            border-bottom-color: var(--primary-color);
            color: white;
        }
        
        .history-tab:hover:not(.active) {
            border-bottom-color: var(--border-color);
        }
        
        .history-content {
            display: none;
        }
        
        .history-content.active {
            display: block;
        }
        
        .history-item {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .history-date {
            min-width: 80px;
            text-align: center;
        }
        
        .history-date .day {
            font-size: 18px;
            font-weight: 600;
        }
        
        .history-date .month {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .history-details {
            flex: 1;
        }
        
        .history-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .history-details p {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .history-status {
            padding: 5px 10px;
            border-radius: var(--border-radius-lg);
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-completed {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .status-scheduled {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }
        
        .status-in-progress {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #272727;
            border-radius: var(--border-radius-md);
            width: 90%;
            max-width: 500px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            transition: color var(--transition-normal);
        }
        
        .modal-close:hover {
            color: white;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        .no-data {
            padding: 30px;
            text-align: center;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .equipment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .equipment-status {
                margin-left: 0;
                align-self: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .action-btn {
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
                        <?php 
                                    // Use the improved profile picture function
                                    $manager_pic = getProfilePicture($manager_data['profile_picture'] ?? '');
                                    ?>
                        <img src="<?php echo htmlspecialchars($manager_pic); ?>" alt="Profile Picture">
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
        
        <?php if ($error): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Error</h3>
                    <a href="index.php" class="view-all"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
                <div class="card-content">
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            </div>
        <?php elseif ($equipment): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-dumbbell"></i> Equipment Details</h3>
                    <a href="index.php" class="view-all"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
                <div class="card-content">
                    <div class="equipment-header">
                        <div class="equipment-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="equipment-title">
                            <h2><?php echo htmlspecialchars($equipment['name']); ?></h2>
                            <p><?php echo htmlspecialchars($equipment['type']); ?> - <?php echo htmlspecialchars($equipment['location']); ?></p>
                        </div>
                        <div class="equipment-status status-<?php echo $equipment['status']; ?>">
                            <i class="fas fa-<?php echo $equipment['status'] === 'available' ? 'check-circle' : ($equipment['status'] === 'in_use' ? 'users' : 'tools'); ?>"></i>
                            <?php echo ucfirst($equipment['status']); ?>
                        </div>
                    </div>
                    
                    <div class="equipment-details">
                        <div class="detail-item">
                            <h4>Manufacturer</h4>
                            <p><?php echo $equipment['brand'] ? htmlspecialchars($equipment['brand']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Model</h4>
                            <p><?php echo $equipment['model'] ? htmlspecialchars($equipment['model']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Serial Number</h4>
                            <p><?php echo $equipment['serial_number'] ? htmlspecialchars($equipment['serial_number']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Purchase Date</h4>
                            <p><?php echo $equipment['purchase_date'] ? date('M d, Y', strtotime($equipment['purchase_date'])) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Next Maintenance</h4>
                            <p><?php echo $equipment['next_maintenance_date'] ? date('M d, Y', strtotime($equipment['next_maintenance_date'])) : 'Not scheduled'; ?></p>
                        </div>
                       <div class="detail-item">
    <h4>Last Updated</h4>
    <p>
        <?php 
        echo !empty($equipment['last_maintenance_date']) 
            ? date('M d, Y', strtotime($equipment['last_maintenance_date'])) 
            : 'N/A';
        ?>
    </p>
    
</div>

                    </div>
                    
                    <?php if ($equipment['maintenance_notes']): ?>
                        <div class="detail-item" style="margin-bottom: 20px;">
                            <h4>Maintenance Notes</h4>
                            <p><?php echo nl2br(htmlspecialchars($equipment['maintenance_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <button class="action-btn" id="changeStatusBtn">
                            <i class="fas fa-exchange-alt"></i> Change Status
                        </button>
                        <a href="schedule-maintenance.php?id=<?php echo $equipment['equipment_id']; ?>" class="action-btn">
                            <i class="fas fa-tools"></i> Schedule Maintenance
                        </a>
                        <a href="edit-equipment.php?id=<?php echo $equipment['equipment_id']; ?>" class="action-btn secondary">
                            <i class="fas fa-edit"></i> Edit Details
                        </a>
                    </div>
                    
                    <div class="history-tabs">
                        <div class="history-tab active" data-tab="maintenance">Maintenance History</div>
                        <div class="history-tab" data-tab="usage">Usage History</div>
                    </div>
                    
                    <div class="history-content active" id="maintenance-history">
                        <?php if (count($maintenance_history) > 0): ?>
                            <?php foreach ($maintenance_history as $maintenance): ?>
                                <div class="history-item">
                                    <div class="history-date">
                                        <div class="day"><?php echo date('d', strtotime($maintenance['maintenance_date'])); ?></div>
                                        <div class="month"><?php echo date('M Y', strtotime($maintenance['maintenance_date'])); ?></div>
                                    </div>
                                    <div class="history-details">
                                        <h4><?php echo htmlspecialchars($maintenance['description']); ?></h4>
                                        <p><?php echo $maintenance['description'] ? htmlspecialchars($maintenance['description']) : 'No description provided'; ?></p>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-tools" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No maintenance history available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="history-content" id="usage-history">
                        <?php if (count($usage_history) > 0): ?>
                            <?php foreach ($usage_history as $usage): ?>
                                <div class="history-item">
                                    <div class="history-date">
                                        <div class="day"><?php echo date('d', strtotime($usage['start_time'])); ?></div>
                                        <div class="month"><?php echo date('M Y', strtotime($usage['start_time'])); ?></div>
                                    </div>
                                    <div class="history-details">
                                        <h4>Used by: <?php echo htmlspecialchars($usage['user_name'] ?? 'Unknown User'); ?></h4>
                                        <p>
                                            From: <?php echo date('h:i A', strtotime($usage['start_time'])); ?> 
                                            To: <?php echo date('h:i A', strtotime($usage['end_time'])); ?>
                                            (<?php echo round((strtotime($usage['end_time']) - strtotime($usage['start_time'])) / 60); ?> minutes)
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No usage history available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Change Status Modal -->
            <div class="modal" id="statusModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Change Equipment Status</h3>
                        <button class="modal-close" id="closeModal">&times;</button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="status">Select New Status</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="available" <?php echo $equipment['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="in_use" <?php echo $equipment['status'] === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                    <option value="maintenance" <?php echo $equipment['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="action-btn secondary" id="cancelModal">Cancel</button>
                            <button type="submit" name="update_status" class="action-btn">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="equipment-sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Toast message
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
        
        // Tab switching
        const tabs = document.querySelectorAll('.history-tab');
        const contents = document.querySelectorAll('.history-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tabId + '-history').classList.add('active');
            });
        });
        
        // Modal functionality
        const modal = document.getElementById('statusModal');
        const openModalBtn = document.getElementById('changeStatusBtn');
        const closeModalBtn = document.getElementById('closeModal');
        const cancelModalBtn = document.getElementById('cancelModal');
        
        if (openModalBtn && modal) {
            openModalBtn.addEventListener('click', () => {
                modal.classList.add('show');
            });
            
            closeModalBtn.addEventListener('click', () => {
                modal.classList.remove('show');
            });
            
            cancelModalBtn.addEventListener('click', () => {
                modal.classList.remove('show');
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
        }
    </script>
</body>
</html>