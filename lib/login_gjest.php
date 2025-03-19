<?php
session_start();
require_once 'db.php';

try {
    // 1. Sjekk om PIN-kode er oppgitt
    if (!isset($_POST['emneID']) || empty(trim($_POST['emneID']))) {
        throw new Exception("Ingen PIN-kode oppgitt.");
    }
    
    $pin = trim($_POST['emneID']);
    
    // Valider PIN-kode format (4 siffer)
    if (!preg_match('/^\d{4}$/', $pin)) {
        throw new Exception("Ugyldig PIN-kode format. Må være 4 siffer.");
    }
    
    // 2. Opprett databasetilkobling med gjest-rolle
    $conn = get_db_connection('guest');
    
    // 3. Verifiser PIN-kode og hent emne- og foreleserinformasjon
    $stmt = $conn->prepare("CALL verify_course_pin(?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av spørring: " . $conn->error);
    }
    
    $stmt->bind_param("s", $pin);
    $stmt->execute();
    
    // Håndter multiple resultsets
    do {
        if ($result = $stmt->get_result()) {
            $data = $result->fetch_assoc();
            $result->free();
        }
    } while ($stmt->more_results() && $stmt->next_result());
    
    if (!$data) {
        throw new Exception("Feil PIN-kode.");
    }
    
    $stmt->close();
    
    // 4. Generer en unik gjeste-ID basert på timestamp og IP
    $ip = $_SERVER['REMOTE_ADDR'];
    $guest_id = 'guest_' . time() . '_' . substr(md5($ip), 0, 8);
    
    // 5. Lagre informasjon i sesjonsvariabler
    $_SESSION['gjest_id'] = $guest_id;
    $_SESSION['emne_id'] = $data['emne_id'];
    $_SESSION['emne_navn'] = $data['emne_navn'];
    $_SESSION['emne_kode'] = $data['emne_kode'];
    $_SESSION['pin_kode'] = $pin;
    $_SESSION['foreleser_fornavn'] = $data['foreleser_fornavn'];
    $_SESSION['foreleser_etternavn'] = $data['foreleser_etternavn'];
    $_SESSION['foreleser_bilde'] = $data['foreleser_bilde'];
    $_SESSION['user_type'] = 'guest';
    
    // 6. Lukk databasetilkoblingen
    $conn->close();
    
    // 7. Omdiriger til gjeste-dashboard
    header("Location: dashboard_gjest.php");
    exit();
    
} catch (Exception $e) {
    // Logg feilen
    error_log("Feil i login_gjest.php: " . $e->getMessage());
    
    // Lukk databasetilkoblingen hvis den eksisterer
    if (isset($conn)) {
        $conn->close();
    }
    
    // Omdiriger til login-siden med feilmelding
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../pages/login.php");
    exit();
}
?>
