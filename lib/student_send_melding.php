<?php
session_start();
require 'db.php';

try {
    // Debug: Log session data
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("POST data: " . print_r($_POST, true));

    // Ensure the student is logged in
    if (!isset($_SESSION['student_fname'])) {
        throw new Exception("Du må være logget inn for å sende en melding.");
    }

    // Get form data
    $student_id = $_SESSION['student_id'] ?? null;
    $emne_id = $_POST['emne_id'] ?? null;
    $innhold = trim($_POST['melding'] ?? '');

    // Debug: Log the values we're about to use
    error_log("Using values - student_id: " . $student_id . ", emne_id: " . $emne_id . ", innhold length: " . strlen($innhold));

    // Validate input fields
    if (empty($student_id)) {
        throw new Exception("Student not found");
    }
    
    if (empty($emne_id) || empty($innhold)) {
        throw new Exception("Alle felt må fylles ut.");
    }

    // Get database connection with student role
    $conn = get_db_connection('student');

    // Call the send_message stored procedure
    $stmt = $conn->prepare("CALL send_message(?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $stmt->bind_param("iis", $student_id, $emne_id, $innhold);
    
    // Debug: Log the SQL and parameters
    error_log("Executing send_message with params - student_id: $student_id, emne_id: $emne_id, innhold: $innhold");
    
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Failed to get result from send_message procedure");
    }

    $response = $result->fetch_assoc();

    if ($response['result'] === 'SUCCESS') {
        // Close resources
        $stmt->close();
        close_db_connection($conn);

        // Set success message and redirect
        $_SESSION['success'] = "Melding er sendt til foreleser";
        header("Location: dashboard.php");
        exit();
    } else {
        throw new Exception($response['message'] ?? "En feil oppstod under sending av melding.");
    }

} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Message sending error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>