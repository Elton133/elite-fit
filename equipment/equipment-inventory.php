<?php
include '../services/equipment-manager-logic.php';

// Initialize variables for filtering and pagination
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? '';
$location_filter = $_GET['location'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

$sql_conditions = [];
$sql_params = [];
$sql_types = "";

// Filters
if ($status_filter !== 'all') {
    $sql_conditions[] = "status = ?";
    $sql_params[] = $status_filter;
    $sql_types .= "s";
}

if (!empty($type_filter)) {
    $sql_conditions[] = "type = ?";
    $sql_params[] = $type_filter;
    $sql_types .= "s";
}

if (!empty($location_filter)) {
    $sql_conditions[] = "location = ?";
    $sql_params[] = $location_filter;
    $sql_types .= "s";
}

if (!empty($search)) {
    $sql_conditions[] = "(name LIKE ? OR serial_number LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
    $sql_types .= "sss";
}

$sql_where = !empty($sql_conditions) ? "WHERE " . implode(" AND ", $sql_conditions) : "";

// Count total records
$total_records = 0;
$total_pages = 1;
$count_sql = "SELECT COUNT(*) FROM equipment_inventory $sql_where";

$stmt = mysqli_prepare($conn, $count_sql);
if ($stmt) {
    if (!empty($sql_params)) {
        mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total_records);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $total_pages = ceil($total_records / $per_page);
    $page = min($page, max(1, $total_pages));
}

// Get paginated equipment list
$offset = ($page - 1) * $per_page;
$equipment_list = [];

$list_sql = "SELECT * FROM equipment_inventory $sql_where ORDER BY name LIMIT ? OFFSET ?";
$list_stmt = mysqli_prepare($conn, $list_sql);

if ($list_stmt) {
    $list_types = $sql_types . "ii";
    $list_params = [...$sql_params, $per_page, $offset];

    mysqli_stmt_bind_param($list_stmt, $list_types, ...$list_params);
    mysqli_stmt_execute($list_stmt);
    $result = mysqli_stmt_get_result($list_stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $equipment_list[] = $row;
    }

    mysqli_stmt_close($list_stmt);
}

// Get equipment types
$equipment_types = [];
$type_result = mysqli_query($conn, "SELECT DISTINCT type FROM equipment_inventory ORDER BY type");
if ($type_result) {
    while ($row = mysqli_fetch_assoc($type_result)) {
        $equipment_types[] = $row['type'];
    }
}

// Get locations
$locations = [];
$location_result = mysqli_query($conn, "SELECT DISTINCT location FROM equipment_inventory ORDER BY location");
if ($location_result) {
    while ($row = mysqli_fetch_assoc($location_result)) {
        $locations[] = $row['location'];
    }
}

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Inventory - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .filter-control {
            width: 100%;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            color: white;
            font-family: var(--font-family);
            font-size: 14px;
        }
        
        .search-group {
            flex: 2;
            min-width: 250px;
            display: flex;
            gap: 10px;
        }
        
        .search-group .filter-control {
            flex: 1;
        }
        
        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .equipment-table th,
        .equipment-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .equipment-table th {
            background: rgba(0, 0, 0, 0.2);
            font-weight: 600;
            color: var(--text-light);
            position: sticky;
            top: 0;
        }
        
        .equipment-table tbody tr {
            transition: background-color var(--transition-normal);
        }
        
        .equipment-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: var(--border-radius-lg);
            font-size: 12px;
            font-weight: 500;
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
        
        .table-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius-circle);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            transition: all var(--transition-normal);
        }
        
        .action-icon:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .action-icon.view:hover {
            background: rgba(52, 152, 219, 0.3);
            color: #3498db;
        }
        
        .action-icon.edit:hover {
            background: rgba(241, 196, 15, 0.3);
            color: #f1c40f;
        }
        
        .action-icon.delete:hover {
            background: rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            text-decoration: none;
            transition: all var(--transition-normal);
        }
        
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .table-container {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            .search-group {
                flex-direction: column;
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
                <h3><i class="fas fa-dumbbell"></i> Equipment Inventory</h3>
                <a href="add-equipment.php" class="action-btn"><i class="fas fa-plus"></i> Add Equipment</a>
            </div>
            <div class="card-content">
                <form method="get" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="filter-control" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="in_use" <?php echo $status_filter === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select id="type" name="type" class="filter-control" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <?php foreach ($equipment_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="location">Location</label>
                            <select id="location" name="location" class="filter-control" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location); ?>" <?php echo $location_filter === $location ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <div style="flex: 1;">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="filter-control" placeholder="Search by name, serial number, or description" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div style="display: flex; align-items: flex-end;">
                                <button type="submit" class="action-btn" style="height: 38px;"><i class="fas fa-search"></i></button>
                                <a href="equipment-inventory.php" class="action-btn secondary" style="height: 38px; margin-left: 5px;"><i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="table-container">
                    <table class="equipment-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Next Maintenance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($equipment_list) > 0): ?>
                                <?php foreach ($equipment_list as $equipment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['type']); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $equipment['status']; ?>">
                                                <?php echo ucfirst($equipment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $equipment['next_maintenance_date'] ? date('M d, Y', strtotime($equipment['next_maintenance_date'])) : 'Not scheduled'; ?>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="equipment-details.php?id=<?php echo $equipment['equipment_id']; ?>" class="action-icon view" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="schedule-maintenance.php?id=<?php echo $equipment['equipment_id']; ?>" class="action-icon edit" title="Schedule Maintenance">
                                                    <i class="fas fa-tools"></i>
                                                </a>
                                                <a href="edit-equipment.php?id=<?php echo $equipment['equipment_id']; ?>" class="action-icon edit" title="Edit Equipment">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                        <p>No equipment found matching your criteria</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&status=<?php echo $status_filter; ?>&type=<?php echo urlencode($type_filter); ?>&location=<?php echo urlencode($location_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo urlencode($type_filter); ?>&location=<?php echo urlencode($location_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&type=<?php echo urlencode($type_filter); ?>&location=<?php echo urlencode($location_filter); ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo urlencode($type_filter); ?>&location=<?php echo urlencode($location_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $total_pages; ?>&status=<?php echo $status_filter; ?>&type=<?php echo urlencode($type_filter); ?>&location=<?php echo urlencode($location_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-right"></i></span>
                            <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 10px; color: var(--text-muted);">
                    Showing <?php echo count($equipment_list); ?> of <?php echo $total_records; ?> equipment
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
    </script>
</body>
</html>