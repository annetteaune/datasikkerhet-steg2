<?php

session_start();
require_once 'db.php';

use CleanSteg1\Database\Database;

try {
    // Valider at nødvendige felt er fylt ut
    if (!isset($_POST['melding_id']) || !isset($_POST['innhold']) || empty($_POST['innhold'])) {
        throw new Exception("Alle felt må fylles ut");
    }

    // Hent IP-adresse
    $ip_adresse = $_SERVER['REMOTE_ADDR'];

    // Hent andre verdier
    $melding_id = filter_input(INPUT_POST, 'melding_id', FILTER_VALIDATE_INT);
    $innhold = $_POST['innhold'];

    // Opprett databasetilkobling
    $conn = Database::getConnection('guest');

    // Kall prosedyren med IP-adresse
    $stmt = $conn->prepare("CALL add_comment(?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av add_comment");
    }

    $stmt->bind_param("iss", $melding_id, $ip_adresse, $innhold);
    if (!$stmt->execute()) {
        throw new Exception("Feil ved lagring av kommentar");
    }

    // Håndter resultatet
    $result = $stmt->get_result();
    $status = $result->fetch_assoc();

    if ($status['result'] === 'SUCCESS') {
        $_SESSION['success'] = "Kommentar lagt til";
    } else {
        throw new Exception($status['message'] ?? "Feil ved lagring av kommentar");
    }

    $stmt->close();
    Database::closeConnection($conn);

    // Omdiriger tilbake til dashboard
    header("Location: dashboard_gjest.php");
    exit();
} catch (Exception $e) {
    error_log("Feil i submit_comment.php: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard_gjest.php");
    exit();
}
