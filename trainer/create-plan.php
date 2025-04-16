<?php
session_start();
require_once('../datacon.php');

// Authentication
if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || $_SESSION['role'] !== 'trainer') {
    header("Location: ../login/index.php");
    exit();
}

$trainer_id = $_SESSION['table_id'];
$error_message = '';
$success_message = '';
$request_data = null;
$user_data = null;
$user_fitness_data = null;

// Get request ID
if (!isset($_GET['request_id']) || !filter_var($_GET['request_id'], FILTER_VALIDATE_INT)) {
    $error_message = "Invalid request.";
} else {
    $request_id = filter_var($_GET['request_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Fetch request details
    $sql = "SELECT wr.*, u.first_name, u.last_name, u.profile_picture, u.email, ufd.* 
            FROM workout_requests wr
            JOIN user_register_details u ON wr.user_id = u.table_id 
            LEFT JOIN user_fitness_details ufd ON wr.user_id = ufd.table_id
            WHERE wr.request_id = ? AND wr.trainer_id = ? AND wr.status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $request_id, $trainer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $request_data = $result->fetch_assoc();
        $user_data = [
            'table_id' => $request_data['user_id'],
            'first_name' => $request_data['first_name'], 
            'last_name' => $request_data['last_name'],
            'profile_picture' => $request_data['profile_picture'],
            'email' => $request_data['email']
        ];
        $fitness_columns = ['fitness_goal_1', 'fitness_goal_2', 'fitness_goal_3', 'experience_level', 
                            'preferred_days', 'health_conditions'];
        foreach ($fitness_columns as $col) {
            $user_fitness_data[$col] = $request_data[$col] ?? '';
        }
        $user_fitness_data['notes'] = $request_data['notes'];
    } else {
        $error_message = "Request not found or already processed.";
    }
    $stmt->close();
}

// Handle form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_plan']) && $request_data) {
    $plan_name = trim($_POST['plan_name']);
    $description = trim($_POST['description']);
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);
    $exercises = $_POST['exercises'] ?? [];

    if (empty($plan_name) || empty($start_date) || empty($end_date)) {
        $error_message = "Plan Name, Start Date, and End Date are required.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error_message = "End Date cannot be before Start Date.";
    } elseif (empty($exercises)) {
        $error_message = "Please add at least one exercise.";
    } else {
        $conn->begin_transaction();
        try {
            // Insert plan
            $sql = "INSERT INTO workout_plans (user_id, trainer_id, plan_name, description, start_date, end_date, status, last_updated)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissss", $request_data['user_id'], $trainer_id, $plan_name, $description, $start_date, $end_date);
            $stmt->execute();
            $plan_id = $conn->insert_id;
            $stmt->close();

            // Insert exercises
            $sql = "INSERT INTO workout_plan_exercises (plan_id, exercise_name, sets, reps, duration, day_of_week, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($exercises as $exercise) {
                if (!empty($exercise['exercise_name']) && !empty($exercise['sets']) && !empty($exercise['reps'])) {
                    $stmt->bind_param("issssss", 
                        $plan_id,
                        $exercise['exercise_name'],
                        $exercise['sets'],
                        $exercise['reps'],
                        $exercise['duration'],
                        $exercise['day_of_week'],
                        $exercise['notes']
                    );
                    $stmt->execute();
                }
            }
            $stmt->close();

            // Update request
            $sql = "UPDATE workout_requests SET status = 'completed' WHERE request_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success_message = "Workout plan created successfully!";
            header("Refresh:3; url=trainer-dashboard.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error creating plan: " . $e->getMessage();
        }
    }
}
?>

<!-- HTML VIEW -->
<!DOCTYPE html>
<html>
<head>
    <title>Create Workout Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 960px; margin-top: 2rem; }
        .exercise-group { margin-bottom: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container">
    <h3>Create Plan for <?= $user_data['first_name'] ?? 'Member' ?></h3>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php elseif ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <?php if ($request_data): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Plan Name</label>
                <input type="text" name="plan_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>

            <h5>Exercises</h5>
            <div id="exercise-container"></div>
            <button type="button" class="btn btn-outline-secondary" onclick="addExercise()">+ Add Exercise</button>

            <br><br>
            <button type="submit" name="create_plan" class="btn btn-primary">Create Plan</button>
        </form>
    <?php endif; ?>
</div>

<script>
    function addExercise() {
        const container = document.getElementById('exercise-container');
        const index = container.children.length;
        container.insertAdjacentHTML('beforeend', `
            <div class="exercise-group">
                <div class="row">
                    <div class="col">
                        <input type="text" name="exercises[${index}][exercise_name]" class="form-control" placeholder="Exercise Name" required>
                    </div>
                    <div class="col">
                        <input type="text" name="exercises[${index}][sets]" class="form-control" placeholder="Sets" required>
                    </div>
                    <div class="col">
                        <input type="text" name="exercises[${index}][reps]" class="form-control" placeholder="Reps" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col">
                        <input type="text" name="exercises[${index}][duration]" class="form-control" placeholder="Duration (optional)">
                    </div>
                    <div class="col">
                        <input type="text" name="exercises[${index}][day_of_week]" class="form-control" placeholder="Day (e.g., Monday)">
                    </div>
                    <div class="col">
                        <input type="text" name="exercises[${index}][notes]" class="form-control" placeholder="Notes">
                    </div>
                </div>
            </div>
        `);
    }
</script>
</body>
</html>
