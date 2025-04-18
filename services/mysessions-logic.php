<?php
session_start();
require_once('../datacon.php');

include '../services/welcome-logic.php';

// Redirect if session variables are not set
if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$user_id = $_SESSION['table_id'];

// Fetch user's scheduled sessions
$sql_sessions = "SELECT ts.session_id, ts.session_date, ts.start_time, ts.end_time, 
                ts.session_type, ts.session_status, ts.notes,
                t.first_name, t.last_name, t.profile_picture
                FROM training_sessions ts
                JOIN trainers t ON ts.trainer_id = t.trainer_id
                WHERE ts.user_id = ?
                ORDER BY ts.session_date DESC, ts.start_time DESC";

$stmt_sessions = $conn->prepare($sql_sessions);
$stmt_sessions->bind_param("i", $user_id);
$stmt_sessions->execute();
$result_sessions = $stmt_sessions->get_result();
?>