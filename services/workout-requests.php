<?php
session_start();
require_once('../datacon.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../login/index.php");
    exit();
}

$error = '';
$success = '';
$selected_trainer = null;
$trainer_id = null;

// Check if a trainer was selected from the trainers page
if (isset($_GET['trainer_id'])) {
    $trainer_id = intval($_GET['trainer_id']);
    
    // Fetch trainer details to display
    $stmt = $conn->prepare("SELECT trainer_id, first_name, last_name, profile_picture FROM trainers WHERE trainer_id = ?");
    $stmt->bind_param("i", $trainer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_trainer = $result->fetch_assoc();
    } else {
        $error = "Selected trainer not found.";
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = $_POST['notes'];
    $trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
    
    if (empty($trainer_id)) {
        $error = "Please select a trainer for your workout request.";
    } else if (empty($notes)) {
        $error = "Please describe your fitness goals.";
    } else {
        $user_id = $_SESSION['table_id'];
        $status = 'pending'; // Default status for new requests
        
        $stmt = $conn->prepare("INSERT INTO workout_requests (user_id, trainer_id, notes, status,request_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiss", $user_id, $trainer_id, $notes, $status);
        
        if ($stmt->execute()) {
            $success = "Your workout request has been sent successfully!";
            // Clear form data after successful submission
            $notes = '';
            $trainer_id = null;
            $selected_trainer = null;
        } else {
            $error = "Error submitting your request. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch all trainers for the dropdown (optional alternative to redirecting)
$stmt = $conn->prepare("SELECT trainer_id, first_name, last_name FROM trainers WHERE availability_status = 'Available'");
$stmt->execute();
$result = $stmt->get_result();
$trainers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>