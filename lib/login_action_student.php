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
        // Clear any existing session data
        $_SESSION = array();

        // Retrieve and trim form data
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Simple validation: make sure fields are not empty
        if (empty($email) || empty($password)) {
            throw new Exception("Vennligst fyll ut både e-post og passord.");
        }

        // Get database connection with guest role for login
        $conn = get_db_connection('guest');

        // Call the user_login stored procedure with raw password
        $stmt = $conn->prepare("CALL user_login(?, ?)");
        if (!$stmt) {
            throw new Exception("Database query failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $email, $password);
        
        if (!$stmt->execute()) {
            throw new Exception("Feil ved innlogging: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Debug: Log what we got from the database with column names
        error_log("Login result columns: " . implode(", ", array_keys($user ?? [])));
        error_log("Login result values: " . print_r($user, true));
        
        // Free the result and close the statement
        $result->free();
        $stmt->close();
        
        // Handle multiple result sets
        while ($conn->more_results() && $conn->next_result()) {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        }

        if (!$user) {
            throw new Exception("Ugyldig e-post eller passord.");
        }

        // Verify that this is a student account
        if ($user['user_type'] !== 'student') {
            throw new Exception("Denne kontoen er ikke en student-konto.");
        }

        // Debug: Log the user array before setting session
        error_log("User data before setting session: " . print_r($user, true));

        // Login successful, set session variables
        if (!isset($user['bruker_id']) || empty($user['bruker_id'])) {
            throw new Exception("Kunne ikke hente student-ID.");
        }

        // Set session variables for successful login
        $_SESSION['student_id'] = $user['bruker_id'];
        $_SESSION['student_fname'] = $user['fornavn'];
        $_SESSION['student_lname'] = $user['etternavn'];
        $_SESSION['student_email'] = $user['epost'];
        $_SESSION['user_type'] = 'student';

        // Debug: Log what we set in the session
        error_log("Session variables set: " . print_r($_SESSION, true));

        // Close database connection
        close_db_connection($conn);

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        // Log error and show user-friendly message
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../pages/student_login.php?error=1");
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: ../pages/login.php");
    exit();
}
?>
