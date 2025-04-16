<?php
session_start();
include_once "../datacon.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Securely get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields");
        exit();
    }

    // Prepare statement to prevent SQL injection
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

        // Verify if the provided password matches the hashed password
        if (password_verify($password, $hashed_password)) {
            // Set all relevant session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $email;
            $_SESSION['table_id'] = $row['table_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['profile_picture'] = $row['profile_picture'];
            $_SESSION['user_id'] = $row['table_id']; // Additional alias for table_id
            $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];

            // Check role and redirect accordingly
            switch(strtolower($_SESSION['role'])) {
                case 'admin':
                    $message = "Login successful";
echo "<script>
   localStorage.setItem('toastMessage', '$message');
   setTimeout(function() {
       window.location.href='../admin/admin_dashboard.php';
   }, 100);
</script>";

                    // header("Location: ../admin/admin_dashboard.php");
                    break;
                case 'trainer':
                    header("Location: ../trainer/trainer-dashboard.php");
                    break;
                case 'equipment_manager':
                    header("Location: ../equipment/manager-dashboard.php");
                    break;
                case 'user':
                default:
                $message = "Login successful";
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
            echo "<script>alert('Invalid details'); window.location.href='../login/index.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='../login/index.php';</script>";
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>