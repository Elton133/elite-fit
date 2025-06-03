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

// Pagination
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Build query
$query = "SELECT * FROM equipment_inventory WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM equipment_inventory WHERE 1=1";

// Add search condition
if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (name LIKE ? OR type LIKE ? OR brand LIKE ? OR model LIKE ? OR location LIKE ?)";
    $count_query .= " AND (name LIKE ? OR type LIKE ? OR brand LIKE ? OR model LIKE ? OR location LIKE ?)";
}

// Add status filter
if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $count_query .= " AND status = ?";
}

// Add type filter
if (!empty($type_filter)) {
    $query .= " AND type = ?";
    $count_query .= " AND type = ?";
}

// Add sorting
$query .= " ORDER BY equipment_id DESC LIMIT ?, ?";

// Prepare and execute count query
$count_stmt = $conn->prepare($count_query);

$bind_params = [];
$bind_types = "";

if (!empty($search)) {
    $bind_params = array_merge($bind_params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
    $bind_types .= "sssss";
}

if (!empty($status_filter)) {
    $bind_params[] = $status_filter;
    $bind_types .= "s";
}

if (!empty($type_filter)) {
    $bind_params[] = $type_filter;
    $bind_types .= "s";
}

if (!empty($bind_params)) {
    $count_stmt->bind_param($bind_types, ...$bind_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Prepare and execute main query
$stmt = $conn->prepare($query);

// Add pagination parameters
$bind_params[] = $start;
$bind_params[] = $limit;
$bind_types .= "ii";

if (!empty($bind_params)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}

$stmt->execute();
$result = $stmt->get_result();
$equipment = $result->fetch_all(MYSQLI_ASSOC);

// Handle equipment deletion
if (isset($_POST['delete_equipment']) && isset($_POST['equipment_id'])) {
    $equipment_id = $_POST['equipment_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete from equipment_usage first (if exists)
        $stmt = $conn->prepare("DELETE FROM equipment_usage WHERE equipment_id = ?");
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        
        // Delete from equipment_inventory
        $stmt = $conn->prepare("DELETE FROM equipment_inventory WHERE equipment_id = ?");
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        echo "<script>
            localStorage.setItem('toastMessage', 'Equipment deleted successfully!');
            window.location.href = 'all-equipments.php';
        </script>";
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $delete_error = "Failed to delete equipment: " . $e->getMessage();
    }
}

// Get unique equipment types for filter
$types_query = "SELECT DISTINCT type FROM equipment_inventory ORDER BY type";
$types_result = $conn->query($types_query);
$equipment_types = [];
while ($row = $types_result->fetch_assoc()) {
    $equipment_types[] = $row['type'];
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
    <title>All Equipment - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .equipment-container {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .equipment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .equipment-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .equipment-title p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .equipment-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-input {
            padding: 10px 15px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            min-width: 250px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .filter-select {
            padding: 10px 15px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .filter-select option {
            color: black;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: var(--font-family);
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .equipment-table th {
            text-align: left;
            padding: 15px;
            background: rgba(30, 60, 114, 0.2);
            color: white;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .equipment-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .equipment-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .equipment-table tr:last-child td {
            border-bottom: none;
        }
        
        .equipment-name {
            font-weight: 600;
            color: white;
        }
        
        .equipment-details {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .equipment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
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
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .action-icon:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: scale(1.1);
        }
        
        .action-icon.view:hover {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .action-icon.edit:hover {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }
        
        .action-icon.delete:hover {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .pagination a, .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: var(--primary-color);
            box-shadow: 0 4px 10px rgba(30, 60, 114, 0.3);
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 0;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-results i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(40, 40, 40, 0.95);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header h3 {
            font-size: 24px;
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close-modal:hover {
            color: white;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 992px) {
            .equipment-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .equipment-actions {
                width: 100%;
            }
            
            .search-form {
                width: 100%;
            }
            
            .search-input {
                flex: 1;
            }
        }
        
        @media (max-width: 768px) {
            .equipment-table {
                display: block;
                overflow-x: auto;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input, .filter-select, .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
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
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Admin Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($admin_data['first_name'] ?? 'Admin') . ' ' . htmlspecialchars($admin_data['last_name'] ?? '') ?></h3>
                        <p class="user-status">Administrator</p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="equipment-container">
            <div class="equipment-header">
                <div class="equipment-title">
                    <h2><i class="fas fa-dumbbell"></i> All Equipment</h2>
                    <p>Manage all equipment in EliteFit Gym</p>
                </div>
                
                <div class="equipment-actions">
                    <form action="" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search equipment..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select name="status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="available" <?php echo ($status_filter == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="in_use" <?php echo ($status_filter == 'in_use') ? 'selected' : ''; ?>>In Use</option>
                            <option value="maintenance" <?php echo ($status_filter == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                        
                        <select name="type" class="filter-select">
                            <option value="">All Types</option>
                            <?php foreach ($equipment_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($type_filter == $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                    
                    <a href="add-equipment.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Equipment
                    </a>
                </div>
            </div>
            
            <?php if (isset($delete_error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($delete_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($equipment) > 0): ?>
                <div class="table-responsive">
                    <table class="equipment-table">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Type & Brand</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Purchase Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div class="equipment-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="equipment-details">
                                                ID: <?php echo htmlspecialchars($item['equipment_id']); ?>
                                                <?php if (!empty($item['serial_number'])): ?>
                                                    | S/N: <?php echo htmlspecialchars($item['serial_number']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="equipment-name"><?php echo htmlspecialchars($item['type']); ?></div>
                                        <div class="equipment-details">
                                            <?php if (!empty($item['brand'])): ?>
                                                <?php echo htmlspecialchars($item['brand']); ?>
                                                <?php if (!empty($item['model'])): ?>
                                                    - <?php echo htmlspecialchars($item['model']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                No brand specified
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td>
                                        <span class="equipment-status status-<?php echo htmlspecialchars($item['status']); ?>">
                                            <?php 
                                            $status_display = str_replace('_', ' ', $item['status']);
                                            echo htmlspecialchars(ucwords($status_display)); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($item['purchase_date'])) {
                                            echo date('M d, Y', strtotime($item['purchase_date']));
                                        } else {
                                            echo 'Not specified';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="action-icon view" title="View Equipment" onclick="viewEquipment(<?php echo $item['equipment_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="edit-equipment.php?id=<?php echo $item['equipment_id']; ?>" class="action-icon edit" title="Edit Equipment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="action-icon delete" title="Delete Equipment" onclick="confirmDelete(<?php echo $item['equipment_id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        
                        if ($end_page - $start_page < 4 && $start_page > 1) {
                            $start_page = max(1, $end_page - 4);
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-right"></i></span>
                            <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No equipment found. Try adjusting your search criteria.</p>
                    <a href="all-equipments.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Confirm Deletion</h3>
                <button class="close-modal" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the equipment <strong id="deleteEquipmentName"></strong>?</p>
                <p>This action cannot be undone and will also remove all usage records for this equipment.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_equipment" value="1">
                    <input type="hidden" name="equipment_id" id="deleteEquipmentId">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(90deg, #e74c3c, #c0392b);">
                        <i class="fas fa-trash-alt"></i> Delete Equipment
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="admin-sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Show delete confirmation modal
        function confirmDelete(equipmentId, equipmentName) {
            document.getElementById('deleteEquipmentId').value = equipmentId;
            document.getElementById('deleteEquipmentName').textContent = equipmentName;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        // View equipment details
        function viewEquipment(equipmentId) {
            // Redirect to equipment details page
            window.location.href = 'view-equipment.php?id=' + equipmentId;
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
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
    </script>
</body>
</html>