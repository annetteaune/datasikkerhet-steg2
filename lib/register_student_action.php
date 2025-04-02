<?php
// Configure secure session parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
require_once 'db.php';
require_once 'registration_attempts.php';

try {
	// Get client IP
	$ip = $_SERVER['REMOTE_ADDR'];
	
	// Check registration rate limit
	$limit_check = check_registration_limit($ip);
	if ($limit_check['limited']) {
		$remaining_minutes = ceil($limit_check['remaining_time'] / 60);
		throw new Exception("For mange registreringsforsøk. Vennligst vent {$remaining_minutes} minutter før du prøver igjen.");
	}
	
	// Validate required fields
	$required_fields = ['fornavn', 'etternavn', 'epost', 'passord', 'bekreft_passord'];
	foreach ($required_fields as $field) {
		if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
			throw new Exception("Alle felt må fylles ut.");
		}
	}
	
	// Sanitize and validate input
	$fornavn = trim($_POST['fornavn']);
	$etternavn = trim($_POST['etternavn']);
	$epost = trim($_POST['epost']);
	$passord = $_POST['passord'];
	$bekreft_passord = $_POST['bekreft_passord'];
	
	// Validate email format
	if (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Ugyldig e-postadresse.");
	}
	
	// Validate password strength
	if (!validate_password($passord)) {
		throw new Exception("Passordet må være minst 8 tegn langt og inneholde minst én stor bokstav, ett tall og ett spesialtegn.");
	}
	
	// Check if passwords match
	if ($passord !== $bekreft_passord) {
		throw new Exception("Passordene stemmer ikke overens.");
	}
	
	// Get database connection
	$conn = get_db_connection('guest');
	
	// Start transaction
	$conn->begin_transaction();
	
	try {
		// Check if email already exists
		$stmt = $conn->prepare("SELECT student_id FROM studenter WHERE epost = ?");
		$stmt->bind_param("s", $epost);
		$stmt->execute();
		if ($stmt->get_result()->num_rows > 0) {
			throw new Exception("E-postadressen er allerede i bruk.");
		}
		$stmt->close();
		
		// Hash password
		$hashed_password = password_hash($passord, PASSWORD_ARGON2ID);
		
		// Insert student - only using the columns that exist in the table
		$stmt = $conn->prepare("INSERT INTO studenter (fornavn, etternavn, epost, passord) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("ssss", $fornavn, $etternavn, $epost, $hashed_password);
		$stmt->execute();
		$stmt->close();
		
		// Record successful registration
		record_registration_attempt($ip);
		
		// Commit transaction
		$conn->commit();
		
		// Set success message
		$_SESSION['success'] = "Registrering vellykket! Du kan nå logge inn.";
		header("Location: ../pages/student_login.php");
		exit();
		
	} catch (Exception $e) {
		// Rollback transaction on error
		$conn->rollback();
		throw $e;
	}
	
} catch (Exception $e) {
	// Log error
	error_log("Registration error: " . $e->getMessage());
	
	// Close database connection if it exists
	if (isset($conn)) {
		$conn->close();
	}
	
	// Store error message and form data in session
	$_SESSION['error'] = $e->getMessage();
	$_SESSION['form_data'] = $_POST;
	
	// Redirect back to registration form
	header("Location: ../pages/registrer_student.php");
	exit();
}
?>

