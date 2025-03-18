<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

try {
    $student_id = $_SESSION['student_id'];
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
    
    // Opprett databasetilkobling med student-rolle
    $conn = get_db_connection('student');
    
    // Kall lagret prosedyre for å bytte passord
    $stmt = $conn->prepare("CALL change_student_password(?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av spørring: " . $conn->error);
    }
    
    $stmt->bind_param("iss", $student_id, $current_pw, $new_pw);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        throw new Exception("Feil ved bytte av passord.");
    }
    
    $response = $result->fetch_assoc();
    
    if (!$response || $response['success'] != 1) {
        throw new Exception($response['message'] ?? "Feil ved bytte av passord.");
    }
    
    // Lukk databasetilkoblingen
    $stmt->close();
    $conn->close();
    
    // Sett suksessmelding og omdiriger
    $_SESSION['success'] = "Passordet ditt er endret.";
    header("Location: dashboard_student.php");
    exit();
    
} catch (Exception $e) {
    // Logg feilen
    error_log("Feil i bytt_pw.php: " . $e->getMessage());
    
    // Lukk databasetilkoblingen hvis den eksisterer
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    
    // Sett feilmelding og omdiriger
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard_student.php");
    exit();
}
?>
