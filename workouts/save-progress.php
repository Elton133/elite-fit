<?php
session_start();
require_once('../datacon.php');

if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: ../login/index.php");
    exit();
}

$table_id = $_SESSION['table_id'];

// Get form data
$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;
$body_fat = isset($_POST['body_fat']) ? floatval($_POST['body_fat']) : null;
$muscle_mass = isset($_POST['muscle_mass']) ? floatval($_POST['muscle_mass']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$measurement_date = date('Y-m-d');

// Validate data
if ($weight === null && $body_fat === null && $muscle_mass === null) {
    // No metrics provided
    $_SESSION['error_message'] = "Please provide at least one measurement.";
    header("Location: progress.php");
    exit();
}

// Insert progress data
$sql = "INSERT INTO user_progress_metrics (user_id, weight, body_fat_percentage, muscle_mass, measurement_date, notes) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("idddss", $table_id, $weight, $body_fat, $muscle_mass, $measurement_date, $notes);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    $_SESSION['success_message'] = "Progress recorded successfully!";
} else {
    $_SESSION['error_message'] = "Error recording progress. Please try again.";
}

header("Location: progress.php");
exit();
?>