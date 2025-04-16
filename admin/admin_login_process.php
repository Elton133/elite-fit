<?php
session_start();
include_once "../datacon.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Securely get form data
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];

    if (empty($admin_username) || empty($admin_password)) {
        header("Location: admin.php?error=Please fill in all fields");
        exit();
    }

    // Prepare statement to prevent SQL injection
    $query = "SELECT * FROM  admin_login_details WHERE admin_username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $admin_username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $hashed_password = $row['admin_password'];
        if (password_verify($admin_password, $hashed_password)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['admin_username'] = $admin_username;
            $_SESSION['table_id'] = $row['table_id'] ?? null;
            
            // Fetch fitness details if available
            // if ($_SESSION['table_id']) {
            //     $fitness_query = "SELECT * FROM user_fitness_details WHERE table_id = ?";
            //     $stmt_fitness = mysqli_prepare($conn, $fitness_query);
            //     mysqli_stmt_bind_param($stmt_fitness, "i", $_SESSION['table_id']);
            //     mysqli_stmt_execute($stmt_fitness);
            //     $fitness_result = mysqli_stmt_get_result($stmt_fitness);
                
            //     if ($fitness_data = mysqli_fetch_assoc($fitness_result)) {
            //         $_SESSION['fitness_details'] = $fitness_data;
            //     }
            // }

            header("Location: ../welcome/welcome.php"); // Redirect to dashboard
            exit();
        } else {
            header("Location: admin.php?error=Incorrect password");
            exit();
        }
    } else {
        header("Location:admin.php?error=User does not exist");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}
?>
