<?php
include('../datacon.php');

if (isset($_POST['add_user'])) {
    // Sanitize & get data
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = $_POST['user_password'];
    $location = $_POST['location'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $role = $_POST['role'];

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Upload profile picture
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);

    // Insert into user_register_details
    $register_query = "INSERT INTO user_register_details 
        (first_name, last_name, contact_number, email, user_password, location, gender, date_of_birth, profile_picture, role) 
        VALUES 
        ('$first_name', '$last_name', '$contact_number', '$email', '$hashed_password', '$location', '$gender', '$dob', '$target_file', '$role')";
    
    if (!mysqli_query($conn, $register_query)) {
        echo "<script>alert('Failed to create user profile: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }

    // Insert login details
    $login_query = "INSERT INTO user_login_details (username, user_password) 
                    VALUES ('$email', '$hashed_password')";

    if (!mysqli_query($conn, $login_query)) {
        echo "<script>alert('Failed to create login account: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }

    // If trainer, insert into trainers table
    if ($role === 'trainer') {
        $specialization = $_POST['specialization'];
        $experience_years = $_POST['experience_years'];
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $availability_status = $_POST['availability_status'];

        $trainer_query = "INSERT INTO trainers 
            (first_name, last_name, specialization, experience_years, bio, profile_picture, availability_status) 
            VALUES 
            ('$first_name', '$last_name', '$specialization', '$experience_years', '$bio', '$target_file', '$availability_status')";

        if (!mysqli_query($conn, $trainer_query)) {
            echo "<script>alert('Trainer creation failed: " . mysqli_error($conn) . "'); window.history.back();</script>";
            exit();
        }
    }

    echo "<script>alert('User created successfully!'); window.location.href='../admin/admin_dashboard.php';</script>";
}
?>
