<?php
session_start();
include_once '../datacon.php';
require_once 'email_verification.php';

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function showToastAndRedirect($message, $type = 'success', $redirectUrl = '../login/index.php') {
    echo "<script>
        localStorage.setItem('toastMessage', '$message');
        localStorage.setItem('toastType', '$type');
        setTimeout(function() {
            window.location.href = '$redirectUrl';
        }, 100);
    </script>";
    exit();
}

function showToastAndGoBack($message, $type = 'error') {
    echo "<script>
        localStorage.setItem('toastMessage', '$message');
        localStorage.setItem('toastType', '$type');
        setTimeout(function() {
            window.history.back();
        }, 100);
    </script>";
    exit();
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

    // Validation with toast messages
    if (empty($first_name) || empty($last_name) || empty($contact_number) || empty($email) || empty($user_password) || empty($location) || empty($gender) || empty($date_of_birth) || empty($role)) {
        showToastAndGoBack('Please fill in all required fields!');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showToastAndGoBack('Invalid email format!');
    }

    if (!preg_match("/^[0-9]{10}$/", $contact_number)) {
        showToastAndGoBack('Invalid contact number format! Please enter a 10-digit number.');
    }

    if (strlen($user_password) < 8) {
        showToastAndGoBack('Password must be at least 8 characters long!');
    }

    // Check if email or contact number already exists
    $email_check = "SELECT * FROM user_register_details WHERE email = ? LIMIT 1";
    $contact_check = "SELECT * FROM user_register_details WHERE contact_number = ? LIMIT 1";

    $email_stmt = mysqli_prepare($conn, $email_check);
    mysqli_stmt_bind_param($email_stmt, "s", $email);
    mysqli_stmt_execute($email_stmt);
    $email_result = mysqli_stmt_get_result($email_stmt);

    $contact_stmt = mysqli_prepare($conn, $contact_check);
    mysqli_stmt_bind_param($contact_stmt, "s", $contact_number);
    mysqli_stmt_execute($contact_stmt);
    $contact_result = mysqli_stmt_get_result($contact_stmt);

    if (mysqli_num_rows($email_result) > 0) {
        showToastAndGoBack('Email already exists! Please use a different email address.');
    }

    if (mysqli_num_rows($contact_result) > 0) {
        showToastAndGoBack('Contact number already exists! Please use a different contact number.');
    }

    // File upload validation with toast messages
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profile_picture["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($profile_picture["tmp_name"]);
    if ($check === false) {
        showToastAndGoBack('File is not an image.');
    }

    if ($profile_picture["size"] > 500000) {
        showToastAndGoBack('Sorry, your file is too large.');
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        showToastAndGoBack('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');
    }

    if ($uploadOk == 0) {
        showToastAndGoBack('Sorry, your file was not uploaded.');
    } else {
        if (!move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
            showToastAndGoBack('Sorry, there was an error uploading your file.');
        }
    }

    $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
    $dob = date('Y-m-d', strtotime($date_of_birth));

    // Database Insertion with prepared statements
    $register_query = "INSERT INTO user_register_details (first_name, last_name, contact_number, email, user_password, location, gender, date_of_birth, profile_picture, role, email_verified)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

    $stmt = mysqli_prepare($conn, $register_query);
    mysqli_stmt_bind_param($stmt, "ssssssssss", $first_name, $last_name, $contact_number, $email, $hashed_password, $location, $gender, $dob, $target_file, $role);

    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);

        // Initialize OTP verification
        $emailVerification = new EmailVerification($conn);
        
        // Generate OTP
        $otp = $emailVerification->generateOTP();
        
        // Store OTP
        $otpStored = $emailVerification->storeOTP($user_id, $email, $otp);
        
        // Send OTP email
        $emailSent = false;
        if ($otpStored) {
            $emailSent = $emailVerification->sendOTPEmail($email, $first_name, $otp);
        }

        // Handle user fitness details
        if ($role === 'user') {
            $user_weight = clean_input($_POST["user_weight"]);
            $user_height = clean_input($_POST["user_height"]);
            $user_bodytype = clean_input($_POST["user_bodytype"]);
            $workout_1 = clean_input($_POST["preferred_workout_routine_1"]);
            $workout_2 = clean_input($_POST["preferred_workout_routine_2"]);
            $workout_3 = clean_input($_POST["preferred_workout_routine_3"]);

            $fitness_goal_1 = clean_input($_POST["fitness_goal_1"]);
            $fitness_goal_2 = clean_input($_POST["fitness_goal_2"]);
            $fitness_goal_3 = clean_input($_POST["fitness_goal_3"]);
            $experience_level = clean_input($_POST["experience_level"]);
            $health_condition = clean_input($_POST["health_condition"] ?? '');
            $health_condition_desc = clean_input($_POST["health_condition_desc"] ?? '');

            if ($workout_1 == $workout_2 || $workout_1 == $workout_3 || $workout_2 == $workout_3) {
                showToastAndGoBack('You cannot select the same workout routine more than once!');
            }

            $fitness_query = "INSERT INTO user_fitness_details (table_id, user_weight, user_height, user_bodytype, preferred_workout_routine_1, preferred_workout_routine_2, preferred_workout_routine_3, fitness_goal_1, fitness_goal_2, fitness_goal_3, experience_level, health_condition, health_condition_desc)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $fitness_stmt = mysqli_prepare($conn, $fitness_query);
            mysqli_stmt_bind_param($fitness_stmt, "issssssssssss", $user_id, $user_weight, $user_height, $user_bodytype, $workout_1, $workout_2, $workout_3, $fitness_goal_1, $fitness_goal_2, $fitness_goal_3, $experience_level, $health_condition, $health_condition_desc);

            if (!mysqli_stmt_execute($fitness_stmt)) {
                showToastAndGoBack('Error saving fitness details: ' . mysqli_error($conn));
            }
        }

        // Create login credentials
        $login_query = "INSERT INTO user_login_details (username, user_password) VALUES (?, ?)";
        $login_stmt = mysqli_prepare($conn, $login_query);
        mysqli_stmt_bind_param($login_stmt, "ss", $email, $hashed_password);
   
        if (!mysqli_stmt_execute($login_stmt)) {
            showToastAndGoBack('Error creating login credentials: ' . mysqli_error($conn));
        }

        // Handle trainer details
        if ($role == 'trainer') {
            $trainer_query = "INSERT INTO trainers (trainer_id, first_name, last_name, specialization, experience_years, bio, profile_picture, availability_status, email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $trainer_stmt = mysqli_prepare($conn, $trainer_query);
            mysqli_stmt_bind_param($trainer_stmt, "issssisss", $user_id, $first_name, $last_name, $specialization, $experience_years, $bio, $target_file, $availability_status, $email);
            
            if (!mysqli_stmt_execute($trainer_stmt)) {
                showToastAndGoBack('Error creating trainer details: ' . mysqli_error($conn));
            }
        }

        // Final success handling
        if ($emailSent) {
            $_SESSION['verification_email'] = $email;
            echo "<script>
                localStorage.setItem('toastMessage', 'Registration successful! Please check your email for the verification code.');
                localStorage.setItem('toastType', 'success');
                setTimeout(function() {
                    window.location.href = 'verify_otp.php';
                }, 1500);
            </script>";
        } else {
            showToastAndRedirect('Registration successful! However, there was an issue sending the verification code. Please contact support.', 'warning');
        }
        exit();
    } else {
        showToastAndGoBack('Error: ' . mysqli_error($conn));
    }

    mysqli_close($conn);
}
?>