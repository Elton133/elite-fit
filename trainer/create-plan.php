<?php include '../services/create-plan-logic.php'?>


<!DOCTYPE html>
<html>
<head>
    <title>Create Workout Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
</head>
<body>
<div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include 'trainer-sidebar.php'; ?>
<div class="container">
<div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
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
<script src="sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script>

        const msg = localStorage.getItem('toastMessage');
        if (msg) {
          Toastify({
            text: msg,
            duration: 5000,
            gravity: "top",
            position: "center",
            backgroundColor: "#28a745",
            close: true
          }).showToast();
          localStorage.removeItem('toastMessage');
        }
    </script>
</body>
</html>
