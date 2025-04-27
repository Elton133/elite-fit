<?php
session_start();
require_once('../datacon.php');

if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// This is an AJAX endpoint, so we'll return JSON
header('Content-Type: application/json');

$table_id = $_SESSION['table_id'];
$session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
$exercise_id = isset($_POST['exercise_id']) ? intval($_POST['exercise_id']) : 0;
$completed = isset($_POST['completed']) ? intval($_POST['completed']) : 0;
$sets_completed = isset($_POST['sets_completed']) ? intval($_POST['sets_completed']) : 0;
$reps_completed = isset($_POST['reps_completed']) ? intval($_POST['reps_completed']) : 0;
$duration_seconds = isset($_POST['duration_seconds']) ? intval($_POST['duration_seconds']) : 0;

// Validate that the session belongs to the user
$sql_check = "SELECT ws.session_id 
              FROM workout_sessions ws
              WHERE ws.session_id = ? AND ws.user_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $session_id, $table_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Check if progress entry already exists
$sql_check_progress = "SELECT progress_id FROM exercise_progress WHERE session_id = ? AND exercise_id = ?";
$stmt_check_progress = $conn->prepare($sql_check_progress);
$stmt_check_progress->bind_param("ii", $session_id, $exercise_id);
$stmt_check_progress->execute();
$result_check_progress = $stmt_check_progress->get_result();

if ($result_check_progress->num_rows > 0) {
    // Update existing progress
    $progress_id = $result_check_progress->fetch_assoc()['progress_id'];
    
    $sql_update = "UPDATE exercise_progress 
                  SET sets_completed = ?, reps_completed = ?, duration_seconds = ?, completed = ?
                  WHERE progress_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("iiiii", $sets_completed, $reps_completed, $duration_seconds, $completed, $progress_id);
    $result = $stmt_update->execute();
    $stmt_update->close();
} else {
    // Create new progress entry
    $sql_insert = "INSERT INTO exercise_progress 
                  (session_id, exercise_id, sets_completed, reps_completed, duration_seconds, completed)
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iiiiii", $session_id, $exercise_id, $sets_completed, $reps_completed, $duration_seconds, $completed);
    $result = $stmt_insert->execute();
    $stmt_insert->close();
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>