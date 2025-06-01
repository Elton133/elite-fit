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
            min-height: 100vh;
        }

        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: -1;
        }

        .container { 
            max-width: 800px; 
            margin-top: 2rem;
            padding: 0 20px;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .back-button {
            margin-bottom: 30px;
        }

        .back-button a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .back-button a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            padding: 16px 20px;
            font-size: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .trainer-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .trainer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .trainer-card:hover::before {
            opacity: 1;
        }

        .trainer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .trainer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .trainer-card:hover .trainer-avatar img {
            transform: scale(1.05);
        }

        .trainer-info {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .trainer-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }

        .trainer-badge {
            display: inline-block;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-select-trainer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }

        .btn-select-trainer:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .selected-trainer {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
            border: 2px solid rgba(102, 126, 234, 0.4);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.2);
        }

        .selected-trainer::after {
            content: '✓';
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
            width: 100%;
            margin-top: 20px;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .btn-primary:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
            box-shadow: none;
        }

        .browse-trainers-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .browse-trainers-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .browse-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.6);
        }

        .alert {
            border-radius: 16px;
            border: none;
            padding: 20px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b7a;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #4ade80;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 24px;
                margin: 16px;
            }

            .page-title {
                font-size: 2rem;
                margin-bottom: 30px;
            }

            .trainer-card {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .trainer-avatar {
                margin-right: 0;
                margin-bottom: 16px;
            }

            .container {
                padding: 0 16px;
            }
        }

        /* Animation for form appearance */
        .form-section {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .trainer-card {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
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
        
        <div class="back-button">
            <a href="workouts.php"><i class="fas fa-arrow-left"></i> Back to Workouts</a>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Create Workout Request</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error ?>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-section">
                    <label class="form-label">
                        <i class="fas fa-bullseye me-2"></i>
                        Describe your fitness goals & workout needs
                    </label>
                    <textarea 
                        name="notes" 
                        class="form-control" 
                        rows="6" 
                        required
                        placeholder="Tell us about your fitness goals, preferred workout style, any limitations, and what you hope to achieve..."
                    ><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                </div>
                
                <?php if ($selected_trainer): ?>
                    <div class="form-section">
                        <label class="form-label">
                            <i class="fas fa-user-check me-2"></i>
                            Selected Trainer
                        </label>
                        <div class="trainer-card selected-trainer">
                            <div class="trainer-avatar">
                                <img src="<?= !empty($selected_trainer['profile_picture']) ? '../trainer/uploads/' . htmlspecialchars($selected_trainer['profile_picture']) : '../register/uploads/default-avatar.jpg' ?>" alt="Trainer">
                            </div>
                            <div class="trainer-info">
                                <div class="trainer-name"><?= htmlspecialchars($selected_trainer['first_name'] . ' ' . $selected_trainer['last_name']) ?></div>
                                <span class="trainer-badge">Selected Trainer</span>
                            </div>
                            <a href="../trainer/trainers.php?context=workout_request" class="btn-select-trainer">
                                <i class="fas fa-exchange-alt"></i>
                                Change Trainer
                            </a>
                        </div>
                        <input type="hidden" name="trainer_id" value="<?= $selected_trainer['trainer_id'] ?>">
                    </div>
                <?php else: ?>
                    <div class="form-section">
                        <label class="form-label">
                            <i class="fas fa-users me-2"></i>
                            Select a trainer
                        </label>
                        <div class="browse-trainers-card">
                            <div class="browse-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4 style="margin-bottom: 16px; color: rgba(255, 255, 255, 0.9);">Choose Your Perfect Trainer</h4>
                            <p style="margin-bottom: 24px; color: rgba(255, 255, 255, 0.7);">Browse our certified trainers and find the perfect match for your fitness journey</p>
                            <a href="../trainer/trainers.php?context=workout_request" class="btn-select-trainer">
                                <i class="fas fa-search"></i>
                                Browse Trainers
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary" <?= !$selected_trainer ? 'disabled' : '' ?>>
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Workout Request
                </button>
            </form>
        </div>
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
                backgroundColor: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
                close: true,
                style: {
                    borderRadius: "12px",
                    fontWeight: "600"
                }
            }).showToast();
            localStorage.removeItem('toastMessage');
        }

        // Add subtle parallax effect to background
        document.addEventListener('mousemove', (e) => {
            const background = document.querySelector('.background');
            const x = (e.clientX / window.innerWidth) * 10;
            const y = (e.clientY / window.innerHeight) * 10;
            background.style.transform = `translate(${x}px, ${y}px)`;
        });

        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-primary');
            if (!submitBtn.disabled) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending Request...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>