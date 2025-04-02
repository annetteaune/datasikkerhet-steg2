<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include database connection file
require 'db.php';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get and validate input
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception("Vennligst fyll ut alle felt.");
        }
        
        // Get database connection with guest role
        $conn = get_db_connection('guest');
        
        // Call the login_student stored procedure
        $stmt = $conn->prepare("CALL login_student(?)");
        if (!$stmt) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Ugyldig e-post eller passord.");
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password using Argon2
        if (!password_verify($password, $user['passord'])) {
            throw new Exception("Ugyldig e-post eller passord.");
        }
        
        // Set session variables with names matching dashboard expectations
        $_SESSION['student_id'] = $user['bruker_id'];
        $_SESSION['student_fname'] = $user['fornavn'];
        $_SESSION['student_lname'] = $user['etternavn'];
        $_SESSION['student_email'] = $user['epost'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Close database connections
        $stmt->close();
        $conn->close();
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        
        // Close database connections if they exist
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
        
        // Redirect back with error message
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: ../pages/student_login.php");
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: ../pages/login.php");
    exit();
}
?>
