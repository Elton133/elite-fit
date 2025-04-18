<?php
    session_start();
    require_once('../datacon.php');

    // Redirect if session variables are not set or user is not an equipment manager
    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || $_SESSION['role'] !== 'equipment_manager') {
        header("Location: ../login/index.php");
        exit();
    }

    $email = $_SESSION['email'];
    $table_id = $_SESSION['table_id'];

    // Fetch manager data
    $sql_manager = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ? AND role = 'equipment_manager'";
    $stmt_manager = $conn->prepare($sql_manager);
    $stmt_manager->bind_param("s", $email);
    $stmt_manager->execute();
    $result_manager = $stmt_manager->get_result();
    $manager_data = $result_manager->fetch_assoc();
    $stmt_manager->close();


    if (!empty($manager_data['profile_picture']) && file_exists("../register/uploads/" . $manager_data['profile_picture'])) {
        $profile_pic = "../register/uploads/" . $manager_data['profile_picture'];
    }

    // Fetch equipment statistics
    $sql_stats = "SELECT 
                COUNT(*) as total_equipment,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                FROM equipment_inventory";
    $result_stats = $conn->query($sql_stats);
    $equipment_stats = $result_stats->fetch_assoc();

    // Fetch equipment under maintenance
    $sql_maintenance = "SELECT equipment_id, name, type, location, maintenance_notes, last_maintenance_date, next_maintenance_date
                    FROM equipment_inventory
                    WHERE status = 'maintenance'
                    ORDER BY next_maintenance_date ASC
                    LIMIT 5";
    $result_maintenance = $conn->query($sql_maintenance);
    $maintenance_equipment = [];
    while ($row = $result_maintenance->fetch_assoc()) {
        $maintenance_equipment[] = $row;
    }

    // Fetch recently used equipment
    $sql_recent = "SELECT ei.equipment_id, ei.name, ei.type, ei.location, eu.user_id, eu.start_time, eu.end_time,
                u.first_name, u.last_name
                FROM equipment_usage eu
                JOIN equipment_inventory ei ON eu.equipment_id = ei.equipment_id
                JOIN user_register_details u ON eu.user_id = u.table_id
                ORDER BY eu.end_time DESC
                LIMIT 5";
    $result_recent = $conn->query($sql_recent);
    $recent_usage = [];
    while ($row = $result_recent->fetch_assoc()) {
        $recent_usage[] = $row;
    }

    // Fetch maintenance schedule
    $sql_schedule = "SELECT equipment_id, name, type, location, next_maintenance_date
                    FROM equipment_inventory
                    WHERE next_maintenance_date IS NOT NULL
                    ORDER BY next_maintenance_date ASC
                    LIMIT 5";
    $result_schedule = $conn->query($sql_schedule);
    $maintenance_schedule = [];
    while ($row = $result_schedule->fetch_assoc()) {
        $maintenance_schedule[] = $row;
    }

    // greeting
    $hour = date("H");

    // Determine the time of day
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