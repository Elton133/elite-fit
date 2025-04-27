<?php
session_start();
require_once('../datacon.php');

if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: ../login/index.php");
    exit();
}

$email = $_SESSION['email'];
$table_id = $_SESSION['table_id'];

// Fetch user data
$sql_user = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$stmt_user->close();

// Fetch workout history (completed sessions)
$sql_history = "SELECT ws.*, wp.plan_name, wp.description
               FROM workout_sessions ws
               JOIN workout_plans wp ON ws.plan_id = wp.plan_id
               WHERE ws.user_id = ? AND ws.status = 'completed'
               ORDER BY ws.end_time DESC
               LIMIT 10";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $table_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$workout_history = $result_history->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();

// Fetch user progress metrics
$sql_metrics = "SELECT * FROM user_progress_metrics 
               WHERE user_id = ? 
               ORDER BY measurement_date DESC 
               LIMIT 12";
$stmt_metrics = $conn->prepare($sql_metrics);
$stmt_metrics->bind_param("i", $table_id);
$stmt_metrics->execute();
$result_metrics = $stmt_metrics->get_result();
$progress_metrics = $result_metrics->fetch_all(MYSQLI_ASSOC);
$stmt_metrics->close();

// Calculate workout statistics
$sql_stats = "SELECT 
              COUNT(*) as total_workouts,
              SUM(total_duration) as total_duration,
              AVG(total_duration) as avg_duration,
              MAX(total_duration) as max_duration
              FROM workout_sessions
              WHERE user_id = ? AND status = 'completed'";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $table_id);
$stmt_stats->execute();
$workout_stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Format stats for display
$total_workouts = $workout_stats['total_workouts'] ?? 0;
$total_duration = $workout_stats['total_duration'] ?? 0;
$avg_duration = $workout_stats['avg_duration'] ?? 0;
$max_duration = $workout_stats['max_duration'] ?? 0;

// Convert seconds to hours and minutes for display
$total_hours = floor($total_duration / 3600);
$total_minutes = floor(($total_duration % 3600) / 60);
$avg_minutes = floor($avg_duration / 60);
$max_minutes = floor($max_duration / 60);

$profile_pic = "../register/uploads/default-avatar.jpg";
if (!empty($user_data['profile_picture']) && file_exists("../register/uploads/" . $user_data['profile_picture'])) {
    $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
}

$hour = date("H");
$greeting = ($hour >= 5 && $hour < 12) ? "Good morning" : (($hour < 17) ? "Good afternoon" : (($hour < 21) ? "Good evening" : "Good night"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="workout-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                <p>Track your fitness journey and progress</p>
            </div>
            <div class="quick-actions">
                <a href="workouts.php" class="action-btn"><i class="fas fa-dumbbell"></i> My Workouts</a>
                <button id="recordProgressBtn" class="action-btn secondary"><i class="fas fa-weight"></i> Record Progress</button>
            </div>
        </div>

        <div class="dashboard">
            <div class="dashboard-row">
                <!-- Workout Statistics Card -->
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Workout Statistics</h3>
                    </div>
                    <div class="card-content">
                        <div class="stat-item">
                            <div class="stat-icon bmi-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Total Workouts</h4>
                                <p class="stat-value"><?php echo $total_workouts; ?></p>
                                <p class="stat-label">Completed sessions</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon weight-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Total Time</h4>
                                <p class="stat-value"><?php echo $total_hours; ?>h <?php echo $total_minutes; ?>m</p>
                                <p class="stat-label">Time spent working out</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon exp-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Average Session</h4>
                                <p class="stat-value"><?php echo $avg_minutes; ?> min</p>
                                <p class="stat-label">Per workout</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Chart Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Progress Metrics</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-metric="weight">Weight</button>
                            <button class="chart-btn" data-metric="body_fat">Body Fat</button>
                            <button class="chart-btn" data-metric="muscle_mass">Muscle Mass</button>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="chart-container">
                            <canvas id="progressChart"></canvas>
                        </div>
                        <?php if (empty($progress_metrics)): ?>
                            <div class="no-data">
                                <p>No progress data recorded yet.</p>
                                <button id="recordProgressBtnAlt" class="action-btn" style="margin-top: 15px;">
                                    <i class="fas fa-plus"></i> Record First Measurement
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <!-- Workout History Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Workout History</h3>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($workout_history)): ?>
                            <div class="history-list">
                                <?php foreach ($workout_history as $workout): ?>
                                    <div class="history-item">
                                        <div class="history-date">
                                            <span class="day"><?php echo date('d', strtotime($workout['end_time'])); ?></span>
                                            <span class="month"><?php echo date('M', strtotime($workout['end_time'])); ?></span>
                                        </div>
                                        <div class="history-details">
                                            <h4><?php echo htmlspecialchars($workout['plan_name']); ?></h4>
                                            <p><?php echo date('h:i A', strtotime($workout['start_time'])); ?> - <?php echo date('h:i A', strtotime($workout['end_time'])); ?></p>
                                            <div class="history-meta">
                                                <span><i class="fas fa-clock"></i> <?php echo floor($workout['total_duration'] / 60); ?> min</span>
                                            </div>
                                        </div>
                                        <a href="workout-detail.php?plan_id=<?php echo $workout['plan_id']; ?>" class="history-action">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-history" style="font-size: 48px; margin-bottom: 15px; color: rgba(255,255,255,0.3);"></i>
                                <p>No workout history yet.</p>
                                <a href="workouts.php" class="action-btn" style="margin-top: 15px; display: inline-block;">
                                    <i class="fas fa-dumbbell"></i> Start a Workout
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Recording Modal -->
        <div id="progressModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Record Progress</h2>
                <form id="progressForm" action="save-progress.php" method="post">
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" id="weight" name="weight" step="0.1" min="30" max="300">
                    </div>
                    <div class="form-group">
                        <label for="body_fat">Body Fat (%)</label>
                        <input type="number" id="body_fat" name="body_fat" step="0.1" min="3" max="50">
                    </div>
                    <div class="form-group">
                        <label for="muscle_mass">Muscle Mass (kg)</label>
                        <input type="number" id="muscle_mass" name="muscle_mass" step="0.1" min="10" max="100">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="action-btn">Save Progress</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../welcome/sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Progress chart initialization
            const progressData = <?php echo json_encode($progress_metrics); ?>;
            const ctx = document.getElementById('progressChart').getContext('2d');
            let activeMetric = 'weight';
            let progressChart;
            
            function initChart() {
                if (progressData.length === 0) return;
                
                const labels = progressData.map(item => {
                    const date = new Date(item.measurement_date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }).reverse();
                
                const data = progressData.map(item => parseFloat(item[activeMetric])).reverse();
                
                const chartConfig = {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: getMetricLabel(activeMetric),
                            data: data,
                            backgroundColor: 'rgba(79, 172, 254, 0.2)',
                            borderColor: 'rgba(79, 172, 254, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(79, 172, 254, 1)',
                            pointBorderColor: '#fff',
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 14
                                },
                                padding: 10,
                                displayColors: false
                            }
                        }
                    }
                };
                
                if (progressChart) {
                    progressChart.destroy();
                }
                
                progressChart = new Chart(ctx, chartConfig);
            }
            
            function getMetricLabel(metric) {
                switch(metric) {
                    case 'weight': return 'Weight (kg)';
                    case 'body_fat': return 'Body Fat (%)';
                    case 'muscle_mass': return 'Muscle Mass (kg)';
                    default: return '';
                }
            }
            
            // Initialize chart
            initChart();
            
            // Chart metric switcher
            const chartBtns = document.querySelectorAll('.chart-btn');
            chartBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    chartBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    activeMetric = this.dataset.metric;
                    initChart();
                });
            });
            
            // Progress modal functionality
            const modal = document.getElementById('progressModal');
            const recordBtn = document.getElementById('recordProgressBtn');
            const recordBtnAlt = document.getElementById('recordProgressBtnAlt');
            const closeBtn = document.querySelector('.close');
            
            function openModal() {
                modal.style.display = 'block';
            }
            
            function closeModal() {
                modal.style.display = 'none';
            }
            
            if (recordBtn) {
                recordBtn.addEventListener('click', openModal);
            }
            
            if (recordBtnAlt) {
                recordBtnAlt.addEventListener('click', openModal);
            }
            
            closeBtn.addEventListener('click', closeModal);
            
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>