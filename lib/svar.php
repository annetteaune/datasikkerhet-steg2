<?php

session_start();
require_once 'db.php';

use CleanSteg1\Database\Database;

try {
    // Sørge for at foreleseren er logget inn
    if (!isset($_SESSION['foreleser_id'])) {
        throw new Exception("Du må være logget inn for å svare på meldinger.");
    }

    // Hent formdata
    $foreleser_id = $_SESSION['foreleser_id'];
    $melding_id = $_POST['melding_id'] ?? null;
    $innhold = trim($_POST['innhold'] ?? '');

    // Valider inputfelt
    if (empty($foreleser_id) || empty($innhold) || empty($melding_id)) {
        throw new Exception("Alle felt må fylles ut.");
    }

    // Opprett databasetilkobling
    $conn = Database::getConnection('lecturer');

    // Kall send_response-prosedyren
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
        // Lukk databaseforbindelse
        Database::closeConnection($conn);

        // Sett success-melding og omdiriger
        $_SESSION['success_message'] = "Svar er sendt til student";
        header("Location: dashboard_foreleser.php?message=success");
        exit();
    } else {
        throw new Exception($response['message'] ?? "En feil oppstod under sending av svar.");
    }
} catch (Exception $e) {
    // Logg error og vis brukervennlig melding
    error_log("Svar sending feil: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: dashboard_foreleser.php?error=1");
    exit();
}
