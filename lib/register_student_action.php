<?php

// Konfigurer sikre session-parametre før session start
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
require_once 'db.php';
require_once 'registration_attempts.php';

use CleanSteg1\Database\Database;
use CleanSteg1\Security\RegistrationAttempts;

try {
    // Hent klientens IP-adresse
    $ip = $_SERVER['REMOTE_ADDR'];

    // Sjekk registreringsrate-begrensning
    $limit_check = RegistrationAttempts::checkLimit($ip);
    if ($limit_check['limited']) {
        $remaining_minutes = ceil($limit_check['remaining_time'] / 60);
        throw new Exception("For mange registreringsforsøk. Vennligst vent 
            {$remaining_minutes} minutter før du prøver igjen.");
    }

    // Valider påkrevde felt
    $required_fields = ['fornavn', 'etternavn', 'epost', 'passord', 'bekreft_passord'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Alle felt må fylles ut.");
        }
    }

    // Saner og valider input
    $fornavn = trim($_POST['fornavn']);
    $etternavn = trim($_POST['etternavn']);
    $epost = trim($_POST['epost']);
    $passord = $_POST['passord'];
    $bekreft_passord = $_POST['bekreft_passord'];

    // Valider e-postformat
    if (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Ugyldig e-postadresse.");
    }

    // Valider passord
    $password_validation = Database::validatePassword($passord);
    if (!$password_validation['valid']) {
        $_SESSION['error'] = $password_validation['message'];
        header("Location: ../pages/registrer_student.php");
        exit();
    }

    // Sjekk om passordene stemmer
    if ($passord !== $bekreft_passord) {
        throw new Exception("Passordene stemmer ikke overens.");
    }

    // Opprett databasetilkobling
    $conn = Database::getConnection('guest');

    // Start transaksjon
    $conn->begin_transaction();

    try {
        // Sjekk om e-post allerede eksisterer
        $stmt = $conn->prepare("SELECT student_id FROM studenter WHERE epost = ?");
        $stmt->bind_param("s", $epost);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("E-postadressen er allerede i bruk.");
        }
        $stmt->close();

        // Hash passord
        $hashed_password = password_hash($passord, PASSWORD_ARGON2ID);

        // Sett inn student
        $stmt = $conn->prepare("INSERT INTO studenter (fornavn, etternavn, epost, passord) 
            VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fornavn, $etternavn, $epost, $hashed_password);
        $stmt->execute();
        $stmt->close();

        // Registrer forsøk
        RegistrationAttempts::recordAttempt($ip);

        // Commit transaksjon
        $conn->commit();

        // Sett success-melding
        $_SESSION['success'] = "Registrering vellykket! Du kan nå logge inn.";
        header("Location: ../pages/student_login.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaksjon ved error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log error
    error_log("Registration error: " . $e->getMessage());

    // Lukk databaseforbindelse hvis den eksisterer
    if (isset($conn)) {
        $conn->close();
    }

    // Lagre feilmelding og formdata i session
    $_SESSION['error'] = $e->getMessage();
    $_SESSION['form_data'] = $_POST;

    // Omdiriger tilbake til registreringsform
    header("Location: ../pages/registrer_student.php");
    exit();
}
