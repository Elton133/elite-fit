<?php
   
    require_once('../datacon.php');

    // Redirect if session variables are not set
    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
        header("Location: login.php");
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

    // Fetch fitness data
    $sql_fitness = "SELECT user_weight, user_height, user_bodytype,fitness_goal_1,fitness_goal_2,fitness_goal_3,experience_level, health_condition, health_condition_desc FROM user_fitness_details WHERE table_id = ?";
    $stmt_fitness = $conn->prepare($sql_fitness);
    $stmt_fitness->bind_param("i", $user_data['table_id']);
    $stmt_fitness->execute();
    $result_fitness = $stmt_fitness->get_result();
    $fitness_data = $result_fitness->fetch_assoc();
    $stmt_fitness->close();

    $sql = "SELECT wp.workout_name 
            FROM user_fitness_details ufd
            JOIN workout_plan wp ON wp.table_id IN (ufd.preferred_workout_routine_1, 
                                                    ufd.preferred_workout_routine_2, 
                                                    ufd.preferred_workout_routine_3)
            WHERE ufd.table_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $workout_preferences = [];
    while ($row = $result->fetch_assoc()) {
        $workout_preferences[] = $row['workout_name'];
    }


    // Calculate BMI
    $bmi = null;
    $bmi_category = "";
    if (!empty($fitness_data['user_weight']) && !empty($fitness_data['user_height']) && 
        is_numeric($fitness_data['user_height']) && is_numeric($fitness_data['user_weight']) && 
        $fitness_data['user_height'] > 0 && $fitness_data['user_weight'] > 0) {
        
        $weight_kg = $fitness_data['user_weight'];
        $height_m = $fitness_data['user_height'] / 100; // Convert cm to m
        $bmi = $weight_kg / ($height_m * $height_m);
        
        // Determine BMI category
        $bmi_category = ($bmi < 18.5) ? "Underweight" :
                        (($bmi < 25) ? "Normal weight" :
                        (($bmi < 30) ? "Overweight" : "Obese"));
    }

    // Handle profile picture
    $profile_pic = "../register/uploads/default-avatar.jpg"; 
    if (!empty($user_data['profile_picture'])) {
        if (file_exists("../register/uploads/" . $user_data['profile_picture'])) {
            $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
        } elseif (file_exists("../register/" . $user_data['profile_picture'])) {
            $profile_pic = "../register/" . $user_data['profile_picture'];
        }
    }

    // Initialize empty arrays for goals and preferences
    $fitness_goals = [];
    $workout_preferences = [];

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