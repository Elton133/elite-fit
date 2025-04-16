<?php
    session_start();
    require_once('../datacon.php');

    // Redirect if session variables are not set
    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
        header("Location: login.php");
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

    // Fetch fitness data
    $sql_fitness = "SELECT user_weight, user_height, user_bodytype,fitness_goal_1,fitness_goal_2,fitness_goal_3,experience_level, health_condition, health_condition_desc FROM user_fitness_details WHERE table_id = ?";
    $stmt_fitness = $conn->prepare($sql_fitness);
    $stmt_fitness->bind_param("i", $user_data['table_id']);
    $stmt_fitness->execute();
    $result_fitness = $stmt_fitness->get_result();
    $fitness_data = $result_fitness->fetch_assoc();
    $stmt_fitness->close();

    $sql = "SELECT wp.workout_name 
            FROM user_fitness_details ufd
            JOIN workout_plan wp ON wp.table_id IN (ufd.preferred_workout_routine_1, 
                                                    ufd.preferred_workout_routine_2, 
                                                    ufd.preferred_workout_routine_3)
            WHERE ufd.table_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $workout_preferences = [];
    while ($row = $result->fetch_assoc()) {
        $workout_preferences[] = $row['workout_name'];
    }


    // Calculate BMI
    $bmi = null;
    $bmi_category = "";
    if (!empty($fitness_data['user_weight']) && !empty($fitness_data['user_height']) && 
        is_numeric($fitness_data['user_height']) && is_numeric($fitness_data['user_weight']) && 
        $fitness_data['user_height'] > 0 && $fitness_data['user_weight'] > 0) {
        
        $weight_kg = $fitness_data['user_weight'];
        $height_m = $fitness_data['user_height'] / 100; // Convert cm to m
        $bmi = $weight_kg / ($height_m * $height_m);
        
        // Determine BMI category
        $bmi_category = ($bmi < 18.5) ? "Underweight" :
                        (($bmi < 25) ? "Normal weight" :
                        (($bmi < 30) ? "Overweight" : "Obese"));
    }

    // Handle profile picture
    $profile_pic = "../register/uploads/default-avatar.jpg"; 
    if (!empty($user_data['profile_picture']) && file_exists("../register/uploads/" . $user_data['profile_picture'])) {
        $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
    }

    // Initialize empty arrays for goals and preferences
    $fitness_goals = [];
    $workout_preferences = [];

    // greeting
    $hour = date("H");

    // Determine the time of day
    if ($hour >= 5 && $hour < 12) {
        $greeting = "Good morning";
    } elseif ($hour >= 12 && $hour < 17) {
        $greeting = "Good afternoon";
    } elseif ($hour >= 17 && $hour < 21) {
        $greeting = "Good evening";
    } else {
        $greeting = "Good night";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to EliteFit Gym</title>
    <link rel="stylesheet" href="welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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
            
            <!-- <div class="logo-container">
                <img src="../register/dumbbell.png" alt="EliteFit Logo" class="logo">
                <h1>EliteFit<span>Gym</span></h1>
            </div> -->
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Admin Profile Picture">
                    </div>
                    <div class="user-info">
                        <!-- <h3><?= htmlspecialchars($admin_data['first_name'] ?? 'Admin') . ' ' . htmlspecialchars($admin_data['last_name'] ?? '') ?></h3> -->
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
            <h2><?php echo $greeting . ', ' . htmlspecialchars($user_data['first_name'] ?? 'User'); ?>!</h2>
<p>Ready to crush your fitness goals today?</p>
            </div>
            <div class="quick-actions">
                <button class="action-btn"><i class="fas fa-dumbbell"></i> Start Workout</button>
                <a href="../welcome/schedule-session.php" class="action-btn secondary"><i class="fas fa-calendar-alt"></i> Book Class</a>
            </div>
        </div>
        
        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Your Fitness Stats</h3>
                    </div>
                    <div class="card-content">
                        <div class="stat-item">
                            <div class="stat-icon bmi-icon">
                                <i class="fas fa-weight"></i>
                            </div>
                            <div class="stat-info">
                                <h4>BMI</h4>
                                <p class="stat-value"><?php echo isset($bmi) ? number_format($bmi, 1) : 'N/A'; ?></p>
                                <p class="stat-label"><?php echo htmlspecialchars($bmi_category); ?></p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon weight-icon">
                                <i class="fas fa-weight-scale"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Weight</h4>
                                <p class="stat-value"><?php echo isset($fitness_data['user_weight']) ? htmlspecialchars($fitness_data['user_weight']) . ' kg' : 'N/A'; ?></p>
                                <p class="stat-label">Body Type: <?php echo htmlspecialchars($fitness_data['user_bodytype'] ?? 'Not specified'); ?></p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon height-icon">
                                <i class="fas fa-ruler-vertical"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Height</h4>
                                <p class="stat-value"><?php echo isset($fitness_data['user_height']) ? htmlspecialchars($fitness_data['user_height']) . ' cm' : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Add a card for fitness goals -->
                <div class="dashboard-card goals-card">
    <div class="card-header">
        <h3><i class="fas fa-bullseye"></i> Your Fitness Goals</h3>
    </div>
    <div class="card-content">
        <?php 
        // Assuming $user_goals is fetched from your database
        $goals = [
            'fitness_goal_1' => $fitness_data['fitness_goal_1'],
            'fitness_goal_2' => $fitness_data['fitness_goal_2'],
            'fitness_goal_3' => $fitness_data['fitness_goal_3']
        ];

        // Remove empty goals
        $filtered_goals = array_filter($goals);

        if (!empty($filtered_goals)): ?>
            <ul class="goals-list">
                <?php 
                $index = 0;
                foreach ($filtered_goals as $goal): 
                    $progress = (3 - $index) * 25; // Adjust progress dynamically
                    $index++; // Increment index for progress calculation
                ?>
                    <li class="goal-item">
                        <div class="goal-info">
                            <p class="goal-description"><?php echo htmlspecialchars($goal); ?></p>
                        </div>
                        <div class="goal-progress">
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-goals">No fitness goals set yet. <a href="#">Set your first goal!</a></p>
        <?php endif; ?>
    </div>
</div>

</div>
            
            <div class="dashboard-row">
                <!-- Add a card for workout preferences -->
        <div class="dashboard-card workout-card">
            <div class="card-header">
                <h3><i class="fas fa-running"></i> Your Workout Preferences</h3>
            </div>
            <div class="card-content">
                <?php if (count($workout_preferences) > 0): ?>
                    <div class="workout-preferences">
                        <?php foreach ($workout_preferences as $workout): ?>
                            <div class="workout-badge">
                                <?php echo htmlspecialchars($workout); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-workouts">No workout preferences set yet. <a href="#">Set your preferences!</a></p>
                <?php endif; ?>
        </div>
</div>
                
                <!-- Add a card for health conditions if any -->
                <?php if (isset($fitness_data['health_condition']) && $fitness_data['health_condition'] == 'Yes'): ?>
                <div class="dashboard-card health-card">
                    <div class="card-header">
                        <h3><i class="fas fa-heartbeat"></i> Health Considerations</h3>
                    </div>
                    <div class="card-content">
                        <div class="health-info">
                            <p><?php echo htmlspecialchars($fitness_data['health_condition_desc'] ?? 'No specific details provided.'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        const backgrounds = [
            'url("https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1470&q=80")'
        ];
        
        let currentBg = 0;
        const bgElement = document.querySelector('.background');
        
        function changeBackground() {
            bgElement.style.backgroundImage = backgrounds[currentBg];
            currentBg = (currentBg + 1) % backgrounds.length;
        }
        
        changeBackground();
        setInterval(changeBackground, 8000);


        document.querySelector('.dropdown-menu').addEventListener('click', function() {
            this.querySelector('.dropdown-content').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.dropdown-menu') && !event.target.matches('.fa-chevron-down')) {
                const dropdowns = document.querySelectorAll('.dropdown-content');
                dropdowns.forEach(dropdown => {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                });
            }
        });

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

