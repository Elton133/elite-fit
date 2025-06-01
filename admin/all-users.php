<?php
include_once "../datacon.php";
include_once "../services/admin-logic.php";

// Pagination
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Build query
$query = "SELECT * FROM user_register_details WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM user_register_details WHERE 1=1";

// Add search condition
if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR contact_number LIKE ?)";
    $count_query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR contact_number LIKE ?)";
}

// Add role filter
if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $count_query .= " AND role = ?";
}

// Add sorting
$query .= " ORDER BY date_of_registration DESC LIMIT ?, ?";

// Prepare and execute count query
$count_stmt = $conn->prepare($count_query);

if (!empty($search) && !empty($role_filter)) {
    $count_stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $role_filter);
} elseif (!empty($search)) {
    $count_stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
} elseif (!empty($role_filter)) {
    $count_stmt->bind_param("s", $role_filter);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Prepare and execute main query
$stmt = $conn->prepare($query);

if (!empty($search) && !empty($role_filter)) {
    $stmt->bind_param("sssssii", $search_term, $search_term, $search_term, $search_term, $role_filter, $start, $limit);
} elseif (!empty($search)) {
    $stmt->bind_param("ssssii", $search_term, $search_term, $search_term, $search_term, $start, $limit);
} elseif (!empty($role_filter)) {
    $stmt->bind_param("sii", $role_filter, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Handle user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $user_email = $_POST['user_email'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete from user_login_details
        $stmt = $conn->prepare("DELETE FROM user_login_details WHERE username = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        
        // Delete from user_register_details
        $stmt = $conn->prepare("DELETE FROM user_register_details WHERE table_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        echo "<script>
            localStorage.setItem('toastMessage', 'User deleted successfully!');
            window.location.href = 'all-users.php';
        </script>";
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $delete_error = "Failed to delete user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .users-container {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .users-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .users-title p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .users-actions {
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
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table th {
            text-align: left;
            padding: 15px;
            background: rgba(30, 60, 114, 0.2);
            color: white;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .users-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-weight: 600;
            color: white;
        }
        
        .user-email {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .user-role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .role-member {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .role-trainer {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .role-equipment_manager {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }
        
        .role-admin {
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
        .filter-select option{
            color: black;
        }
        @media (max-width: 992px) {
            .users-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .users-actions {
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
            .users-table {
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
                    <span class="notification-badge">5</span>
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
        
        <div class="users-container">
            <div class="users-header">
                <div class="users-title">
                    <h2><i class="fas fa-users"></i> All Users</h2>
                    <p>Manage all users of EliteFit Gym</p>
                </div>
                
                <div class="users-actions">
                    <form action="" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search users..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select name="role" class="filter-select">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo ($role_filter == 'user') ? 'selected' : ''; ?>>Members</option>
                            <option value="trainer" <?php echo ($role_filter == 'trainer') ? 'selected' : ''; ?>>Trainers</option>
                            <option value="equipment_manager" <?php echo ($role_filter == 'equipment_manager') ? 'selected' : ''; ?>>Equipment Managers</option>
                            <option value="admin" <?php echo ($role_filter == 'admin') ? 'selected' : ''; ?>>Administrators</option>
                        </select>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                    
                    <a href="add-user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add User
                    </a>
                </div>
            </div>
            
            <?php if (isset($delete_error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($delete_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div class="user-avatar">
                                                <?php 
                                                $user_pic = "../register/uploads/default-avatar.jpg";
                                                if (!empty($user['profile_picture'])) {
                                                    if (file_exists("../register/uploads/" . $user['profile_picture'])) {
                                                        $user_pic = "../register/uploads/" . $user['profile_picture'];
                                                    } elseif (file_exists("../register/" . $user['profile_picture'])) {
                                                        $user_pic = "../register/" . $user['profile_picture'];
                                                    }
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User Avatar">
                                            </div>
                                            <div>
                                                <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                    <td>
                                        <span class="user-role role-<?php echo htmlspecialchars($user['role']); ?>">
                                            <?php 
                                            $role_display = str_replace('_', ' ', $user['role']);
                                            echo htmlspecialchars(ucwords($role_display)); 
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['date_of_registration'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="action-icon view" title="View User" onclick="viewUser(<?php echo $user['table_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="edit-user.php?id=<?php echo $user['table_id']; ?>" class="action-icon edit" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="action-icon delete" title="Delete User" onclick="confirmDelete(<?php echo $user['table_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>')">
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
                            <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
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
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
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
                    <p>No users found. Try adjusting your search criteria.</p>
                    <a href="all-users.php" class="btn btn-secondary">
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
                <p>Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_user" value="1">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="user_email" id="deleteUserEmail">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(90deg, #e74c3c, #c0392b);">
                        <i class="fas fa-trash-alt"></i> Delete User
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
        function confirmDelete(userId, userName, userEmail) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserEmail').value = userEmail;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        // View user details
        function viewUser(userId) {
            // Redirect to user profile page
            window.location.href = 'view-user.php?id=' + userId;
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