<?php include '../services/mysessions-logic.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - EliteFit Gym</title>
    <link rel="stylesheet" href="welcome-styles.css">
    <link rel="stylesheet" href="sidebar-styles.css">
    <link rel="stylesheet" href="sessions-styles.css">
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
            <h2><i class="fas fa-calendar-check"></i> My Training Sessions</h2>
            <p>View and manage your scheduled training sessions</p>
        </div>
        
        <div class="sessions-actions">
            <a href="../trainer/trainers.php" class="btn-new-session"><i class="fas fa-plus"></i> Schedule New Session</a>
        </div>
        
        <div class="sessions-container">
            <?php if ($result_sessions && $result_sessions->num_rows > 0): ?>
                <div class="sessions-list">
                    <?php 
                    $current_date = null;
                    while($session = $result_sessions->fetch_assoc()): 
                        $session_date = new DateTime($session['session_date']);
                        $formatted_date = $session_date->format('F j, Y');
                        
                        // Display date header if it's a new date
                        if ($current_date !== $formatted_date):
                            $current_date = $formatted_date;
                            $is_past = $session_date < new DateTime('today');
                    ?>
                            <div class="date-header <?php echo $is_past ? 'past' : ''; ?>">
                                <?php echo $formatted_date; ?>
                                <?php if ($is_past): ?>
                                    <span class="past-label">Past</span>
                                <?php endif; ?>
                            </div>
                    <?php endif; ?>
                    
                    <div class="session-card <?php echo strtolower($session['session_status']); ?>">
                        <div class="session-time">
                            <?php 
                                echo date('g:i A', strtotime($session['start_time'])) . ' - ' . 
                                     date('g:i A', strtotime($session['end_time'])); 
                            ?>
                        </div>
                        
                        <div class="session-details">
                            <div class="session-type">
                                <i class="fas fa-dumbbell"></i>
                                <?php echo htmlspecialchars($session['session_type']); ?>
                            </div>
                            
                            <div class="session-trainer">
                                <?php 
                                $profile_pic = "../register/uploads/default-trainer.jpg"; 
                                if (!empty($session['profile_picture']) && file_exists("../register/uploads/" . $session['profile_picture'])) {
                                    $profile_pic = "../register/uploads/" . $session['profile_picture'];
                                }
                                ?>
                                <div class="trainer-avatar">
                                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Trainer">
                                </div>
                                <div class="trainer-name">
                                    <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($session['notes'])): ?>
                                <div class="session-notes">
                                    <i class="fas fa-sticky-note"></i>
                                    <?php echo htmlspecialchars($session['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="session-status">
                            <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $session['session_status'])); ?>">
                                <?php echo htmlspecialchars($session['session_status']); ?>
                            </span>
                        </div>
                        
                        <div class="session-actions">
                            <?php if ($session['session_status'] === 'Scheduled' && new DateTime($session['session_date']) > new DateTime('today')): ?>
                                <a href="edit-session.php?id=<?php echo $session['session_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="cancel-session.php?id=<?php echo $session['session_id']; ?>" class="btn-cancel" 
                                   onclick="return confirm('Are you sure you want to cancel this session?');">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php elseif ($session['session_status'] === 'Completed'): ?>
                                <a href="rate-session.php?id=<?php echo $session['session_id']; ?>" class="btn-rate">
                                    <i class="fas fa-star"></i> Rate
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-sessions">
                    <i class="fas fa-calendar-xmark"></i>
                    <h3>No Sessions Scheduled</h3>
                    <p>You don't have any training sessions scheduled yet.</p>
                    <a href="../trainer/trainers.php" class="btn-schedule-now">Schedule Your First Session</a>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
</body>
</html>