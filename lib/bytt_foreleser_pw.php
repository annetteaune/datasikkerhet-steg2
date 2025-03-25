<?php
// bytt_foreleser_pw.php

session_start();
if (!isset($_SESSION['foreleser_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

try {
    $foreleser_id = $_SESSION['foreleser_id'];
    $current_pw = $_POST['current_pw'] ?? '';
    $new_pw = $_POST['new_pw'] ?? '';
    $confirm_pw = $_POST['confirm_pw'] ?? '';
    
    // Valider påkrevde felt
    if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
        throw new Exception("Alle felt må fylles ut.");
    }
    
    // Valider passordkrav
    if (strlen($new_pw) < 8) {
        throw new Exception("Nytt passord må være minst 8 tegn langt.");
    }
    
    if (!preg_match('/[A-Z]/', $new_pw)) {
        throw new Exception("Nytt passord må inneholde minst én stor bokstav.");
    }
    
    if (!preg_match('/[a-z]/', $new_pw)) {
        throw new Exception("Nytt passord må inneholde minst én liten bokstav.");
    }
    
    if (!preg_match('/[0-9]/', $new_pw)) {
        throw new Exception("Nytt passord må inneholde minst ett tall.");
    }
    
    // Sjekk at nytt passord og bekreftelse matcher
    if ($new_pw !== $confirm_pw) {
        throw new Exception("Passordene er ikke like.");
    }
    
    // Get database connection with lecturer role
    $conn = get_db_connection('lecturer');
    error_log("Database connection established");

    // Hent foreleserens lagrede passord for verifisering
    $verify_stmt = $conn->prepare("SELECT passord FROM foreleser WHERE foreleser_id = ?");
    if (!$verify_stmt) {
        throw new Exception("Feil ved forberedelse av passordsjekk");
    }

    $verify_stmt->bind_param("i", $foreleser_id);
    if (!$verify_stmt->execute()) {
        throw new Exception("Feil ved utførelse av passordsjekk");
    }

    $result = $verify_stmt->get_result();
    $user = $result->fetch_assoc();
    $verify_stmt->close();

    if (!$user || !password_verify($current_pw, $user['passord'])) {
        throw new Exception("Nåværende passord er feil");
    }

    // Hash det nye passordet
    $hashed_new_pw = password_hash($new_pw, PASSWORD_DEFAULT);

    // Kall prosedyren for å oppdatere passordet
    $update_stmt = $conn->prepare("CALL change_lecturer_password(?, ?)");
    if (!$update_stmt) {
        throw new Exception("Feil ved forberedelse av passordbytte");
    }

    $update_stmt->bind_param("is", $foreleser_id, $hashed_new_pw);
    if (!$update_stmt->execute()) {
        throw new Exception("Feil ved utførelse av passordbytte");
    }

    $result = $update_stmt->get_result();
    if (!$result) {
        throw new Exception("Ingen respons fra databasen");
    }
    
    $response = $result->fetch_assoc();
    if ($response['result'] === 'ERROR') {
        throw new Exception($response['message']);
    }
    
    $update_stmt->close();
    $conn->close();
    
    // Password change successful
    $_SESSION['pw_message'] = "Passordet ble oppdatert.";
    header("Location: dashboard_foreleser.php");
    exit();
    
} catch (Exception $e) {
    // Logg feilen
    error_log("Feil i bytt_foreleser_pw.php: " . $e->getMessage());
    
    // Lukk databasetilkoblingen hvis den eksisterer
    if (isset($verify_stmt) && $verify_stmt instanceof mysqli_stmt) {
        $verify_stmt->close();
    }
    if (isset($update_stmt) && $update_stmt instanceof mysqli_stmt) {
        $update_stmt->close();
    }
    if (isset($conn)) {
        close_db_connection($conn);
    }
    
    // Sett feilmelding og omdiriger
    $_SESSION['pw_message'] = $e->getMessage();
    header("Location: dashboard_foreleser.php");
    exit();
}
?>
