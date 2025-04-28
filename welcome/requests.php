<?php include '../services/workout-requests.php'?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Workout Request</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            color: white;
            font-family: "Fredoka", sans-serif;
        }
        .container { 
            max-width: 700px; 
            margin-top: 2rem; 
        }
        .trainer-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .trainer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        .trainer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .trainer-info {
            flex: 1;
        }
        .trainer-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .btn-select-trainer {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-select-trainer:hover {
            background: #2a5298;
            transform: translateY(-2px);
        }
        .selected-trainer {
            background: rgba(30, 60, 114, 0.3);
            border: 1px solid rgba(30, 60, 114, 0.5);
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include '../welcome/sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
        
        </header>
        
        <div class="back-button mb-3">
            <a href="workouts.php"><i class="fas fa-arrow-left"></i> Back to Workouts</a>
        </div>
        
        <h3>Make a Workout Request</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Describe your fitness goals / workout needs</label>
                <textarea name="notes" class="form-control" rows="5" required><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
            </div>
            
            <?php if ($selected_trainer): ?>
                <div class="mb-3">
                    <label>Selected Trainer</label>
                    <div class="trainer-card selected-trainer">
                        <div class="trainer-avatar">
                            <img src="<?= !empty($selected_trainer['profile_picture']) ? '../trainer/uploads/' . htmlspecialchars($selected_trainer['profile_picture']) : '../register/uploads/default-avatar.jpg' ?>" alt="Trainer">
                        </div>
                        <div class="trainer-info">
                            <div class="trainer-name"><?= htmlspecialchars($selected_trainer['first_name'] . ' ' . $selected_trainer['last_name']) ?></div>
                        </div>
                        <a href="request-plan.php" class="btn-select-trainer">Change</a>
                    </div>
                    <input type="hidden" name="trainer_id" value="<?= $selected_trainer['trainer_id'] ?>">
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <label>Select a trainer</label>
                    <a href="../trainer/trainers.php?context=workout_request" class="btn-select-trainer">
                        <i class="fas fa-user"></i> Browse Trainers
                    </a>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary" <?= !$selected_trainer ? 'disabled' : '' ?>>Send Request</button>
        </form>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
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