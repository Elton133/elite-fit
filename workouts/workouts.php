<?php include '../services/workouts-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workouts - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="workout-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Workout Styles */
:root {
  --primary-color: #1e3c72;
  --primary-hover: #2a5298;
  --secondary-color: rgba(255, 255, 255, 0.2);
  --text-color: #fff;
  --text-muted: rgba(255, 255, 255, 0.7);
  --text-light: rgba(255, 255, 255, 0.8);
  --text-lighter: rgba(255, 255, 255, 0.6);
  --border-color: rgba(255, 255, 255, 0.1);
  --card-bg: rgba(255, 255, 255, 0.2);
  --success-color: #28a745;
  --success-hover: #218838;
  --danger-color: #e74c3c;
  --warning-color: #f39c12;
  --shadow-sm: 0 4px 15px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 8px 32px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.15);
  --border-radius-sm: 10px;
  --border-radius-md: 15px;
  --border-radius-lg: 50px;
  --border-radius-circle: 50%;
  --transition-fast: 0.2s ease;
  --transition-normal: 0.3s ease;
}

/* Workout Plans Grid */
.workouts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.plan-card-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.plan-card {
  background: rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-md);
  padding: 20px;
  transition: all var(--transition-normal);
  height: 100%;
  border: 1px solid var(--border-color);
}

.plan-card:hover {
  transform: translateY(-5px);
  background: rgba(255, 255, 255, 0.15);
  box-shadow: var(--shadow-md);
  border-color: rgba(255, 255, 255, 0.2);
}

.plan-card h3 {
  font-size: 18px;
  margin-bottom: 10px;
  color: var(--text-color);
}

.plan-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.plan-status {
  padding: 4px 10px;
  border-radius: var(--border-radius-lg);
  font-size: 12px;
  font-weight: 500;
}

.status-active {
  background-color: rgba(40, 167, 69, 0.2);
  color: #4cd964;
}

.status-completed {
  background-color: rgba(108, 117, 125, 0.2);
  color: #d1d1d1;
}

.plan-dates {
  font-size: 12px;
  color: var(--text-lighter);
}

.plan-description {
  margin-bottom: 15px;
  font-size: 14px;
  color: var(--text-muted);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.plan-stats {
  display: flex;
  gap: 15px;
}

.plan-stats .stat-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-lighter);
}

.no-workouts {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 0;
  text-align: center;
}

.no-workouts p {
  margin-bottom: 20px;
  color: var(--text-muted);
}

/* Workout Detail Page */
.workout-header {
  margin-bottom: 30px;
}

.back-button {
  margin-bottom: 15px;
}

.back-button a {
  color: var(--text-muted);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: color var(--transition-normal);
}

.back-button a:hover {
  color: var(--text-color);
}

.workout-header h1 {
  font-size: 28px;
  margin-bottom: 10px;
}

.workout-meta {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.workout-duration {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 14px;
  color: var(--text-muted);
}

.workout-description {
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 20px;
}

/* Exercise List */
.exercises-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.exercise-item {
  display: flex;
  align-items: center;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--border-radius-sm);
  transition: all var(--transition-normal);
  border: 1px solid transparent;
}

.exercise-item:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--border-color);
}

.exercise-item.current {
  background: rgba(30, 60, 114, 0.2);
  border-color: rgba(30, 60, 114, 0.4);
  box-shadow: 0 0 15px rgba(30, 60, 114, 0.2);
}

.exercise-number {
  width: 30px;
  height: 30px;
  border-radius: var(--border-radius-circle);
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-right: 15px;
}

.exercise-details {
  flex: 1;
}

.exercise-details h4 {
  font-size: 16px;
  margin-bottom: 5px;
  color: var(--text-color);
}

.exercise-details p {
  font-size: 14px;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.exercise-meta {
  display: flex;
  gap: 15px;
  font-size: 13px;
  color: var(--text-lighter);
}

.exercise-meta span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.exercise-status {
  margin-left: 15px;
}

.status-indicator {
  color: var(--text-lighter);
  font-size: 14px;
}

.status-indicator.completed {
  color: var(--success-color);
}

/* Workout Timer Section */
.workout-timer-section {
  background: rgba(30, 60, 114, 0.3);
  border-radius: var(--border-radius-md);
  padding: 20px;
  margin-bottom: 30px;
  border: 1px solid rgba(30, 60, 114, 0.5);
  box-shadow: var(--shadow-md);
}

.timer-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.timer-display {
  font-size: 48px;
  font-weight: 700;
  color: var(--text-color);
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.timer-controls {
  display: flex;
  gap: 15px;
}

.timer-btn {
  padding: 10px 20px;
  border-radius: var(--border-radius-lg);
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: var(--text-color);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-normal);
  display: flex;
  align-items: center;
  gap: 8px;
}

.timer-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.timer-btn.complete-btn {
  background: var(--success-color);
}

.timer-btn.complete-btn:hover {
  background: var(--success-hover);
}

.current-exercise {
  text-align: center;
  margin-top: 10px;
}

.current-exercise h3 {
  font-size: 16px;
  margin-bottom: 5px;
  color: var(--text-light);
}

.exercise-timer {
  font-size: 24px;
  font-weight: 600;
  color: var(--text-color);
}

/* Progress Page Styles */
.chart-container {
  height: 300px;
  width: 100%;
  position: relative;
}

.chart-controls {
  display: flex;
  gap: 10px;
}

.chart-btn {
  padding: 5px 10px;
  border-radius: var(--border-radius-lg);
  border: none;
  background: rgba(255, 255, 255, 0.1);
  color: var(--text-muted);
  font-size: 12px;
  cursor: pointer;
  transition: all var(--transition-normal);
}

.chart-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.chart-btn.active {
  background: var(--primary-color);
  color: var(--text-color);
}

/* History List */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.history-item {
  display: flex;
  align-items: center;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--border-radius-sm);
  transition: all var(--transition-normal);
  border: 1px solid transparent;
}

.history-item:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--border-color);
  transform: translateY(-3px);
}

.history-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 60px;
  margin-right: 15px;
}

.history-date .day {
  font-size: 24px;
  font-weight: 700;
  line-height: 1;
}

.history-date .month {
  font-size: 14px;
  color: var(--text-muted);
}

.history-details {
  flex: 1;
}

.history-details h4 {
  font-size: 16px;
  margin-bottom: 5px;
}

.history-details p {
  font-size: 14px;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.history-meta {
  display: flex;
  gap: 15px;
  font-size: 13px;
  color: var(--text-lighter);
}

.history-action {
  color: var(--text-muted);
  margin-left: 15px;
  transition: color var(--transition-normal);
}

.history-action:hover {
  color: var(--text-color);
}

/* Modal Styles */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(5px);
}

.modal-content {
  background: #272727;
  margin: 10% auto;
  padding: 30px;
  border-radius: var(--border-radius-md);
  max-width: 500px;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border-color);
  position: relative;
}

.close {
  position: absolute;
  right: 20px;
  top: 15px;
  font-size: 28px;
  font-weight: bold;
  color: var(--text-muted);
  cursor: pointer;
}

.close:hover {
  color: var(--text-color);
}

.modal-content h2 {
  margin-bottom: 20px;
  font-size: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: var(--text-light);
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px 15px;
  border-radius: var(--border-radius-sm);
  border: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-color);
  font-size: 16px;
  transition: all var(--transition-normal);
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  background: rgba(255, 255, 255, 0.1);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 30px;
}

/* Responsive Styles */
@media (max-width: 768px) {
  .workouts-grid {
    grid-template-columns: 1fr;
  }
  
  .timer-display {
    font-size: 36px;
  }
  
  .timer-controls {
    flex-direction: column;
    width: 100%;
  }
  
  .timer-btn {
    width: 100%;
    justify-content: center;
  }
  
  .exercise-item {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .exercise-number {
    margin-bottom: 10px;
  }
  
  .exercise-status {
    margin-left: 0;
    margin-top: 10px;
    align-self: flex-end;
  }
  
  .exercise-meta {
    flex-wrap: wrap;
  }
  
  .chart-controls {
    flex-wrap: wrap;
  }
}

@media (max-width: 480px) {
  .workout-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .history-item {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .history-date {
    flex-direction: row;
    gap: 5px;
    margin-bottom: 10px;
  }
  
  .history-action {
    margin-left: 0;
    margin-top: 10px;
    align-self: flex-end;
  }
}
    </style>
</head>
<body>
    <div class="background"></div>
    <?php include '../welcome/sidebar.php'; ?>

    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h3>
                        <p class="user-status">Member</p>
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

        <div class="welcome-banner">
            <div class="welcome-text">
                <h2><?php echo $greeting . ', ' . htmlspecialchars($user_data['first_name']); ?>!</h2>
                <p>Your workout plans and exercises</p>
            </div>
            <div class="quick-actions">
                <a href="progress.php" class="action-btn"><i class="fas fa-chart-line"></i> View Progress</a>
                <a href="request-plan.php" class="action-btn secondary"><i class="fas fa-plus"></i> Request New Plan</a>
            </div>
        </div>

        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Workout Plans</h3>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($plans)): ?>
                            <div class="workouts-grid">
                                <?php foreach ($plans as $plan): ?>
                                    <a href="workout-detail.php?plan_id=<?php echo $plan['plan_id']; ?>" class="plan-card-link">
                                        <div class="plan-card">
                                            <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                            <div class="plan-meta">
                                                <span class="plan-status <?php echo strtolower($plan['status']) === 'active' ? 'status-active' : 'status-completed'; ?>">
                                                    <?php echo htmlspecialchars($plan['status']); ?>
                                                </span>
                                                <span class="plan-dates">
                                                    <?php echo date('M j, Y', strtotime($plan['start_date'])); ?> -
                                                    <?php echo $plan['end_date'] ? date('M j, Y', strtotime($plan['end_date'])) : 'Ongoing'; ?>
                                                </span>
                                            </div>
                                            <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                                            <div class="plan-stats">
                                                <div class="stat-item">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <span><?php echo count(array_filter($exercises, fn($ex) => $ex['plan_id'] == $plan['plan_id'])); ?> Exercises</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-workouts">
                                <i class="fas fa-dumbbell" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>You don't have any workout plans yet!</p>
                                <a href="request-plan.php" class="action-btn" style="margin-top: 15px; display: inline-block;">
                                    <i class="fas fa-plus"></i> Request a Plan
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../welcome/sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
</body>
</html>