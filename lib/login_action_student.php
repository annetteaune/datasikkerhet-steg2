<?php

// Apply security measures first
require_once 'security.php';

// Aktiver feilmelding for debugging (fjern i produksjon)
//error_reporting(E_ALL);
ini_set('display_errors', 1);
// Konfigurer sikre session-parametre før session start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', 1);


// Starte session
session_start();
error_log("Session started");

// Inkluder nødvendige filer
require_once 'bootstrap.php';
require_once 'login_attempts.php';

use CleanSteg1\Database\Database;
use function CleanSteg1\Security\{check_rate_limit, record_failed_attempt,
    reset_login_attempts, sanitize_input, validate_email};

error_log("Required files loaded");

// Sjekk om formen ble sendt via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST request received");

    try {
        // Sjekk rate limiting først
        $rate_limit = check_rate_limit();
        error_log("Rate limit check completed: " . json_encode($rate_limit));

        if ($rate_limit['locked']) {
            throw new Exception("For mange innloggingsforsøk. Vennligst prøv igjen om "
                . $rate_limit['time_remaining'] . " minutter.");
        }

        // Hent og valider input
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        error_log("Input received - Email: " . $email);

        if (empty($email) || empty($password)) {
            throw new Exception("Vennligst fyll ut alle felt.");
        }

        // Valider e-postformat
        if (!validate_email($email)) {
            throw new Exception("Ugyldig e-postadresse.");
        }

        // Valider passord
        $password_validation = Database::validatePassword($password);
        if (!$password_validation['valid']) {
            record_failed_attempt($_SERVER['REMOTE_ADDR']);
            $_SESSION['error'] = "Ugyldig epost eller passord";
            header("Location: /steg2/pages/student_login.php");
            exit();
        }

        // Opprett databasetilkobling
        error_log("Attempting database connection");
        $conn = Database::getConnection('guest');
        error_log("Database connection established");

        // Kall login_student-prosedyren
        $stmt = $conn->prepare("CALL login_student(?)");
        if (!$stmt) {
            error_log("Database prepare failed: " . $conn->error);
            record_failed_attempt($_SERVER['REMOTE_ADDR']);
            throw new Exception("Ugyldig epost eller passord");
        }

        $stmt->bind_param("s", $email);
        error_log("Executing login_student procedure");
        $stmt->execute();
        $result = $stmt->get_result();
        error_log("Query executed. Num rows: " . $result->num_rows);

        if ($result->num_rows === 0) {
            record_failed_attempt($_SERVER['REMOTE_ADDR']);
            throw new Exception("Ugyldig epost eller passord");
        }

        $user = $result->fetch_assoc();
        error_log("User data retrieved: " . json_encode($user));

        // Verifiser passord ved hjelp av Argon2
        if (!password_verify($password, $user['passord'])) {
            error_log("Password verification failed");
            record_failed_attempt($_SERVER['REMOTE_ADDR']);
            throw new Exception("Ugyldig epost eller passord");
        }

        error_log("Password verified successfully");
        // Reset login-forsøk ved vellykket innlogging
        reset_login_attempts($_SERVER['REMOTE_ADDR']);

        // Set session variabler med navn som matcher dashboard-forventninger
        $_SESSION['student_id'] = $user['bruker_id'];
        $_SESSION['student_fname'] = $user['fornavn'];
        $_SESSION['student_lname'] = $user['etternavn'];
        $_SESSION['student_email'] = $user['epost'];
        $_SESSION['user_type'] = $user['user_type'];

        error_log("Session variables set: " . json_encode($_SESSION));

        // Regenerér session ID for å forhindre session-fiksere
        session_regenerate_id(true);
        error_log("Session ID regenerated");

        // Lukk db-tilkoblinger
        $stmt->close();
        Database::closeConnection($conn);

        error_log("Redirecting to dashboard");
        // Omdiriger til dashboard with correct path
        header("Location: /steg2/lib/dashboard.php");
        exit();
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        // Lukk db-tilkoblinger hvis de eksisterer
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            Database::closeConnection($conn);
        }

        // Omdiriger tilbake med feilmelding
        $_SESSION['error'] = $e->getMessage();
        header("Location: /steg2/pages/student_login.php");
        exit();
    }
} else {
    error_log("Non-POST request received");
    // Hvis ikke POST-forespørsel, omdiriger til login-side
    header("Location: /steg2/pages/login.php");
    exit();
}
