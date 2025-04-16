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
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Workout Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.container { max-width: 700px; margin-top: 2rem; }</style>
</head>
<body>
<div class="container">
    <h3>Make a Workout Request</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Describe your fitness goals / workout needs</label>
            <textarea name="notes" class="form-control" rows="5" required></textarea>
        </div>
        <div class="mb-3">
            <label>Assign to Trainer (optional)</label>
            <input type="number" name="trainer_id" class="form-control" placeholder="Trainer ID">
        </div>
        <button type="submit" class="btn btn-primary">Send Request</button>
    </form>
</div>
</body>
</html>
