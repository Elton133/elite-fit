<?php
session_start();
require_once('../datacon.php');

if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || !isset($_POST['action'])) {
    header("Location: ../login/index.php");
    exit();
}

$email = $_SESSION['email'];
$table_id = $_SESSION['table_id'];
$plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$action = $_POST['action'];
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$current_exercise = isset($_POST['current_exercise']) ? intval($_POST['current_exercise']) : 0;
$session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;

// Validate that the plan belongs to the user
$sql_check = "SELECT plan_id FROM workout_plans WHERE plan_id = ? AND user_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $plan_id, $table_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    // Plan doesn't exist or doesn't belong to user
    header("Location: workouts.php");
    exit();
}

switch ($action) {
    case 'start':
        // Create a new workout session
        $sql_start = "INSERT INTO workout_sessions (user_id, plan_id, start_time, status) VALUES (?, ?, NOW(), 'in_progress')";
        $stmt_start = $conn->prepare($sql_start);
        $stmt_start->bind_param("ii", $table_id, $plan_id);
        $stmt_start->execute();
        $session_id = $conn->insert_id;
        $stmt_start->close();
        break;
        
    case 'pause':
        // Update the session to paused status
        $sql_pause = "UPDATE workout_sessions SET status = 'paused', total_duration = ? WHERE session_id = ? AND user_id = ?";
        $stmt_pause = $conn->prepare($sql_pause);
        $stmt_pause->bind_param("iii", $duration, $session_id, $table_id);
        $stmt_pause->execute();
        $stmt_pause->close();
        break;
        
    case 'resume':
        // Update the session to in_progress status
        $sql_resume = "UPDATE workout_sessions SET status = 'in_progress' WHERE session_id = ? AND user_id = ?";
        $stmt_resume = $conn->prepare($sql_resume);
        $stmt_resume->bind_param("ii", $session_id, $table_id);
        $stmt_resume->execute();
        $stmt_resume->close();
        break;
        
    case 'complete':
        // Update the session to completed status
        $sql_complete = "UPDATE workout_sessions SET status = 'completed', end_time = NOW(), total_duration = ? WHERE session_id = ? AND user_id = ?";
        $stmt_complete = $conn->prepare($sql_complete);
        $stmt_complete->bind_param("iii", $duration, $session_id, $table_id);
        $stmt_complete->execute();
        $stmt_complete->close();
        break;
}

// Redirect back to the workout detail page
header("Location: workout-detail.php?plan_id=" . $plan_id);
exit();
?>