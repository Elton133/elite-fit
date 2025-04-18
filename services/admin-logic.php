<?php
    session_start();
    require_once('../datacon.php');

    // // Redirect if session variables are not set or user is not admin
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