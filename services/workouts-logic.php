<?php
    session_start();
    require_once('../datacon.php');

    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
        header("Location: ../login/index.php");
        exit();
    }

    $email = $_SESSION['email'];
    $table_id = $_SESSION['table_id'];

    // Fetch user data
    $sql_user = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();

    // Fetch workout plans
    $sql_plans = "SELECT * FROM workout_plans WHERE user_id = ?";
    $stmt_plans = $conn->prepare($sql_plans);
    $stmt_plans->bind_param("i", $user_data['table_id']);
    $stmt_plans->execute();
    $result_plans = $stmt_plans->get_result();
    $plans = $result_plans->fetch_all(MYSQLI_ASSOC);
    $stmt_plans->close();

    // Fetch exercises for all plans
    $exercises = [];
    if (!empty($plans)) {
        $plan_ids = array_column($plans, 'plan_id');
        $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));

        $sql_exercises = "SELECT * FROM workout_plan_exercises WHERE plan_id IN ($placeholders)";
        $stmt_exercises = $conn->prepare($sql_exercises);
        $stmt_exercises->bind_param(str_repeat('i', count($plan_ids)), ...$plan_ids);
        $stmt_exercises->execute();
        $result_exercises = $stmt_exercises->get_result();
        $exercises = $result_exercises->fetch_all(MYSQLI_ASSOC);
        $stmt_exercises->close();
    }

    $profile_pic = "../register/uploads/default-avatar.jpg";
    if (!empty($user_data['profile_picture']) && file_exists("../register/uploads/" . $user_data['profile_picture'])) {
        $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
    }

    $hour = date("H");
    $greeting = ($hour >= 5 && $hour < 12) ? "Good morning" : (($hour < 17) ? "Good afternoon" : (($hour < 21) ? "Good evening" : "Good night"));
?>