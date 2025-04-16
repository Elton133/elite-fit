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

// Fetch trainers
$sql_trainers = "SELECT table_id, first_name, last_name,
                 profile_picture FROM user_register_details WHERE role = 'trainer' ORDER BY first_name";
$result_trainers = $conn->query($sql_trainers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Trainers - EliteFit Gym</title>
    <link rel="stylesheet" href="welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="trainers-styles.css">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    /* Trainers Page Styles */
.page-title {
  text-align: center;
  margin: 20px 0 30px;
  color: #fff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.page-title h2 {
  font-size: 2rem;
  margin-bottom: 5px;
}

.page-title p {
  font-size: 1rem;
  opacity: 0.9;
}

.trainers-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.trainer-card {
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  position: relative;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.trainer-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.trainer-status {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
  z-index: 1;
}

.trainer-status.available {
  background-color: #4caf50;
}

.trainer-status.fully-booked {
  background-color: #ff9800;
}

.trainer-status.on-leave {
  background-color: #f44336;
}

.trainer-image {
  height: 200px;
  overflow: hidden;
}

.trainer-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.trainer-card:hover .trainer-image img {
  transform: scale(1.05);
}

.trainer-info {
  padding: 15px;
}

.trainer-info h3 {
  margin: 0 0 5px;
  color: #333;
  font-size: 1.2rem;
}

.trainer-specialization {
  color: #e74c3c;
  font-weight: 600;
  margin-bottom: 8px;
}

.trainer-experience {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 8px;
}

.trainer-bio {
  color: #777;
  font-size: 0.9rem;
  line-height: 1.4;
  margin-bottom: 15px;
}

.trainer-actions {
  display: flex;
  padding: 0 15px 15px;
  gap: 10px;
}

.trainer-actions a {
  flex: 1;
  text-align: center;
  padding: 8px 0;
  border-radius: 5px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-view-profile {
  background-color: #f8f9fa;
  color: #333;
  border: 1px solid #ddd;
}

.btn-view-profile:hover {
  background-color: #e9ecef;
}

.btn-schedule {
  background-color: #e74c3c;
  color: white;
}

.btn-schedule:hover {
  background-color: #c0392b;
}

.no-trainers {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 10px;
}

.no-trainers i {
  font-size: 3rem;
  color: #e74c3c;
  margin-bottom: 15px;
}

.no-trainers p {
  font-size: 1.1rem;
  color: #555;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .trainers-container {
    grid-template-columns: 1fr;
  }

  .trainer-card {
    max-width: 100%;
  }
}

</style>
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
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <!-- User profile content from your existing code -->
                </div>
            </div>
        </header>
        
        <div class="page-title">
            <h2><i class="fas fa-users"></i> Our Professional Trainers</h2>
            <p>Select a trainer to schedule your personalized training sessions</p>
        </div>
        
        <div class="trainers-container">
            <?php if ($result_trainers && $result_trainers->num_rows > 0): ?>
                <?php while($trainer = $result_trainers->fetch_assoc()): ?>
                    <div class="trainer-card">
                        <!-- <div class="trainer-status <?php echo strtolower(str_replace(' ', '-', $trainer['availability_status'])); ?>">
                            <?php echo htmlspecialchars($trainer['availability_status']); ?>
                        </div> -->
                        <div class="trainer-image">
                            <?php 
                            $profile_pic = "../register/uploads/default-trainer.jpg"; 
                            if (!empty($trainer['profile_picture']) && file_exists("../register/uploads/" . $trainer['profile_picture'])) {
                                $profile_pic = "../register/uploads/" . $trainer['profile_picture'];
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>">
                        </div>
                        <div class="trainer-info">
                            <h3><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h3>
                            <!-- <p class="trainer-specialization"><?php echo htmlspecialchars($trainer['specialization']); ?></p> -->
                            <!-- <p class="trainer-experience"><i class="fas fa-medal"></i> <?php echo htmlspecialchars($trainer['experience_years']); ?> years experience</p> -->
                            <!-- <p class="trainer-bio"><?php echo htmlspecialchars(substr($trainer['bio'], 0, 120) . '...'); ?></p> -->
                        </div>
                        <!-- <div class="trainer-actions">
                            <a href="trainer-profile.php?id=<?php echo $trainer['trainer_id']; ?>" class="btn-view-profile">View Profile</a>
                            <a href="schedule-session.php?trainer_id=<?php echo $trainer['trainer_id']; ?>" class="btn-schedule">Schedule Session</a>
                        </div> -->
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-trainers">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>No trainers are currently available. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="sidebar-script.js"></script>
    <script>
        // Background rotation script from your existing code
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
    </script>
</body>
</html>