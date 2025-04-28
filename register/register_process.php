<?php
session_start();
include_once '../datacon.php';

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = clean_input($_POST["first_name"]);
    $last_name = clean_input($_POST["last_name"]);
    $contact_number = clean_input($_POST["contact_number"]);
    $email = clean_input($_POST["email"]);
    $user_password = clean_input($_POST["user_password"]);
    $location = clean_input($_POST["location"]);
    $gender = clean_input($_POST["gender"]);
    $date_of_birth = clean_input($_POST["date_of_birth"]);
    $profile_picture = $_FILES["profile_picture"];
    $role = clean_input($_POST["role"]);
    $specialization = clean_input($_POST["specialization"]);
    $experience_years = clean_input(($_POST["experience_years"] ?? 0));
    $bio = clean_input($_POST["bio"]);
    $availability_status = clean_input($_POST["availability_status"]);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($contact_number) || empty($email) || empty($user_password) || empty($location) || empty($gender) || empty($date_of_birth) || empty($role)) {
        echo "<script>alert('Please fill in all required fields!'); window.history.back();</script>";
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!'); window.history.back();</script>";
        exit();
    }

    // Validate contact number format (basic check for digits and length)
    if (!preg_match("/^[0-9]{10}$/", $contact_number)) {
        echo "<script>alert('Invalid contact number format! Please enter a 10-digit number.'); window.history.back();</script>";
        exit();
    }

    // Validate password strength (example: at least 8 characters)
    if (strlen($user_password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long!'); window.history.back();</script>";
        exit();
    }

    // Check if email or contact number already exists in the database
    $email_check = "SELECT * FROM user_register_details WHERE email = '$email' LIMIT 1";
    $contact_check = "SELECT * FROM user_register_details WHERE contact_number = '$contact_number' LIMIT 1";

    $email_result = mysqli_query($conn, $email_check);
    $contact_result = mysqli_query($conn, $contact_check);

    if (mysqli_num_rows($email_result) > 0) {
        echo "<script>alert('Email already exists! Please use a different email address.'); window.history.back();</script>";
        exit();
    }

    if (mysqli_num_rows($contact_result) > 0) {
        echo "<script>alert('Contact number already exists! Please use a different contact number.'); window.history.back();</script>";
        exit();
    }

    // Profile Picture Upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profile_picture["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($profile_picture["tmp_name"]);
    if ($check === false) {
        echo "<script>alert('File is not an image.'); window.history.back();</script>";
        $uploadOk = 0;
        exit();
    }

    // Check file size
    if ($profile_picture["size"] > 500000) {
        echo "<script>alert('Sorry, your file is too large.'); window.history.back();</script>";
        $uploadOk = 0;
        exit();
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.'); window.history.back();</script>";
        $uploadOk = 0;
        exit();
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "<script>alert('Sorry, your file was not uploaded.'); window.history.back();</script>";
        exit();
    } else {
        if (move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
            //echo "The file ". htmlspecialchars( basename( $_FILES["profile_picture"]["name"])). " has been uploaded.";
        } else {
            echo "<script>alert('Sorry, there was an error uploading your file.'); window.history.back();</script>";
            exit();
        }
    }

    // Hash the password
    $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);

    // Convert date of birth to MySQL format
    $dob = date('Y-m-d', strtotime($date_of_birth));

    // Database Insertion
    $register_query = "INSERT INTO user_register_details (first_name, last_name, contact_number, email, user_password, location, gender, date_of_birth, profile_picture, role)
    VALUES ('$first_name', '$last_name', '$contact_number', '$email', '$hashed_password', '$location', '$gender', '$dob', '$target_file', '$role')";

    if (mysqli_query($conn, $register_query)) {
        $user_id = mysqli_insert_id($conn); // Get the last inserted ID

        // Only insert fitness details for regular users
        if ($role === 'user') {
            // User Fitness Details
            $user_weight = clean_input($_POST["user_weight"]);
            $user_height = clean_input($_POST["user_height"]);
            $user_bodytype = clean_input($_POST["user_bodytype"]);
            $workout_1 = clean_input($_POST["preferred_workout_routine_1"]);
            $workout_2 = clean_input($_POST["preferred_workout_routine_2"]);
            $workout_3 = clean_input($_POST["preferred_workout_routine_3"]);

            // Fitness Goals and Health Details
            $fitness_goal_1 = clean_input($_POST["fitness_goal_1"]);
            $fitness_goal_2 = clean_input($_POST["fitness_goal_2"]);
            $fitness_goal_3 = clean_input($_POST["fitness_goal_3"]);
            $experience_level = clean_input($_POST["experience_level"]);
            $health_condition = clean_input($_POST["health_condition"]);
            $health_condition_desc = clean_input($_POST["health_condition_desc"]);

            // Check if workout routines are unique
            if ($workout_1 == $workout_2 || $workout_1 == $workout_3 || $workout_2 == $workout_3) {
                echo "<script>alert('You cannot select the same workout routine more than once!'); window.history.back();</script>";
                exit();
            }

            $fitness_query = "INSERT INTO user_fitness_details (table_id, user_weight, user_height, user_bodytype, preferred_workout_routine_1, preferred_workout_routine_2, preferred_workout_routine_3, fitness_goal_1, fitness_goal_2, fitness_goal_3, experience_level, health_condition, health_condition_desc)
                VALUES ('$user_id', '$user_weight', '$user_height', '$user_bodytype', '$workout_1', '$workout_2', '$workout_3', '$fitness_goal_1', '$fitness_goal_2', '$fitness_goal_3', '$experience_level', '$health_condition', '$health_condition_desc')";

            if (!mysqli_query($conn, $fitness_query)) {
                echo "<script>alert('Error saving fitness details: " . mysqli_error($conn) . "'); window.history.back();</script>";
                exit();
            }
        }

        $login_query = "INSERT INTO user_login_details (username, user_password) VALUES ('$email', '$hashed_password')";
   
        if (!mysqli_query($conn, $login_query)) {
            echo "<script>alert('Error creating login credentials: " . mysqli_error($conn) . "'); window.history.back();</script>";
            exit();
        }

        if ($role == 'trainer') {
            $trainer_query = "INSERT INTO trainers (trainer_id,first_name, last_name, specialization, experience_years, bio, profile_picture, availability_status, email)
            VALUES ('$user_id','$first_name', '$last_name', '$specialization', '$experience_years', '$bio', '$target_file', '$availability_status', '$email')";
            
            if (!mysqli_query($conn, $trainer_query)) {
                echo "<script>alert('Error creating trainer details: " . mysqli_error($conn) . "'); window.history.back();</script>";
                exit();
            }
        }

        echo "<script>alert('Registration successful!'); window.location.href='../login/index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }

    mysqli_close($conn);
}
?>
