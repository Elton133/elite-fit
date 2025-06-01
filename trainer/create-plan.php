<?php include '../services/create-plan-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workout Plan - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .create-plan-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .plan-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .plan-header h2 {
            color: white;
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .plan-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            font-size: 16px;
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #4CAF50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .exercise-container {
            margin-bottom: 20px;
        }
        
        .exercise-group {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .exercise-group:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }
        
        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .exercise-number {
            background: #4CAF50;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .remove-exercise {
            background: #f44336;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .remove-exercise:hover {
            background: #d32f2f;
            transform: scale(1.1);
        }
        
        .exercise-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .exercise-details {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr;
            gap: 15px;
        }
        
        .add-exercise-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .add-exercise-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-primary, .btn-secondary {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .back-button a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            padding: 10px 15px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .back-button a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .no-request {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-request i {
            font-size: 64px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .exercise-grid {
                grid-template-columns: 1fr;
            }
            
            .exercise-details {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .create-plan-container {
                padding: 10px;
            }
        }
        
        .exercise-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255, 255, 255, 0.6);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .exercise-empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include 'trainer-sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">0</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic ?? '../register/uploads/default-avatar.jpg'); ?>" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($trainer_data['first_name'] ?? 'Trainer') ?></h3>
                        <p class="user-status">Fitness Trainer</p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="dashboard">
            <div class="create-plan-container">
                <div class="back-button">
                    <a href="all-requests.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Requests
                    </a>
                </div>
                
                <div class="plan-header">
                    <h2><i class="fas fa-dumbbell"></i> Create Workout Plan</h2>
                    <p>Design a personalized fitness program for <?= htmlspecialchars($user_data['first_name'] ?? 'Member') ?></p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php elseif ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($request_data): ?>
                    <div class="form-container">
                        <form method="POST">
                            <!-- Plan Details Section -->
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    Plan Details
                                </div>
                                
                                <div class="form-group">
                                    <label for="plan_name">Plan Name *</label>
                                    <input type="text" id="plan_name" name="plan_name" class="form-control" placeholder="e.g., Beginner Strength Training" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Describe the goals and approach of this workout plan..."></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="start_date">Start Date *</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date">End Date *</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Exercises Section -->
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-list"></i>
                                    Exercises
                                </div>
                                
                                <div id="exercise-container" class="exercise-container">
                                    <div class="exercise-empty-state">
                                        <i class="fas fa-dumbbell"></i>
                                        <h4>No exercises added yet</h4>
                                        <p>Click the button below to start adding exercises to this workout plan</p>
                                    </div>
                                </div>
                                
                                <button type="button" class="add-exercise-btn" onclick="addExercise()">
                                    <i class="fas fa-plus"></i>
                                    Add Exercise
                                </button>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="create_plan" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Create Plan
                                </button>
                                <a href="all-requests.php" class="btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-request">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3>No Request Found</h3>
                        <p>Unable to find the workout plan request. Please go back and try again.</p>
                        <a href="all-requests.php" class="btn-secondary" style="margin-top: 20px;">
                            <i class="fas fa-arrow-left"></i>
                            Back to Requests
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>

    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script>
        let exerciseCount = 0;

        function addExercise() {
            const container = document.getElementById('exercise-container');
            
            // Remove empty state if it exists
            const emptyState = container.querySelector('.exercise-empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            exerciseCount++;
            
            const exerciseHTML = `
                <div class="exercise-group" data-exercise="${exerciseCount}">
                    <div class="exercise-header">
                        <div class="exercise-number">${exerciseCount}</div>
                        <button type="button" class="remove-exercise" onclick="removeExercise(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="exercise-grid">
                        <div class="form-group">
                            <label>Exercise Name *</label>
                            <input type="text" name="exercises[${exerciseCount}][exercise_name]" class="form-control" placeholder="e.g., Push-ups" required>
                        </div>
                        <div class="form-group">
                            <label>Sets *</label>
                            <input type="text" name="exercises[${exerciseCount}][sets]" class="form-control" placeholder="e.g., 3" required>
                        </div>
                        <div class="form-group">
                            <label>Reps *</label>
                            <input type="text" name="exercises[${exerciseCount}][reps]" class="form-control" placeholder="e.g., 10-12" required>
                        </div>
                    </div>
                    
                    <div class="exercise-details">
                        <div class="form-group">
                            <label>Duration</label>
                            <input type="text" name="exercises[${exerciseCount}][duration]" class="form-control" placeholder="e.g., 30 seconds">
                        </div>
                        <div class="form-group">
                            <label>Day of Week</label>
                            <input type="text" name="exercises[${exerciseCount}][day_of_week]" class="form-control" placeholder="e.g., Monday">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="exercises[${exerciseCount}][notes]" class="form-control" placeholder="Additional instructions...">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', exerciseHTML);
            
            // Scroll to the new exercise
            const newExercise = container.lastElementChild;
            newExercise.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function removeExercise(button) {
            const exerciseGroup = button.closest('.exercise-group');
            exerciseGroup.style.transform = 'translateX(-100%)';
            exerciseGroup.style.opacity = '0';
            
            setTimeout(() => {
                exerciseGroup.remove();
                updateExerciseNumbers();
                
                // Show empty state if no exercises left
                const container = document.getElementById('exercise-container');
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="exercise-empty-state">
                            <i class="fas fa-dumbbell"></i>
                            <h4>No exercises added yet</h4>
                            <p>Click the button below to start adding exercises to this workout plan</p>
                        </div>
                    `;
                }
            }, 300);
        }

        function updateExerciseNumbers() {
            const exercises = document.querySelectorAll('.exercise-group');
            exercises.forEach((exercise, index) => {
                const numberElement = exercise.querySelector('.exercise-number');
                if (numberElement) {
                    numberElement.textContent = index + 1;
                }
            });
        }

        // Set default dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            
            if (startDate) {
                startDate.value = today.toISOString().split('T')[0];
            }
            
            if (endDate) {
                const futureDate = new Date(today);
                futureDate.setMonth(futureDate.getMonth() + 3); // 3 months from now
                endDate.value = futureDate.toISOString().split('T')[0];
            }
        });

        // Toast message handling
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
