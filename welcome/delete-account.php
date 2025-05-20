<?php
session_start();
include_once "../datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Begin transaction
$conn->begin_transaction();

try {
    // Get user email for deleting from login table
    $stmt = $conn->prepare("SELECT email FROM user_register_details WHERE table_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $email = $user['email'];
    
    // Delete from user_settings
    $stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Delete from user_login_details
    $stmt = $conn->prepare("DELETE FROM user_login_details WHERE username = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Delete from user_register_details
    $stmt = $conn->prepare("DELETE FROM user_register_details WHERE table_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Clear session and redirect to login
    session_unset();
    session_destroy();
    
    // Set message for login page
    session_start();
    $_SESSION['message'] = "Your account has been successfully deleted.";
    header("Location: ../login.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Set error message
    $_SESSION['error'] = "Failed to delete account: " . $e->getMessage();
    header("Location: settings.php");
    exit();
}
?>
