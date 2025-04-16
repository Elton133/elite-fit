<?php
session_start();
require_once('../datacon.php');

// Redirect if session variables are not set
if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$user_id = $_SESSION['table_id'];

// // Check if trainer_id is provided
// if (!isset($_GET['table_id']) || !is_numeric($_GET['table_id'])) {
//     header("Location: ../trainer/trainers.php");
//     exit();
// }

// $trainer_id = $_GET['table_id'];

// Fetch trainer details
$sql_trainer = "SELECT table_id, first_name, last_name, specialization, profile_picture 
                FROM user_register_details WHERE table_id = ?";
$stmt_trainer = $conn->prepare($sql_trainer);
$stmt_trainer->bind_param("i", $trainer_id);
$stmt_trainer->execute();
$result_trainer = $stmt_trainer->get_result();

if ($result_trainer->num_rows === 0) {
    header("Location: trainers.php");
    exit();
}

$trainer = $result_trainer->fetch_assoc();
$stmt_trainer->close();

// Fetch trainer availability
$sql_availability = "SELECT day_of_week, start_time, end_time 
                    FROM trainer_availability 
                    WHERE trainer_id = ? 
                    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$stmt_availability = $conn->prepare($sql_availability);
$stmt_availability->bind_param("i", $trainer_id);
$stmt_availability->execute();
$result_availability = $stmt_availability->get_result();
$availabilities = [];

while ($row = $result_availability->fetch_assoc()) {
    $availabilities[] = $row;
}
$stmt_availability->close();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $session_date = $_POST['session_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $session_type = $_POST['session_type'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Basic validation
    if (empty($session_date) || empty($start_time) || empty($end_time) || empty($session_type)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Check if the slot is available (not already booked)
        $sql_check = "SELECT session_id FROM training_sessions 
                     WHERE trainer_id = ? AND session_date = ? 
                     AND ((start_time <= ? AND end_time > ?) 
                     OR (start_time < ? AND end_time >= ?) 
                     OR (start_time >= ? AND end_time <= ?))";
        
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("isssssss", $trainer_id, $session_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $message = "This time slot is already booked. Please select another time.";
            $messageType = "error";
        } else {
            // Insert the new session
            $sql_insert = "INSERT INTO training_sessions (user_id, trainer_id, session_date, start_time, end_time, session_type, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iisssss", $user_id, $trainer_id, $session_date, $start_time, $end_time, $session_type, $notes);
            
            if ($stmt_insert->execute()) {
                $message = "Your session has been successfully scheduled!";
                $messageType = "success";
            } else {
                $message = "Error scheduling your session. Please try again.";
                $messageType = "error";
            }
            
            $stmt_insert->close();
        }
        
        $stmt_check->close();
    }
}
?>

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
                <form action="schedule-session.php?trainer_id=<?php echo $trainer_id; ?>" method="post" class="schedule-form">
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