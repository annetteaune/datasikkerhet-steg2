<?php
// Aktiver feilrapportering for debugging (fjern i produksjon)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konfigurer sikre session-parametre før session start
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session
session_start();

require 'db.php';
require_once 'registration_attempts.php';

try {
	// Hent klientens IP-adresse
	$ip = $_SERVER['REMOTE_ADDR'];
	
	// Sjekk registreringsrate-begrensning
	$limit_check = check_registration_limit($ip);
	if ($limit_check['limited']) {
		$remaining_minutes = ceil($limit_check['remaining_time'] / 60);
		throw new Exception("For mange registreringsforsøk. Vennligst vent {$remaining_minutes} minutter før du prøver igjen.");
	}
	
	// Debug: Log POST data
	error_log("POST data received: " . print_r($_POST, true));
	
	// Hent og valider input
	$fornavn = trim($_POST['fornavn'] ?? '');
	$etternavn = trim($_POST['etternavn'] ?? '');
	$epost = trim($_POST['epost'] ?? '');
	$passord = $_POST['passord'] ?? '';
	$bekreft_passord = $_POST['bekreft_passord'] ?? '';
	$emne_navn = trim($_POST['emne_navn'] ?? '');
	$emne_kode = trim($_POST['emne_kode'] ?? '');
	$pin_kode = trim($_POST['pin_kode'] ?? '');
	
	// Debug: Logg prosesserte input
	error_log("Prosesserte input: fornavn='$fornavn', etternavn='$etternavn', epost='$epost', emne_navn='$emne_navn', emne_kode='$emne_kode', pin_kode='$pin_kode'");
	
	// Grunnleggende validering
	if (empty($fornavn) || empty($etternavn) || empty($epost) || empty($passord) || 
		empty($bekreft_passord) || empty($emne_navn) || empty($emne_kode) || empty($pin_kode)) {
		$missing_fields = [];
		if (empty($fornavn)) $missing_fields[] = 'fornavn';
		if (empty($etternavn)) $missing_fields[] = 'etternavn';
		if (empty($epost)) $missing_fields[] = 'epost';
		if (empty($passord)) $missing_fields[] = 'passord';
		if (empty($bekreft_passord)) $missing_fields[] = 'bekreft_passord';
		if (empty($emne_navn)) $missing_fields[] = 'emne_navn';
		if (empty($emne_kode)) $missing_fields[] = 'emne_kode';
		if (empty($pin_kode)) $missing_fields[] = 'pin_kode';
		
		error_log("Missing fields: " . implode(', ', $missing_fields));
		throw new Exception("Vennligst fyll ut alle felt.");
	}
	
	// Valider e-postformat
	if (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Ugyldig e-postadresse.");
	}
	
	// Valider passordstyrke
	$password_validation = validate_password($passord);
	if (!$password_validation['valid']) {
		throw new Exception($password_validation['message']);
	}
	
	// Sjekk om passordene stemmer
	if ($passord !== $bekreft_passord) {
		throw new Exception("Passordene stemmer ikke overens.");
	}
	
	// Valider PIN-kode
	if (!preg_match('/^[0-9]{4}$/', $pin_kode)) {
		throw new Exception("PIN-koden må være 4 siffer.");
	}
	
	// Håndter filopplasting hvis den er gitt
	$bilde_path = null;
	if (isset($_FILES['bilde']) && $_FILES['bilde']['error'] === UPLOAD_ERR_OK) {
		$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
		$file_type = $_FILES['bilde']['type'];
		
		if (!in_array($file_type, $allowed_types)) {
			throw new Exception("Ugyldig filtype. Kun JPG, PNG og GIF er tillatt.");
		}
		
		$unique_filename = uniqid() . '-' . basename($_FILES['bilde']['name']);
		$upload_path = '../img/' . $unique_filename;
		
		if (!move_uploaded_file($_FILES['bilde']['tmp_name'], $upload_path)) {
			throw new Exception("Kunne ikke laste opp bildet.");
		}
		
		$bilde_path = $unique_filename;
	}
	
	// Hent databaseforbindelse
	$conn = get_db_connection('guest');
	$conn->begin_transaction();
	
	try {
		// Sjekk om e-post allerede eksisterer
		$stmt = $conn->prepare("SELECT foreleser_id FROM foreleser WHERE epost = ?");
		if (!$stmt) {
			throw new Exception("Database query failed: " . $conn->error);
		}
		
		$stmt->bind_param("s", $epost);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			throw new Exception("Denne e-postadressen er allerede registrert.");
		}
		
		// Sjekk om emne_kode allerede eksisterer
		$stmt = $conn->prepare("SELECT emne_kode FROM emner WHERE emne_kode = ?");
		$stmt->bind_param("s", $emne_kode);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			throw new Exception("Denne emnekoden er allerede i bruk.");
		}
		
		// Hash passord med Argon2
		$hashed_password = password_hash($passord, PASSWORD_ARGON2ID, [
			'memory_cost' => 65536,
			'time_cost' => 4,
			'threads' => 3
		]);
		
		// Sett inn ny foreleser
		$stmt = $conn->prepare("INSERT INTO foreleser (fornavn, etternavn, epost, passord, bilde) VALUES (?, ?, ?, ?, ?)");
		if (!$stmt) {
			throw new Exception("Database query failed: " . $conn->error);
		}
		
		$stmt->bind_param("sssss", $fornavn, $etternavn, $epost, $hashed_password, $bilde_path);
		
		if (!$stmt->execute()) {
			throw new Exception("Kunne ikke registrere foreleser: " . $stmt->error);
		}
		
		$foreleser_id = $conn->insert_id;
		
		// Sett inn nytt emne
		$stmt = $conn->prepare("INSERT INTO emner (emne_navn, emne_kode, pin_kode) VALUES (?, ?, ?)");
		if (!$stmt) {
			throw new Exception("Database query failed: " . $conn->error);
		}
		
		$stmt->bind_param("sss", $emne_navn, $emne_kode, $pin_kode);
		
		if (!$stmt->execute()) {
			throw new Exception("Kunne ikke registrere emne: " . $stmt->error);
		}
		
		$emne_id = $conn->insert_id;
		
		// Opprett forhold mellom foreleser og emne
		$stmt = $conn->prepare("INSERT INTO foreleser_emner (foreleser_id, emne_id) VALUES (?, ?)");
		if (!$stmt) {
			throw new Exception("Database query failed: " . $conn->error);
		}
		
		$stmt->bind_param("ii", $foreleser_id, $emne_id);
		
		if (!$stmt->execute()) {
			throw new Exception("Kunne ikke knytte foreleser til emne: " . $stmt->error);
		}
		
		// Commit transaksjon
		$conn->commit();
		
		// Lukk databaseforbindelser
		$stmt->close();
		$conn->close();
		
		// Sett success-melding og omdiriger
		$_SESSION['success_message'] = "Registrering vellykket! Du kan nå logge inn.";
		header("Location: ../pages/foreleser_login.php");
		exit();
		
	} catch (Exception $e) {
		// Rollback transaksjon ved error
		$conn->rollback();
		throw $e;
	}
	
} catch (Exception $e) {
	error_log("Registration error: " . $e->getMessage());
	
	// Lukk databaseforbindelser hvis de eksisterer
	if (isset($stmt)) $stmt->close();
	if (isset($conn)) $conn->close();
	
	// Lagre formdata og feilmelding i session
	$_SESSION['form_data'] = [
		'fornavn' => $fornavn ?? '',
		'etternavn' => $etternavn ?? '',
		'epost' => $epost ?? '',
		'emne_navn' => $emne_navn ?? '',
		'emne_kode' => $emne_kode ?? '',
		'pin_kode' => $pin_kode ?? ''
	];
	$_SESSION['error_message'] = $e->getMessage();
	
	// Omdiriger tilbake til registreringsform
	header("Location: ../pages/registrer_foreleser.php");
	exit();
}
?>

