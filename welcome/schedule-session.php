<?php include '../services/schedule-session-logic.php'?>
<?php include '../services/welcome-logic.php'?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule a Session - EliteFit Gym</title>
    <link rel="stylesheet" href="welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="schedule-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <!-- User profile content from your existing code -->
                </div>
            </div>
        </header>
        
        <div class="page-title">
            <h2><i class="fas fa-calendar-alt"></i> Schedule a Training Session</h2>
            <p>Book your personalized training session with <?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="schedule-container">
            <div class="trainer-info-card">
                <?php 
                $profile_pic = "../register/uploads/default-trainer.jpg"; 
                if (!empty($trainer['profile_picture']) && file_exists("../register/uploads/" . $trainer['profile_picture'])) {
                    $profile_pic = "../register/uploads/" . $trainer['profile_picture'];
                }
                ?>
                <div class="trainer-image">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>">
                </div>
                <div class="trainer-details">
                    <h3><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h3>
                    <p class="trainer-specialization"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                </div>
                
                <div class="trainer-availability">
                    <h4>Weekly Availability</h4>
                    <?php if (count($availabilities) > 0): ?>
                        <ul class="availability-list">
                            <?php foreach ($availabilities as $availability): ?>
                                <li>
                                    <span class="day"><?php echo htmlspecialchars($availability['day_of_week']); ?></span>
                                    <span class="time"><?php 
                                        echo date('g:i A', strtotime($availability['start_time'])) . ' - ' . 
                                             date('g:i A', strtotime($availability['end_time'])); 
                                    ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-availability">No availability information found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="schedule-form-container">
            <form action="../welcome/schedule-session.php?trainer_id=<?php echo $trainer_id; ?>" method="post" class="schedule-form">

                    <div class="form-group">
                        <label for="session_date">Session Date <span class="required">*</span></label>
                        <input type="date" id="session_date" name="session_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time <span class="required">*</span></label>
                            <input type="time" id="start_time" name="start_time" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time">End Time <span class="required">*</span></label>
                            <input type="time" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_type">Session Type <span class="required">*</span></label>
                        <select id="session_type" name="session_type" required>
                            <option value="">Select a session type</option>
                            <option value="Personal Training">Personal Training</option>
                            <option value="Strength Training">Strength Training</option>
                            <option value="Cardio Workout">Cardio Workout</option>
                            <option value="Flexibility & Mobility">Flexibility & Mobility</option>
                            <option value="Nutrition Consultation">Nutrition Consultation</option>
                            <option value="Fitness Assessment">Fitness Assessment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Any specific goals or concerns for this session?"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="trainers.php" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn-schedule">Schedule Session</button>
                    </div>
                </form>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script>
        
        // Form validation
        document.querySelector('.schedule-form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time.');
            }
        });
    </script>
</body>
</html>