<?php

session_start();
require_once 'db.php';

use CleanSteg1\Database\Database;

try {
    // Hent POST data
    $email = $_POST['email'] ?? '';
    $emne_kode = $_POST['emne_kode'] ?? '';
    $pin_kode = $_POST['pin_kode'] ?? '';

    // Valider input
    if (empty($email) || empty($emne_kode) || empty($pin_kode)) {
        header("Location: glemt_passord.php?tomme_felt=Alle felt må fylles ut");
        exit();
    }

    // Opprett databasetilkobling
    $conn = Database::getConnection('guest');

    // Sjekk om foreleser eksisterer og matcher med emne
    $stmt = $conn->prepare("SELECT f.foreleser_id, f.epost 
                           FROM foreleser f 
                           INNER JOIN foreleser_emner fe ON f.foreleser_id = fe.foreleser_id
                           INNER JOIN emner e ON fe.emne_id = e.emne_id 
                           WHERE f.epost = ? AND e.emne_kode = ? AND e.pin_kode = ?");

    $stmt->bind_param("sss", $email, $emne_kode, $pin_kode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: glemt_passord.php?tomme_felt=Ingen match funnet med oppgitt informasjon");
        exit();
    }

    // Generer nytt tilfeldig passord
    $nytt_passord = bin2hex(random_bytes(8)); // 16 tegn langt

    // Hash det nye passordet
    $passord_hash = password_hash($nytt_passord, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,  // 64MB
        'time_cost' => 4,        // 4 iterations
        'threads' => 3           // 3 threads
    ]);

    // Valider passord
    $password_validation = Database::validatePassword($nytt_passord);
    if (!$password_validation['valid']) {
        $_SESSION['error'] = $password_validation['message'];
        header("Location: ../pages/error.php");
        exit();
    }

    // Oppdater passordet i databasen
    $foreleser = $result->fetch_assoc();
    $update_stmt = $conn->prepare("UPDATE foreleser SET passord = ? WHERE foreleser_id = ?");
    $update_stmt->bind_param("si", $passord_hash, $foreleser['foreleser_id']);

    if (!$update_stmt->execute()) {
        throw new Exception("Kunne ikke oppdatere passord");
    }

    // Send det nye passordet til brukeren (i praksis ville dette vært via e-post)
    $melding = "Ditt nye passord er: " . $nytt_passord . "\nVennligst bytt dette passordet når du logger inn.";

    // Lukk databasetilkoblinger
    $stmt->close();
    $update_stmt->close();
    $conn->close();

    // Lukk databaseforbindelse
    Database::closeConnection($conn);

    // Redirect med suksessmelding
    header("Location: glemt_passord.php?pw_byttet=" . urlencode($melding));
    exit();
} catch (Exception $e) {
    error_log("Feil i tilbakestill_passord.php: " . $e->getMessage());

    // Lukk databasetilkoblinger hvis de eksisterer
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($update_stmt)) {
        $update_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }

    // Lukk databaseforbindelse
    Database::closeConnection($conn);

    header("Location: glemt_passord.php?tomme_felt=" . urlencode("En feil oppstod: " . $e->getMessage()));
    exit();
}
