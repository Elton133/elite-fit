<?php
// OTP MANAGER CLASS
// File: login/includes/otp-manager.php

require_once '../datacon.php';
require_once 'config/email-config.php';

class OTPManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function generateOTP() {
        return sprintf("%0" . OTP_LENGTH . "d", mt_rand(1, pow(10, OTP_LENGTH) - 1));
    }
    
    public function storeOTP($email, $otp) {
        try {
            $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
            
            $deleteStmt = $this->conn->prepare("DELETE FROM password_reset_otp WHERE email = ?");
            $deleteStmt->bind_param("s", $email);
            $deleteStmt->execute();
            
            $insertStmt = $this->conn->prepare("INSERT INTO password_reset_otp (email, otp, expiry, created_at) VALUES (?, ?, ?, NOW())");
            $insertStmt->bind_param("sss", $email, $otp, $expiry);
            
            return $insertStmt->execute();
        } catch (Exception $e) {
            error_log("OTP Storage Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function verifyOTP($email, $submittedOTP) {
        try {
            $stmt = $this->conn->prepare("SELECT otp, expiry FROM password_reset_otp WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['valid' => false, 'message' => 'No OTP found for this email'];
            }
            
            $row = $result->fetch_assoc();
            $storedOTP = $row['otp'];
            $expiry = $row['expiry'];
            
            if (strtotime($expiry) < time()) {
                return ['valid' => false, 'message' => 'OTP has expired'];
            }
            
            if ($submittedOTP !== $storedOTP) {
                return ['valid' => false, 'message' => 'Invalid OTP'];
            }
            
            return ['valid' => true, 'message' => 'OTP verified successfully'];
        } catch (Exception $e) {
            error_log("OTP Verification Error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Verification failed'];
        }
    }
    
    public function cleanupExpiredOTPs() {
        try {
            $stmt = $this->conn->prepare("DELETE FROM password_reset_otp WHERE expiry < NOW()");
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("OTP Cleanup Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteOTP($email) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM password_reset_otp WHERE email = ?");
            $stmt->bind_param("s", $email);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("OTP Deletion Error: " . $e->getMessage());
            return false;
        }
    }
}
?>