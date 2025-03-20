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
        // Retrieve and trim form data
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Simple validation: make sure fields are not empty
        if (empty($email) || empty($password)) {
            throw new Exception("Vennligst fyll ut både e-post og passord.");
        }

        // Get database connection with lecturer role
        $conn = get_db_connection('lecturer');
        error_log("Database connection established");

        // Hent brukerinformasjon og passord
        $stmt = $conn->prepare("SELECT foreleser_id, fornavn, etternavn, epost, passord FROM foreleser WHERE epost = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $conn->error);
            throw new Exception("Feil ved forberedelse av login");
        }

        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            error_log("Failed to execute statement: " . $stmt->error);
            throw new Exception("Feil ved utførelse av login");
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Free the result and close the statement
        $result->free();
        $stmt->close();

        if (!$user) {
            error_log("No user found for email: " . $email);
            throw new Exception("Ugyldig e-post eller passord.");
        }

        // Verifiser passordet
        if (!password_verify($password, $user['passord'])) {
            error_log("Password verification failed for email: " . $email);
            throw new Exception("Ugyldig e-post eller passord.");
        }

        // Login successful, set session variables
        $_SESSION['foreleser_id'] = $user['foreleser_id'];
        $_SESSION['foreleser_fname'] = $user['fornavn'];
        $_SESSION['foreleser_lname'] = $user['etternavn'];
        $_SESSION['foreleser_email'] = $user['epost'];
        $_SESSION['user_type'] = 'lecturer';

        // Close database connection
        close_db_connection($conn);

        // Redirect to dashboard
        header("Location: dashboard_foreleser.php");
        exit();
        
    } catch (Exception $e) {
        // Log error and show user-friendly message
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error_message'] = $e->getMessage();
        
        // Close database connection if it exists
        if (isset($conn)) {
            close_db_connection($conn);
        }
        
        header("Location: ../pages/foreleser_login.php?error=1");
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: ../pages/login.php");
    exit();
}
?>
