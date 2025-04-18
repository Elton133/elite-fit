<?php
session_start();
require_once('../datacon.php');

// Redirect if session variables are not set
if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$user_id = $_SESSION['table_id'];

// Validate and fetch trainer_id
if (!isset($_GET['trainer_id']) || !is_numeric($_GET['trainer_id'])) {
    header("Location: ../trainer/trainers.php");
    exit();
}
$trainer_id = (int) $_GET['trainer_id'];

// Fetch trainer details
$sql_trainer = "SELECT trainer_id, first_name, last_name, specialization, profile_picture 
                FROM trainers WHERE trainer_id = ?";
$stmt_trainer = $conn->prepare($sql_trainer);
$stmt_trainer->bind_param("i", $trainer_id);
$stmt_trainer->execute();
$result_trainer = $stmt_trainer->get_result();

if ($result_trainer->num_rows === 0) {
    header("Location: ../trainer/trainers.php");
    exit();
}

$trainer = $result_trainer->fetch_assoc();
$stmt_trainer->close();

// Fetch trainer availability
$sql_availability = "SELECT day_of_week, start_time, end_time 
                     FROM trainer_availability 
                     WHERE trainer_id = ? 
                     ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$stmt_availability = $conn->prepare($sql_availability);
$stmt_availability->bind_param("i", $trainer_id);
$stmt_availability->execute();
$result_availability = $stmt_availability->get_result();
$availabilities = [];

while ($row = $result_availability->fetch_assoc()) {
    $availabilities[] = $row;
}
$stmt_availability->close();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_date = $_POST['session_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $session_type = $_POST['session_type'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (empty($session_date) || empty($start_time) || empty($end_time) || empty($session_type)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Check slot availability
        $sql_check = "SELECT session_id FROM training_sessions 
                      WHERE trainer_id = ? AND session_date = ? 
                      AND ((start_time <= ? AND end_time > ?) 
                      OR (start_time < ? AND end_time >= ?) 
                      OR (start_time >= ? AND end_time <= ?))";

        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("isssssss", $trainer_id, $session_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "This time slot is already booked. Please select another time.";
            $messageType = "error";
        } else {
            $sql_insert = "INSERT INTO training_sessions (user_id, trainer_id, session_date, start_time, end_time, session_type, notes) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iisssss", $user_id, $trainer_id, $session_date, $start_time, $end_time, $session_type, $notes);

            if ($stmt_insert->execute()) {
                $message = "Your session has been successfully scheduled!";
                $messageType = "success";
            } else {
                $message = "Error scheduling your session. Please try again.";
                $messageType = "error";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>