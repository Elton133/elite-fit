<?php
include_once "../datacon.php";

// a superglobal that has a request method post, and anything received from the frontend is cleaned 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    function clean_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    // using random_int(), which is better and more secure as opposed to shuffle
//    function generate_password($length = 12) {
//     $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+';
//     $password = '';

//     for ($i = 0; $i < $length; $i++) {
//         $password .= $chars[random_int(0, strlen($chars) - 1)];
//     }

//     return $password;
// }

    // function generate_strong_password($length = 12) {
    //     $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+';
    //     return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    // }

    // User Registration Details
    $first_name = clean_input($_POST["first_name"]);
    $last_name = clean_input($_POST["last_name"]);
    $contact_number = clean_input($_POST["contact_number"]);
    $email = clean_input($_POST["email"]);
    $user_password = clean_input($_POST["user_password"]);
    $location = clean_input($_POST["location"]);
    $gender = clean_input($_POST["gender"]);
    $date_of_birth = clean_input($_POST["date_of_birth"]);
    $profile_picture = $_FILES["profile_picture"];

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

    $dob = date("Y-m-d", strtotime($date_of_birth));
    $today = date("Y-m-d");
    $age = date_diff(date_create($dob), date_create($today))->y;

    if ($age < 18 || $age > 69) {
        echo "<script>alert('You must be at least 18 and less than 70 years old to register!'); window.history.back();</script>";
        exit();
    }

    $check_query = "SELECT * FROM user_register_details WHERE email='$email' OR contact_number='$contact_number'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Email or contact number already registered!'); window.history.back();</script>";
        exit();
    }

    if ($workout_1 == $workout_2 || $workout_1 == $workout_3 || $workout_2 == $workout_3) {
        echo "<script>alert('You cannot select the same workout routine more than once!'); window.history.back();</script>";
        exit();
    }

    $target_dir = "uploads/";
    $profile_pic_name = basename($profile_picture["name"]);
    $target_file = $target_dir . time() . "_" . $profile_pic_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
        echo "<script>alert('Only JPG, JPEG, and PNG files are allowed for profile pictures!'); window.history.back();</script>";
        exit();
    }

    if (!move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
        echo "<script>alert('Error uploading profile picture!'); window.history.back();</script>";
        exit();
    }

    $register_query = "INSERT INTO user_register_details (first_name, last_name, contact_number, email, user_password,location, gender, date_of_birth, profile_picture)
        VALUES ('$first_name', '$last_name', '$contact_number', '$email', '$user_password', '$location', '$gender', '$dob', '$target_file')";

    if (!mysqli_query($conn, $register_query)) {
        echo "<script>alert('Error registering user: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }

    $user_id = mysqli_insert_id($conn);

    $fitness_query = "INSERT INTO user_fitness_details (table_id, user_weight, user_height, user_bodytype, preferred_workout_routine_1, preferred_workout_routine_2, preferred_workout_routine_3, fitness_goal_1, fitness_goal_2, fitness_goal_3, experience_level, health_condition, health_condition_desc)
        VALUES ('$user_id', '$user_weight', '$user_height', '$user_bodytype', '$workout_1', '$workout_2', '$workout_3', '$fitness_goal_1', '$fitness_goal_2', '$fitness_goal_3', '$experience_level', '$health_condition', '$health_condition_desc')";

    if (!mysqli_query($conn, $fitness_query)) {
        echo "<script>alert('Error saving fitness details: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }

    // $login_query = "INSERT INTO user_login_details (username, user_password) VALUES ('$email', '$user_password')";

    // if (!mysqli_query($conn, $login_query)) {
    //     echo "<script>alert('Error storing login credentials: " . mysqli_error($conn) . "'); window.history.back();</script>";
    //     exit();
    // }



    $hashed_password = password_hash($user_password, PASSWORD_BCRYPT);



    $login_query = "INSERT INTO user_login_details (username, user_password) VALUES ('$email', '$hashed_password')";
   
    if (!mysqli_query($conn, $login_query)) {
        echo "<script>alert('Error creating login credentials: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }
    echo "<script>alert('Registration successful! '); window.location.href='../login';</script>";

   
}
?>