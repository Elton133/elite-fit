<?php
session_start();
include_once "../datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's meal plans
$stmt = $conn->prepare("SELECT * FROM meal_plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meal_plans = $stmt->get_result();

// Get user's nutrition goals
$stmt = $conn->prepare("SELECT * FROM nutrition_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$nutrition_goals = $stmt->get_result()->fetch_assoc();

// Get user's recent food logs
$stmt = $conn->prepare("SELECT * FROM food_logs WHERE user_id = ? ORDER BY log_date DESC, meal_time ASC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$food_logs = $stmt->get_result();

// Calculate daily nutrition totals if logs exist
$daily_totals = [];
if ($food_logs->num_rows > 0) {
    // Reset result pointer
    $food_logs->data_seek(0);
    
    while ($log = $food_logs->fetch_assoc()) {
        $date = $log['log_date'];
        if (!isset($daily_totals[$date])) {
            $daily_totals[$date] = [
                'calories' => 0,
                'protein' => 0,
                'carbs' => 0,
                'fat' => 0
            ];
        }
        
        $daily_totals[$date]['calories'] += $log['calories'];
        $daily_totals[$date]['protein'] += $log['protein'];
        $daily_totals[$date]['carbs'] += $log['carbs'];
        $daily_totals[$date]['fat'] += $log['fat'];
    }
    
    // Reset result pointer again for display
    $food_logs->data_seek(0);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nutrition - EliteFit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .nutrition-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .action-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .nutrition-dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .nutrition-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: 100%;
        }
        
        .nutrition-card-header {
            background: rgba(30, 60, 114, 0.3);
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nutrition-card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nutrition-card-body {
            padding: 20px;
        }
        
        .nutrition-goals {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .goal-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .goal-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }
        
        .goal-value {
            font-size: 24px;
            font-weight: 600;
        }
        
        .goal-progress {
            margin-top: 10px;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .progress-calories {
            background: linear-gradient(90deg, #ff9966, #ff5e62);
        }
        
        .progress-protein {
            background: linear-gradient(90deg, #4facfe, #00f2fe);
        }
        
        .progress-carbs {
            background: linear-gradient(90deg, #43e97b, #38f9d7);
        }
        
        .progress-fat {
            background: linear-gradient(90deg, #f6d365, #fda085);
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .meal-plan-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .meal-plan-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .meal-plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .meal-plan-header {
            background: rgba(30, 60, 114, 0.3);
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .meal-plan-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .meal-plan-body {
            padding: 15px;
        }
        
        .meal-plan-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .meal-plan-description {
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .meal-plan-stats {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .meal-stat {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 8px;
            flex: 1;
            text-align: center;
            font-size: 12px;
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .meal-plan-footer {
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
        }
        
        .food-log-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .food-log-table th {
            text-align: left;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        
        .food-log-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .food-log-table tr:last-child td {
            border-bottom: none;
        }
        
        .food-log-date {
            background: rgba(30, 60, 114, 0.2);
            padding: 8px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .meal-time {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .meal-breakfast {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .meal-lunch {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .meal-dinner {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .meal-snack {
            background: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }
        
        .empty-state {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .empty-state-text {
            font-size: 18px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            position: relative;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: white;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 992px) {
            .nutrition-dashboard {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .nutrition-goals {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
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
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="../register/uploads/default-avatar.jpg" alt="User Profile">
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="nutrition-container">
            <div class="tabs">
                <div class="tab active" data-tab="dashboard">Dashboard</div>
                <div class="tab" data-tab="meal-plans">Meal Plans</div>
                <div class="tab" data-tab="food-log">Food Log</div>
            </div>
            
            <!-- Dashboard Tab -->
            <div class="tab-content active" id="dashboard">
                <div class="section-header">
                    <h2 class="section-title">Nutrition Dashboard</h2>
                    <div>
                        <a href="log-food.php" class="action-btn">
                            <i class="fas fa-plus"></i> Log Food
                        </a>
                    </div>
                </div>
                
                <div class="nutrition-dashboard">
                    <div class="nutrition-card">
                        <div class="nutrition-card-header">
                            <h3 class="nutrition-card-title">
                                <i class="fas fa-bullseye"></i> Nutrition Goals
                            </h3>
                        </div>
                        <div class="nutrition-card-body">
                            <?php if ($nutrition_goals): ?>
                                <div class="nutrition-goals">
                                    <div class="goal-item">
                                        <div class="goal-label">Daily Calories</div>
                                        <div class="goal-value"><?= number_format($nutrition_goals['calories']) ?></div>
                                        <?php if (isset($daily_totals[date('Y-m-d')])): ?>
                                            <div class="goal-progress">
                                                <?php 
                                                $calories_percent = min(100, ($daily_totals[date('Y-m-d')]['calories'] / $nutrition_goals['calories']) * 100);
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill progress-calories" style="width: <?= $calories_percent ?>%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <span><?= number_format($daily_totals[date('Y-m-d')]['calories']) ?> / <?= number_format($nutrition_goals['calories']) ?></span>
                                                    <span><?= number_format($calories_percent) ?>%</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="goal-item">
                                        <div class="goal-label">Protein (g)</div>
                                        <div class="goal-value"><?= number_format($nutrition_goals['protein']) ?></div>
                                        <?php if (isset($daily_totals[date('Y-m-d')])): ?>
                                            <div class="goal-progress">
                                                <?php 
                                                $protein_percent = min(100, ($daily_totals[date('Y-m-d')]['protein'] / $nutrition_goals['protein']) * 100);
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill progress-protein" style="width: <?= $protein_percent ?>%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <span><?= number_format($daily_totals[date('Y-m-d')]['protein']) ?> / <?= number_format($nutrition_goals['protein']) ?></span>
                                                    <span><?= number_format($protein_percent) ?>%</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="goal-item">
                                        <div class="goal-label">Carbohydrates (g)</div>
                                        <div class="goal-value"><?= number_format($nutrition_goals['carbs']) ?></div>
                                        <?php if (isset($daily_totals[date('Y-m-d')])): ?>
                                            <div class="goal-progress">
                                                <?php 
                                                $carbs_percent = min(100, ($daily_totals[date('Y-m-d')]['carbs'] / $nutrition_goals['carbs']) * 100);
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill progress-carbs" style="width: <?= $carbs_percent ?>%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <span><?= number_format($daily_totals[date('Y-m-d')]['carbs']) ?> / <?= number_format($nutrition_goals['carbs']) ?></span>
                                                    <span><?= number_format($carbs_percent) ?>%</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="goal-item">
                                        <div class="goal-label">Fats (g)</div>
                                        <div class="goal-value"><?= number_format($nutrition_goals['fat']) ?></div>
                                        <?php if (isset($daily_totals[date('Y-m-d')])): ?>
                                            <div class="goal-progress">
                                                <?php 
                                                $fat_percent = min(100, ($daily_totals[date('Y-m-d')]['fat'] / $nutrition_goals['fat']) * 100);
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill progress-fat" style="width: <?= $fat_percent ?>%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <span><?= number_format($daily_totals[date('Y-m-d')]['fat']) ?> / <?= number_format($nutrition_goals['fat']) ?></span>
                                                    <span><?= number_format($fat_percent) ?>%</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="empty-state-text">
                                        You haven't set any nutrition goals yet.
                                    </div>
                                    <a href="set-goals.php" class="action-btn">
                                        <i class="fas fa-plus"></i> Set Nutrition Goals
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="nutrition-card">
                        <div class="nutrition-card-header">
                            <h3 class="nutrition-card-title">
                                <i class="fas fa-utensils"></i> Today's Meals
                            </h3>
                        </div>
                        <div class="nutrition-card-body">
                            <?php
                            $today = date('Y-m-d');
                            $today_logs = false;
                            
                            if ($food_logs->num_rows > 0) {
                                $food_logs->data_seek(0);
                                while ($log = $food_logs->fetch_assoc()) {
                                    if ($log['log_date'] == $today) {
                                        $today_logs = true;
                                        ?>
                                        <div style="margin-bottom: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                                <div>
                                                    <span class="meal-time meal-<?= strtolower($log['meal_time']) ?>"><?= $log['meal_time'] ?></span>
                                                </div>
                                                <div style="font-size: 14px; color: rgba(255, 255, 255, 0.7);">
                                                    <?= $log['calories'] ?> cal
                                                </div>
                                            </div>
                                            <div style="font-weight: 500;"><?= htmlspecialchars($log['food_name']) ?></div>
                                            <div style="font-size: 14px; color: rgba(255, 255, 255, 0.7);">
                                                <?= $log['serving_size'] ?> - P: <?= $log['protein'] ?>g | C: <?= $log['carbs'] ?>g | F: <?= $log['fat'] ?>g
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                $food_logs->data_seek(0);
                            }
                            
                            if (!$today_logs) {
                                ?>
                                <div style="text-align: center; padding: 20px 0;">
                                    <div style="font-size: 18px; margin-bottom: 15px;">No meals logged today</div>
                                    <a href="log-food.php" class="action-btn">
                                        <i class="fas fa-plus"></i> Log Food
                                    </a>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="section-header">
                    <h3 class="section-title">Recent Meal Plans</h3>
                    <a href="create-meal-plan.php" class="action-btn secondary">
                        <i class="fas fa-plus"></i> Create Meal Plan
                    </a>
                </div>
                
                <?php if ($meal_plans->num_rows > 0): ?>
                    <div class="meal-plan-list">
                        <?php while ($plan = $meal_plans->fetch_assoc()): ?>
                            <div class="meal-plan-card">
                                <div class="meal-plan-header">
                                    <h3 class="meal-plan-title"><?= htmlspecialchars($plan['title']) ?></h3>
                                </div>
                                <div class="meal-plan-body">
                                    <div class="meal-plan-meta">
                                        <div>Created: <?= date('M d, Y', strtotime($plan['created_at'])) ?></div>
                                        <div><?= $plan['days'] ?> days</div>
                                    </div>
                                    
                                    <div class="meal-plan-description">
                                        <?= nl2br(htmlspecialchars(substr($plan['description'], 0, 100) . (strlen($plan['description']) > 100 ? '...' : ''))) ?>
                                    </div>
                                    
                                    <div class="meal-plan-stats">
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_calories']) ?></div>
                                            <div>Calories</div>
                                        </div>
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_protein']) ?>g</div>
                                            <div>Protein</div>
                                        </div>
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_carbs']) ?>g</div>
                                            <div>Carbs</div>
                                        </div>
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_fat']) ?>g</div>
                                            <div>Fat</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="meal-plan-footer">
                                    <a href="view-meal-plan.php?id=<?= $plan['id'] ?>" class="action-btn secondary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="empty-state-text">
                            You haven't created any meal plans yet.
                        </div>
                        <a href="create-meal-plan.php" class="action-btn">
                            <i class="fas fa-plus"></i> Create Meal Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Meal Plans Tab -->
            <div class="tab-content" id="meal-plans">
                <div class="section-header">
                    <h2 class="section-title">My Meal Plans</h2>
                    <a href="create-meal-plan.php" class="action-btn">
                        <i class="fas fa-plus"></i> Create Meal Plan
                    </a>
                </div>
                
                <?php if ($meal_plans->num_rows > 0): ?>
                    <?php 
                    // Reset result pointer
                    $meal_plans->data_seek(0);
                    ?>
                    <div class="meal-plan-list">
                        <?php while ($plan = $meal_plans->fetch_assoc()): ?>
                            <div class="meal-plan-card">
                                <div class="meal-plan-header">
                                    <h3 class="meal-plan-title"><?= htmlspecialchars($plan['title']) ?></h3>
                                </div>
                                <div class="meal-plan-body">
                                    <div class="meal-plan-meta">
                                        <div>Created: <?= date('M d, Y', strtotime($plan['created_at'])) ?></div>
                                        <div><?= $plan['days'] ?> days</div>
                                    </div>
                                    
                                    <div class="meal-plan-description">
                                        <?= nl2br(htmlspecialchars($plan['description'])) ?>
                                    </div>
                                    
                                    <div class="meal-plan-stats">
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_calories']) ?></div>
                                            <div>Calories</div>
                                        </div>
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_protein']) ?>g</div>
                                            <div>Protein</div>
                                        </div>
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_carbs']) ?>g</div>
                                            <div>Carbs</div>
                                        </div>
                                        <div class="meal-stat">
                                            <div class="stat-value"><?= number_format($plan['avg_fat']) ?>g</div>
                                            <div>Fat</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="meal-plan-footer">
                                    <a href="view-meal-plan.php?id=<?= $plan['id'] ?>" class="action-btn secondary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit-meal-plan.php?id=<?= $plan['id'] ?>" class="action-btn secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="empty-state-text">
                            You haven't created any meal plans yet.
                        </div>
                        <a href="create-meal-plan.php" class="action-btn">
                            <i class="fas fa-plus"></i> Create Meal Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Food Log Tab -->
            <div class="tab-content" id="food-log">
                <div class="section-header">
                    <h2 class="section-title">Food Log</h2>
                    <a href="log-food.php" class="action-btn">
                        <i class="fas fa-plus"></i> Log Food
                    </a>
                </div>
                
                <?php if ($food_logs->num_rows > 0): ?>
                    <?php
                    $current_date = null;
                    while ($log = $food_logs->fetch_assoc()):
                        if ($current_date != $log['log_date']):
                            if ($current_date !== null):
                                // Close previous table
                                echo '</table>';
                            endif;
                            
                            $current_date = $log['log_date'];
                            ?>
                            <div class="food-log-date">
                                <i class="fas fa-calendar-day"></i> <?= date('l, F j, Y', strtotime($current_date)) ?>
                                
                                <?php if (isset($daily_totals[$current_date])): ?>
                                    <div style="float: right; font-size: 14px;">
                                        Total: <?= number_format($daily_totals[$current_date]['calories']) ?> cal | 
                                        P: <?= number_format($daily_totals[$current_date]['protein']) ?>g | 
                                        C: <?= number_format($daily_totals[$current_date]['carbs']) ?>g | 
                                        F: <?= number_format($daily_totals[$current_date]['fat']) ?>g
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <table class="food-log-table">
                                <tr>
                                    <th>Meal</th>
                                    <th>Food</th>
                                    <th>Serving</th>
                                    <th>Calories</th>
                                    <th>Macros</th>
                                    <th>Actions</th>
                                </tr>
                        <?php endif; ?>
                        
                        <tr>
                            <td><span class="meal-time meal-<?= strtolower($log['meal_time']) ?>"><?= $log['meal_time'] ?></span></td>
                            <td><?= htmlspecialchars($log['food_name']) ?></td>
                            <td><?= htmlspecialchars($log['serving_size']) ?></td>
                            <td><?= number_format($log['calories']) ?></td>
                            <td>P: <?= $log['protein'] ?>g | C: <?= $log['carbs'] ?>g | F: <?= $log['fat'] ?>g</td>
                            <td>
                                <a href="edit-food-log.php?id=<?= $log['id'] ?>" style="color: rgba(255, 255, 255, 0.7); margin-right: 10px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete-food-log.php?id=<?= $log['id'] ?>" style="color: rgba(255, 255, 255, 0.7);" onclick="return confirm('Are you sure you want to delete this entry?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="empty-state-text">
                            You haven't logged any food yet.
                        </div>
                        <a href="log-food.php" class="action-btn">
                            <i class="fas fa-plus"></i> Log Food
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Show toast message if exists
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
        });
    </script>
</body>
</html>
