<?php
include_once "../datacon.php";
include_once "../services/admin-logic.php";

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'user_registrations';

// Function to get user registrations by date
function getUserRegistrations($conn, $start_date, $end_date) {
    $query = "SELECT DATE(date_of_registration) as reg_date, COUNT(*) as count 
              FROM user_register_details 
              WHERE date_of_registration BETWEEN ? AND ? 
              GROUP BY DATE(date_of_registration) 
              ORDER BY reg_date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get user registrations by role
function getUsersByRole($conn) {
    $query = "SELECT role, COUNT(*) as count FROM user_register_details GROUP BY role";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get equipment usage statistics
function getEquipmentStats($conn) {
    // This is a placeholder. In a real system, you would have a table tracking equipment usage
    $data = [
        ['equipment_name' => 'Treadmill', 'usage_count' => 245, 'maintenance_count' => 3],
        ['equipment_name' => 'Bench Press', 'usage_count' => 189, 'maintenance_count' => 1],
        ['equipment_name' => 'Leg Press', 'usage_count' => 167, 'maintenance_count' => 2],
        ['equipment_name' => 'Rowing Machine', 'usage_count' => 132, 'maintenance_count' => 0],
        ['equipment_name' => 'Elliptical', 'usage_count' => 120, 'maintenance_count' => 1]
    ];
    
    return $data;
}

// Function to get workout plan popularity
function getWorkoutPlanStats($conn) {
    // This is a placeholder. In a real system, you would have tables tracking workout plans and their usage
    $data = [
        ['workout_name' => 'Full Body Workout', 'user_count' => 78],
        ['workout_name' => 'Upper Body Focus', 'user_count' => 65],
        ['workout_name' => 'Lower Body Focus', 'user_count' => 52],
        ['workout_name' => 'Core Strength', 'user_count' => 45],
        ['workout_name' => 'Cardio Blast', 'user_count' => 40]
    ];
    
    return $data;
}

// Get report data based on selected type
switch ($report_type) {
    case 'user_registrations':
        $report_data = getUserRegistrations($conn, $start_date, $end_date);
        $report_title = 'User Registrations';
        $report_description = 'Number of new user registrations over time';
        break;
    
    case 'users_by_role':
        $report_data = getUsersByRole($conn);
        $report_title = 'Users by Role';
        $report_description = 'Distribution of users by their assigned roles';
        break;
    
    case 'equipment_usage':
        $report_data = getEquipmentStats($conn);
        $report_title = 'Equipment Usage';
        $report_description = 'Usage statistics for gym equipment';
        break;
    
    case 'workout_plans':
        $report_data = getWorkoutPlanStats($conn);
        $report_title = 'Workout Plan Popularity';
        $report_description = 'Most popular workout plans among users';
        break;
    
    default:
        $report_data = getUserRegistrations($conn, $start_date, $end_date);
        $report_title = 'User Registrations';
        $report_description = 'Number of new user registrations over time';
}

// Generate CSV export if requested
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers based on report type
    switch ($report_type) {
        case 'user_registrations':
            fputcsv($output, ['Date', 'Number of Registrations']);
            foreach ($report_data as $row) {
                fputcsv($output, [$row['reg_date'], $row['count']]);
            }
            break;
        
        case 'users_by_role':
            fputcsv($output, ['Role', 'Number of Users']);
            foreach ($report_data as $row) {
                fputcsv($output, [$row['role'], $row['count']]);
            }
            break;
        
        case 'equipment_usage':
            fputcsv($output, ['Equipment Name', 'Usage Count', 'Maintenance Count']);
            foreach ($report_data as $row) {
                fputcsv($output, [$row['equipment_name'], $row['usage_count'], $row['maintenance_count']]);
            }
            break;
        
        case 'workout_plans':
            fputcsv($output, ['Workout Plan', 'Number of Users']);
            foreach ($report_data as $row) {
                fputcsv($output, [$row['workout_name'], $row['user_count']]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-container {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .reports-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reports-title p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .reports-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-input {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-family: var(--font-family);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
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
        
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .report-tab {
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-weight: 500;
        }
        
        .report-tab:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .report-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }
        
        .chart-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: 400px;
            position: relative;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        
        .data-table th {
            text-align: left;
            padding: 15px;
            background: rgba(30, 60, 114, 0.2);
            color: white;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .export-options {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .no-data {
            text-align: center;
            padding: 50px 0;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }
        
        @media (max-width: 992px) {
            .reports-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .reports-actions {
                width: 100%;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .form-group {
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
        
        <div class="reports-container">
            <div class="reports-header">
                <div class="reports-title">
                    <h2><i class="fas fa-chart-bar"></i> <?php echo htmlspecialchars($report_title); ?></h2>
                    <p><?php echo htmlspecialchars($report_description); ?></p>
                </div>
                
                <div class="reports-actions">
                    <a href="?report_type=<?php echo urlencode($report_type); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&export=csv" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
            
            <div class="report-tabs">
                <a href="?report_type=user_registrations" class="report-tab <?php echo ($report_type == 'user_registrations') ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> User Registrations
                </a>
                <a href="?report_type=users_by_role" class="report-tab <?php echo ($report_type == 'users_by_role') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Users by Role
                </a>
                <a href="?report_type=equipment_usage" class="report-tab <?php echo ($report_type == 'equipment_usage') ? 'active' : ''; ?>">
                    <i class="fas fa-dumbbell"></i> Equipment Usage
                </a>
                <a href="?report_type=workout_plans" class="report-tab <?php echo ($report_type == 'workout_plans') ? 'active' : ''; ?>">
                    <i class="fas fa-running"></i> Workout Plans
                </a>
            </div>
            
            <?php if ($report_type == 'user_registrations' || $report_type == 'equipment_usage'): ?>
                <form action="" method="GET" class="filter-form">
                    <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
                    
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-input" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-input" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="form-group" style="justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="chart-container">
                <canvas id="reportChart"></canvas>
            </div>
            
            <?php if (count($report_data) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if ($report_type == 'user_registrations'): ?>
                                    <th>Date</th>
                                    <th>Number of Registrations</th>
                                <?php elseif ($report_type == 'users_by_role'): ?>
                                    <th>Role</th>
                                    <th>Number of Users</th>
                                <?php elseif ($report_type == 'equipment_usage'): ?>
                                    <th>Equipment Name</th>
                                    <th>Usage Count</th>
                                    <th>Maintenance Count</th>
                                <?php elseif ($report_type == 'workout_plans'): ?>
                                    <th>Workout Plan</th>
                                    <th>Number of Users</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <?php if ($report_type == 'user_registrations'): ?>
                                        <td><?php echo date('M d, Y', strtotime($row['reg_date'])); ?></td>
                                        <td><?php echo $row['count']; ?></td>
                                    <?php elseif ($report_type == 'users_by_role'): ?>
                                        <td>
                                            <?php 
                                            $role_display = str_replace('_', ' ', $row['role']);
                                            echo htmlspecialchars(ucwords($role_display)); 
                                            ?>
                                        </td>
                                        <td><?php echo $row['count']; ?></td>
                                    <?php elseif ($report_type == 'equipment_usage'): ?>
                                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                                        <td><?php echo $row['usage_count']; ?></td>
                                        <td><?php echo $row['maintenance_count']; ?></td>
                                    <?php elseif ($report_type == 'workout_plans'): ?>
                                        <td><?php echo htmlspecialchars($row['workout_name']); ?></td>
                                        <td><?php echo $row['user_count']; ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-bar"></i>
                    <p>No data available for the selected report and date range.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="admin-sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('reportChart').getContext('2d');
            
            <?php if ($report_type == 'user_registrations'): ?>
                const labels = [<?php echo implode(', ', array_map(function($item) { return "'" . date('M d', strtotime($item['reg_date'])) . "'"; }, $report_data)); ?>];
                const data = [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $report_data)); ?>];
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Registrations',
                            data: data,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            <?php elseif ($report_type == 'users_by_role'): ?>
                const labels = [<?php echo implode(', ', array_map(function($item) { return "'" . ucwords(str_replace('_', ' ', $item['role'])) . "'"; }, $report_data)); ?>];
                const data = [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $report_data)); ?>];
                const backgroundColors = [
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(155, 89, 182, 0.7)'
                ];
                
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: backgroundColors,
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        }
                    }
                });
            <?php elseif ($report_type == 'equipment_usage'): ?>
                const labels = [<?php echo implode(', ', array_map(function($item) { return "'" . $item['equipment_name'] . "'"; }, $report_data)); ?>];
                const usageData = [<?php echo implode(', ', array_map(function($item) { return $item['usage_count']; }, $report_data)); ?>];
                const maintenanceData = [<?php echo implode(', ', array_map(function($item) { return $item['maintenance_count']; }, $report_data)); ?>];
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Usage Count',
                                data: usageData,
                                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                                borderColor: 'rgba(52, 152, 219, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Maintenance Count',
                                data: maintenanceData,
                                backgroundColor: 'rgba(231, 76, 60, 0.7)',
                                borderColor: 'rgba(231, 76, 60, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            <?php elseif ($report_type == 'workout_plans'): ?>
                const labels = [<?php echo implode(', ', array_map(function($item) { return "'" . $item['workout_name'] . "'"; }, $report_data)); ?>];
                const data = [<?php echo implode(', ', array_map(function($item) { return $item['user_count']; }, $report_data)); ?>];
                
                new Chart(ctx, {
                    type: 'horizontalBar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Users',
                            data: data,
                            backgroundColor: [
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(241, 196, 15, 0.7)',
                                'rgba(231, 76, 60, 0.7)',
                                'rgba(155, 89, 182, 0.7)'
                            ],
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>