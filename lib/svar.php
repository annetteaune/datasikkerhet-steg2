<?php
session_start();
require 'db.php';

try {
    // Ensure the lecturer is logged in
    if (!isset($_SESSION['foreleser_id'])) {
        throw new Exception("Du må være logget inn for å svare på meldinger.");
    }

    // Get form data
    $foreleser_id = $_SESSION['foreleser_id'];
    $melding_id = $_POST['melding_id'] ?? null;
    $innhold = trim($_POST['innhold'] ?? '');

    // Validate input fields
    if (empty($foreleser_id) || empty($innhold) || empty($melding_id)) {
        throw new Exception("Alle felt må fylles ut.");
    }

    // Get database connection with lecturer role
    $conn = get_db_connection('lecturer');

    // Call the send_response stored procedure
    $stmt = $conn->prepare("CALL send_response(?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $stmt->bind_param("iis", $melding_id, $foreleser_id, $innhold);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Failed to get result from send_response procedure");
    }

    $response = $result->fetch_assoc();

    if ($response['result'] === 'SUCCESS') {
        // Close resources
        $stmt->close();
        close_db_connection($conn);

        // Set success message and redirect
        $_SESSION['success_message'] = "Svar er sendt til student";
        header("Location: dashboard_foreleser.php?message=success");
        exit();
    } else {
        throw new Exception($response['message'] ?? "En feil oppstod under sending av svar.");
    }

} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Response sending error: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: dashboard_foreleser.php?error=1");
    exit();
}
?>