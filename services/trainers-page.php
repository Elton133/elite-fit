<?php
session_start();
require_once('../datacon.php');

// Redirect if session variables are not set
if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$table_id = $_SESSION['table_id'];

// Fetch trainers
$sql_trainers = "SELECT trainer_id, first_name, last_name, specialization, experience_years,bio,availability_status, profile_picture FROM trainers WHERE availability_status = 'Available'";
$result_trainers = $conn->query($sql_trainers);
?>