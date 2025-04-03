<?php

session_start();
require_once 'db.php';

use CleanSteg1\Database\Database;

try {
    // Debug: Log session data
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("POST data: " . print_r($_POST, true));

    // Sørge for at studenten er logget inn
    if (!isset($_SESSION['student_fname'])) {
        throw new Exception("Du må være logget inn for å sende en melding.");
    }

    // Hent formdata
    $student_id = $_SESSION['student_id'] ?? null;
    $emne_id = $_POST['emne_id'] ?? null;
    $innhold = trim($_POST['melding'] ?? '');

    // Debug
    error_log("Using values - student_id: " . $student_id . ", emne_id: " .
        $emne_id . ", innhold length: " . strlen($innhold));

    // Valider inputfelt
    if (empty($student_id)) {
        throw new Exception("Student ikke funnet");
    }

    if (empty($emne_id) || empty($innhold)) {
        throw new Exception("Alle felt må fylles ut.");
    }

    // Opprett databasetilkobling
    $conn = Database::getConnection('student');

    // Kall send_message-prosedyren
    $stmt = $conn->prepare("CALL send_message(?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $stmt->bind_param("iis", $student_id, $emne_id, $innhold);

    // Debug
    error_log("Executing send_message with params - student_id: $student_id, emne_id: $emne_id, innhold: $innhold");

    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Failed to get result from send_message procedure");
    }

    $response = $result->fetch_assoc();

    if ($response['result'] === 'SUCCESS') {
        // Lukk databaseforbindelse
        Database::closeConnection($conn);

        // Sett success-melding og omdiriger
        $_SESSION['success'] = "Melding er sendt til foreleser";
        header("Location: dashboard.php");
        exit();
    } else {
        throw new Exception($response['message'] ?? "En feil oppstod under sending av melding.");
    }
} catch (Exception $e) {
    // Logg error og vis brukervennlig melding
    error_log("Melding sending feil: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
