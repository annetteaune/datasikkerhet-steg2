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

    // Call the change_password stored procedure
    $stmt = $conn->prepare("CALL change_password(?, ?, ?)");
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        throw new Exception("Feil ved forberedelse av passordbytte");
    }

    $stmt->bind_param("iss", $foreleser_id, $current_pw, $new_pw);
    if (!$stmt->execute()) {
        error_log("Failed to execute statement: " . $stmt->error);
        throw new Exception("Feil ved utførelse av passordbytte");
    }

    $result = $stmt->get_result();
    $response = $result->fetch_assoc();
    
    // Free the result and close the statement
    $result->free();
    $stmt->close();

    if (!$response['success']) {
        error_log("Password change failed: " . $response['message']);
        throw new Exception($response['message']);
    }

    // Verifiser nåværende passord
    if (!password_verify($current_pw, $response['stored_hash'])) {
        error_log("Current password verification failed");
        throw new Exception("Nåværende passord er feil.");
    }

    // Oppdater passordet med ny hash
    $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE foreleser SET passord = ? WHERE foreleser_id = ?");
    if (!$update_stmt) {
        error_log("Failed to prepare update statement: " . $conn->error);
        throw new Exception("Feil ved forberedelse av passordoppdatering");
    }

    $update_stmt->bind_param("si", $new_hash, $foreleser_id);
    if (!$update_stmt->execute()) {
        error_log("Failed to execute update statement: " . $update_stmt->error);
        throw new Exception("Feil ved oppdatering av passord");
    }

    $update_stmt->close();

    // Password change successful
    $_SESSION['success_message'] = "Passordet ble oppdatert.";
    header("Location: dashboard_foreleser.php");
    exit();
    
} catch (Exception $e) {
    // Logg feilen
    error_log("Feil i bytt_foreleser_pw.php: " . $e->getMessage());
    
    // Lukk databasetilkoblingen hvis den eksisterer
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    
    // Sett feilmelding og omdiriger
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard_foreleser.php");
    exit();
}
?>
