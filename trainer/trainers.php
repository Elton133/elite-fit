<?php include '../services/trainers-page.php'?>

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                            $profile_pic = "../register/uploads/default-avatar.jpg"; 
                            if (!empty($trainer['profile_picture'])) {
                                if (file_exists("../register/uploads/" . $trainer['profile_picture'])) {
                                    $profile_pic = "../register/uploads/" . $trainer['profile_picture'];
                                } elseif (file_exists("../register/" . $trainer['profile_picture'])) {
                                    $profile_pic = "../register/" . $trainer['profile_picture'];
                                }
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>">
                        </div>
                        <div class="trainer-info">
                            <h3><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h3>
                            <p class="trainer-specialization"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                            <p class="trainer-experience"><i class="fas fa-medal"></i> <?php echo htmlspecialchars($trainer['experience_years']); ?> years experience</p>
                            <p class="trainer-bio"><?php echo htmlspecialchars(substr($trainer['bio'], 0, 120) . '...'); ?></p>
                        </div>
                        <div class="trainer-actions">
                            <a href="trainer-profile.php?id=<?php echo $trainer['trainer_id']; ?>" class="btn-view-profile">View Profile</a>
                            <a href="../welcome/schedule-session.php?trainer_id=<?php echo $trainer['trainer_id']; ?>" class="btn-schedule">Schedule Session</a>
                        </div>
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
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
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