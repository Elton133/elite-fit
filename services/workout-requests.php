<?php
session_start();
require_once('../datacon.php');

// Auth check for user
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login/index.php");
    exit();
}

$user_id = $_SESSION['table_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = trim($_POST['notes'] ?? '');
    $trainer_id = $_POST['trainer_id'] ?? null;

    if (empty($notes)) {
        $error = "Please describe your workout request.";
    } else {
        $sql = "INSERT INTO workout_requests (user_id, trainer_id, notes, status, request_date) 
                VALUES (?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $trainer_id, $notes);

        if ($stmt->execute()) {
            $success = "Request submitted! Trainer will review it soon.";
            $message = "Request submitted! Trainer will review it soon";
                echo "<script>
                   localStorage.setItem('toastMessage', '$message');
                   setTimeout(function() {
                       window.location.href='../welcome/welcome.php';
                   }, 100);
                </script>";
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}
?>