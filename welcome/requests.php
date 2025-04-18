<?php include '../services/workout-requests.php'?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Workout Request</title>
   <link rel="stylesheet" href="welcome-styles.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
            color:white;
            font-family: "Fredoka";
        }
    .container { max-width: 700px; margin-top: 2rem; }


    </style>
</head>
<body>
<div class="container">
<div class="background"></div>
    
    <!-- Include the sidebar -->

    
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
</html>
