<?php
session_start();
include_once "../datacon.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields");
        exit();
    }

    $query = "SELECT uld.*, urd.* 
              FROM user_login_details uld 
              JOIN user_register_details urd ON uld.username = urd.email 
              WHERE uld.username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $hashed_password = $row['user_password'];

        if (password_verify($password, $hashed_password)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $email;
            $_SESSION['table_id'] = $row['table_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['profile_picture'] = $row['profile_picture'];
            $_SESSION['user_id'] = $row['table_id'];
            $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];

            $role = strtolower($_SESSION['role']);
            $message = "Login successful";

            // 🔥 New trainer-specific check
            if ($role === 'trainer') {
                // First, pull the trainer by EMAIL
                $trainerQuery = "SELECT * FROM trainers WHERE email = ?";
                $trainerStmt = mysqli_prepare($conn, $trainerQuery);
                mysqli_stmt_bind_param($trainerStmt, "s", $_SESSION['email']);
                mysqli_stmt_execute($trainerStmt);
                $trainerResult = mysqli_stmt_get_result($trainerStmt);
            
                if (!$trainer_data = mysqli_fetch_assoc($trainerResult)) {
                    // No trainer record found
                    $errorMessage = "No trainer account linked. Please contact admin.";
                    echo "<script>
                        localStorage.setItem('toastMessage', '$errorMessage');
                        localStorage.setItem('toastType', 'error');
                        window.location.href = '../login/index.php';
                    </script>";
                    exit();
                }
                
                // NOW store trainer info in session
                $_SESSION['trainer_id'] = $trainer_data['trainer_id'];
            }
            

            // 🔥 Role redirection
            switch ($role) {
                case 'admin':
                    echo "<script>
                        localStorage.setItem('toastMessage', '$message');
                        setTimeout(function() {
                            window.location.href='../admin/admin_dashboard.php';
                        }, 100);
                    </script>";
                    break;
                case 'trainer':
                    echo "<script>
                        localStorage.setItem('toastMessage', '$message');
                        setTimeout(function() {
                            window.location.href='../trainer/trainer-dashboard.php';
                        }, 100);
                    </script>";
                    break;
                case 'equipment_manager':
                    echo "<script>
                        localStorage.setItem('toastMessage', '$message');
                        setTimeout(function() {
                            window.location.href='../equipment/manager-dashboard.php';
                        }, 100);
                    </script>";
                    break;
                case 'user':
                default:
                    echo "<script>
                        localStorage.setItem('toastMessage', '$message');
                        setTimeout(function() {
                            window.location.href='../welcome/welcome.php';
                        }, 100);
                    </script>";
                    break;
            }
            exit();
        } else {
            $message = "Invalid details";
            echo "<script>
                localStorage.setItem('toastMessage', '$message');
                localStorage.setItem('toastType', 'error');
                window.location.href = '../login/index.php';
            </script>";
            exit();
        }
    } else {
        $message = "User not found";
        echo "<script>
            localStorage.setItem('toastMessage', '$message');
            localStorage.setItem('toastType', 'error');
            window.location.href = '../login/index.php';
        </script>";
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
