<?php
session_start();
require 'db.php';

// Sjekk at forespørselen er POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Ugyldig forespørselsmetode";
    header("Location: dashboard_gjest.php");
    exit();
}

try {
    // Hent og valider inndata
    $melding_id = filter_input(INPUT_POST, 'melding_id', FILTER_VALIDATE_INT);
    $grunn = trim($_POST['grunn'] ?? '');
    
    // Hent IP-adresse
    $ip_adresse = $_SERVER['REMOTE_ADDR'];

    // Valider påkrevde felt
    if (!$melding_id || empty($grunn)) {
        throw new Exception("Alle påkrevde felt må fylles ut");
    }

    // Opprett databasetilkobling med gjest-rolle
    $conn = get_db_connection('guest');

    // Kall lagret prosedyre for å rapportere melding
    $stmt = $conn->prepare("CALL report_message(?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av prosedyrekall");
    }

    // For gjester sender vi NULL som student_id
    $student_id = null;
    $stmt->bind_param("isis", $melding_id, $ip_adresse, $student_id, $grunn);
    
    if (!$stmt->execute()) {
        throw new Exception("Feil ved utførelse av prosedyrekall");
    }

    // Håndter resultatet
    $result = $stmt->get_result();
    $status = $result->fetch_assoc();

    if ($status['result'] === 'SUCCESS') {
        $_SESSION['success'] = "Rapporten ble sendt inn";
    } else {
        throw new Exception($status['message'] ?? "Feil ved lagring av rapport");
    }

    $stmt->close();
    $conn->close();

    // Omdiriger tilbake til dashboard
    header("Location: dashboard_gjest.php");
    exit();

} catch (Exception $e) {
    error_log("Feil i submit_rapport.php: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard_gjest.php");
    exit();
}
?>
