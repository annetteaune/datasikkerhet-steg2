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
    $innhold = trim($_POST['innhold'] ?? '');

    // Valider påkrevde felt
    if (!$melding_id || !$gjest_id || empty($innhold)) {
        throw new Exception("Alle feltene må fylles ut");
    }

    // Opprett databasetilkobling med gjest-rolle
    $conn = get_db_connection('guest');

    // Kall lagret prosedyre for å legge til kommentar
    $stmt = $conn->prepare("CALL add_comment(?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av prosedyrekall");
    }

    $stmt->bind_param("iis", $melding_id, $gjest_id, $innhold);
    
    if (!$stmt->execute()) {
        throw new Exception("Feil ved utførelse av prosedyrekall");
    }

    $_SESSION['success'] = "Kommentaren ble lagt til";
    header("Location: ../pages/dashboard_gjest.php");
    
} catch (Exception $e) {
    $_SESSION['error'] = "Feil: " . $e->getMessage();
    error_log("Feil i submit_comment.php: " . $e->getMessage());
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
