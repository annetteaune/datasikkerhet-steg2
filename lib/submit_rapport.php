<?php
session_start();
require 'db.php';

// Sjekk at forespørselen er POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Ugyldig forespørselsmetode";
    header("Location: ../pages/dashboard_gjest.php");
    exit();
}

try {
    // Hent og valider inndata
    $melding_id = filter_input(INPUT_POST, 'melding_id', FILTER_VALIDATE_INT);
    $gjest_id = filter_input(INPUT_POST, 'gjest_id', FILTER_VALIDATE_INT);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $grunn = trim($_POST['grunn'] ?? '');

    // Valider påkrevde felt
    if (!$melding_id || !$gjest_id || empty($grunn)) {
        throw new Exception("Alle påkrevde felt må fylles ut");
    }

    // Opprett databasetilkobling med gjest-rolle
    $conn = get_db_connection('guest');

    // Kall lagret prosedyre for å rapportere melding
    $stmt = $conn->prepare("CALL report_message(?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av prosedyrekall");
    }

    // student_id kan være NULL
    $stmt->bind_param("iisi", $melding_id, $gjest_id, $grunn, $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Feil ved utførelse av prosedyrekall");
    }

    $_SESSION['success'] = "Rapporten ble sendt inn";
    header("Location: ../pages/dashboard_gjest.php");
    
} catch (Exception $e) {
    $_SESSION['error'] = "Feil: " . $e->getMessage();
    error_log("Feil i submit_rapport.php: " . $e->getMessage());
    header("Location: ../pages/dashboard_gjest.php");
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
