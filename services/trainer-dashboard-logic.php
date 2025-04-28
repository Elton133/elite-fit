<?php
        session_start();
        require_once('../datacon.php');

        // Redirect if session variables are not set or user is not a trainer
        if (!isset($_SESSION['email']) || !isset($_SESSION['table_id']) || $_SESSION['role'] !== 'trainer') {
            header("Location: ../login/index.php");
            exit();
        }
        

        $email = $_SESSION['email'];
        $trainer_id = $_SESSION['trainer_id'];
        $table_id = $_SESSION['table_id'];

        // Fetch trainer data
        $sql_trainer = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ? AND role = 'trainer'";
        $stmt_trainer = $conn->prepare($sql_trainer);
        $stmt_trainer->bind_param("s", $email);
        $stmt_trainer->execute();
        $result_trainer = $stmt_trainer->get_result();
        $trainer_data = $result_trainer->fetch_assoc();
        $stmt_trainer->close();

        // Handle profile picture
        $profile_pic = "../register/uploads/default-avatar.jpg"; 

        if (!empty($trainer_data['profile_picture'])) {
            $trainer_pic = $trainer_data['profile_picture'];
        
            if (file_exists("../register/uploads/" . $trainer_pic)) {
                $profile_pic = "../register/uploads/" . $trainer_pic;
            } elseif (file_exists("../register/" . $trainer_pic)) {
                $profile_pic = "../register/" . $trainer_pic;
            }
        }
        
       


        // Fetch pending workout plan requests
        $pending_requests = [];
        $sql_pending = "SELECT 
                            wr.request_id,
                            wr.user_id,
                            wr.request_date,
                            wr.status,
                            wr.trainer_id,
                            wr.notes,
                            u.first_name,
                            u.last_name,
                            u.contact_number,
                            u.email,
                            u.location,
                            u.gender,
                            u.profile_picture,
                            ufd.fitness_goal_1,
                            ufd.fitness_goal_2,
                            ufd.fitness_goal_3,
                            ufd.experience_level
                        FROM workout_requests wr
                        JOIN user_register_details u ON wr.user_id = u.table_id
                        JOIN user_fitness_details ufd ON wr.user_id = ufd.table_id
                        WHERE wr.trainer_id = ?
                          AND wr.status = 'pending'
                        ORDER BY wr.request_date DESC
                        LIMIT 5";
        
        $stmt_pending = $conn->prepare($sql_pending);
        $stmt_pending->bind_param("i", $trainer_data['table_id']);
        $stmt_pending->execute();
        $result_pending = $stmt_pending->get_result();
        while ($row = $result_pending->fetch_assoc()) {
            $pending_requests[] = $row;
        }
        $stmt_pending->close();
        

        // Fetch active clients
        $sql_active = "SELECT u.table_id, u.first_name, u.last_name, u.profile_picture,
                    wp.plan_id, wp.plan_name, wp.start_date, wp.end_date, wp.last_updated
                    FROM workout_plans wp
                    JOIN user_register_details u ON wp.user_id = u.table_id
                    WHERE wp.trainer_id = ? AND wp.status = 'active'
                    ORDER BY wp.last_updated DESC
                    LIMIT 5";
        $stmt_active = $conn->prepare($sql_active);
        $stmt_active->bind_param("i", $trainer_data['table_id']);
        $stmt_active->execute();
        $result_active = $stmt_active->get_result();
        $active_clients = [];
        while ($row = $result_active->fetch_assoc()) {
            $active_clients[] = $row;
        }
        $stmt_active->close();

$query = "SELECT 
    ts.session_id, 
    ts.user_id, 
    ts.trainer_id, 
    ts.session_date, 
    ts.start_time, 
    ts.end_time, 
    ts.session_type, 
    ts.session_status, 
    ts.notes, 
    urd.first_name, 
    urd.last_name, 
    urd.contact_number, 
    urd.email, 
    urd.location, 
    urd.gender, 
    urd.profile_picture
FROM training_sessions ts
INNER JOIN user_register_details urd ON ts.user_id = urd.table_id
WHERE ts.trainer_id = ?
AND ts.session_date >= CURDATE()
ORDER BY ts.session_date ASC, ts.start_time ASC
LIMIT 5";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trainer_data['table_id']);
$stmt->execute();
$result = $stmt->get_result();
$sessions = $result->fetch_all(MYSQLI_ASSOC);



        // Fetch trainer stats
        $sql_stats = "SELECT 
                    (SELECT COUNT(*) FROM workout_plans WHERE trainer_id = ? AND status = 'active') as active_plans,
                    (SELECT COUNT(*) FROM workout_requests WHERE trainer_id = ? AND status = 'pending') as pending_requests,
                    (SELECT COUNT(DISTINCT user_id) FROM workout_plans WHERE trainer_id = ?) as total_clients,
                    (SELECT COUNT(*) FROM trainer_reviews WHERE trainer_id = ?) as total_reviews";
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->bind_param("iiii", $trainer_data['table_id'], $trainer_data['table_id'], $trainer_data['table_id'], $trainer_data['table_id']);
        $stmt_stats->execute();
        $trainer_stats = $stmt_stats->get_result()->fetch_assoc();
        $stmt_stats->close();

        // Calculate average rating
        $sql_rating = "SELECT AVG(rating) as avg_rating FROM trainer_reviews WHERE trainer_id = ?";
        $stmt_rating = $conn->prepare($sql_rating);
        $stmt_rating->bind_param("i", $trainer_data['table_id']);
        $stmt_rating->execute();
        $avg_rating = $stmt_rating->get_result()->fetch_assoc()['avg_rating'] ?? 0;
        $avg_rating = number_format($avg_rating, 1);
        $stmt_rating->close();

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