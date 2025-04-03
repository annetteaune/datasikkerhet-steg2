<?php
// Aktiver feilmelding for debugging (fjern i produksjon)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konfigurer sikre session-parametre før session start
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Starte session
session_start();

// Inkluder nødvendige filer
require 'db.php';
require 'login_attempts.php';

// Sjekk om formen ble sendt via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sjekk rate limiting først
        $rate_limit = check_rate_limit();
        if ($rate_limit['locked']) {
            throw new Exception("For mange innloggingsforsøk. Vennligst prøv igjen om " . $rate_limit['time_remaining'] . " minutter.");
        }
        
        // Hent og valider input
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception("Vennligst fyll ut alle felt.");
        }
        
        // Valider e-postformat
        if (!validate_email($email)) {
            throw new Exception("Ugyldig e-postadresse.");
        }
        
        // Hent db-kobling
        $conn = get_db_connection('guest');
        
        // Kall login_lecturer-prosedyren
        $stmt = $conn->prepare("CALL login_lecturer(?)");
        if (!$stmt) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Registrer mislykket forsøk
            record_failed_attempt($_SERVER['REMOTE_ADDR']);
            throw new Exception("Ugyldig e-post eller passord.");
        }
        
        $user = $result->fetch_assoc();
        
        // Verifiser passord ved hjelp av Argon2
        if (!password_verify($password, $user['passord'])) {
            // Registrer mislykket forsøk
            record_failed_attempt($_SERVER['REMOTE_ADDR']);
            throw new Exception("Ugyldig e-post eller passord.");
        }
        
        // Nullstill innloggingsforsøk ved vellykket innlogging
        reset_login_attempts($_SERVER['REMOTE_ADDR']);
        
        // Set session variabler med navn som matcher dashboard-forventninger
        $_SESSION['foreleser_id'] = $user['bruker_id'];
        $_SESSION['foreleser_fname'] = $user['fornavn'];
        $_SESSION['foreleser_lname'] = $user['etternavn'];
        $_SESSION['foreleser_email'] = $user['epost'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Regenerér session ID for å forhindre session-fiksere
        session_regenerate_id(true);
        
        // Lukk db-tilkoblinger
        $stmt->close();
        $conn->close();
        
        // Omdiriger til dashboard
        header("Location: dashboard_foreleser.php");
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        
        // Lukk db-tilkoblinger hvis de eksisterer
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
        
        // Omdiriger tilbake med feilmelding
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: ../pages/foreleser_login.php");
        exit();
    }
} else {
    // Hvis ikke POST-forespørsel, omdiriger til login-side
    header("Location: ../pages/login.php");
    exit();
}
?>
