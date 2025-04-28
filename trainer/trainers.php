<?php
session_start();
require_once('../datacon.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../login/index.php");
    exit();
}

// Get context from URL parameter (session or workout_request)
$context = isset($_GET['context']) ? $_GET['context'] : 'session';

// Fetch all active trainers
$stmt = $conn->prepare("SELECT * FROM trainers WHERE availability_status = 'Available'");
$stmt->execute();
$result = $stmt->get_result();
$trainers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user data for header
$email = $_SESSION['email'];
$sql_user = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$stmt_user->close();

$profile_pic = "../register/uploads/default-avatar.jpg";
if (!empty($user_data['profile_picture']) && file_exists("../register/uploads/" . $user_data['profile_picture'])) {
    $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
}

// Function to determine trainer availability status
function getTrainerStatus($trainer) {
    // This is a placeholder - you should implement real availability logic
    // based on your database structure and business rules
    $availability = isset($trainer['availability']) ? $trainer['availability'] : 'available';
    
    switch ($availability) {
        case 'fully_booked':
            return ['status' => 'fully-booked', 'label' => 'Fully Booked'];
        case 'on_leave':
            return ['status' => 'on-leave', 'label' => 'On Leave'];
        default:
            return ['status' => 'available', 'label' => 'Available'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Trainers - EliteFit Gym</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="trainers-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

        <div class="page-title">
            <h2>Our Professional Trainers</h2>
            <p>
                <?php if ($context === 'workout_request'): ?>
                    Select a trainer for your workout plan request
                <?php else: ?>
                    Schedule a session with one of our expert trainers
                <?php endif; ?>
            </p>
        </div>

        <div class="trainers-grid">
            <?php foreach ($trainers as $trainer): 
                $trainerStatus = getTrainerStatus($trainer);
            ?>
                <div class="trainer-card">
                    <div class="trainer-status <?php echo $trainerStatus['status']; ?>">
                        <?php echo $trainerStatus['label']; ?>
                    </div>
                    <div class="trainer-image">
                        <?php if (!empty($trainer['profile_picture']) && file_exists("uploads/" . $trainer['profile_picture'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($trainer['profile_picture']); ?>" alt="<?php echo htmlspecialchars($trainer['first_name']); ?>">
                        <?php else: ?>
                            <img src="../register/uploads/default-avatar.jpg" alt="Default Profile">
                        <?php endif; ?>
                    </div>
                    <div class="trainer-info">
                        <h3><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h3>
                        <p class="trainer-specialty"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                        <p class="trainer-bio"><?php echo htmlspecialchars($trainer['bio']); ?></p>
                        <div class="trainer-meta">
                            <span><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($trainer['experience_years']); ?> years</span>
                        </div>
                    </div>
                    <div class="trainer-actions">
                        <?php if ($context === 'workout_request'): ?>
                            <a href="../welcome/requests.php?trainer_id=<?php echo $trainer['trainer_id']; ?>" class="btn-select-trainer">
                                <i class="fas fa-check"></i> Select
                            </a>
                        <?php else: ?>
                            <a href="../welcome/schedule-session.php?trainer_id=<?php echo $trainer['trainer_id']; ?>" class="btn-schedule-now">
                                <i class="fas fa-calendar-plus"></i> Schedule Session
                            </a>
                        <?php endif; ?>
                        <a href="trainer-profile.php?id=<?php echo $trainer['trainer_id']; ?>" class="btn-view-profile">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($trainers)): ?>
                <div class="no-trainers">
                    <i class="fas fa-user-slash"></i>
                    <p>No trainers available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="back-button">
            <?php if ($context === 'workout_request'): ?>
                <a href="../welcome/requests.php" class="action-btn"><i class="fas fa-arrow-left"></i> Back to Request</a>
            <?php else: ?>
                <a href="../welcome/dashboard.php" class="action-btn"><i class="fas fa-home"></i> Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="../welcome/sidebar-script.js"></script>
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
</body>
</html>