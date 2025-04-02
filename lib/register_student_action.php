<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';

try {
	// Debug: Log POST data
	error_log("POST data received: " . print_r($_POST, true));
	
	// Get and validate input
	$fornavn = trim($_POST['fornavn'] ?? '');
	$etternavn = trim($_POST['etternavn'] ?? '');
	$epost = trim($_POST['epost'] ?? '');
	$passord = $_POST['passord'] ?? '';
	$bekreft_passord = $_POST['bekreft_passord'] ?? '';
	
	// Debug: Log processed input
	error_log("Processed input: fornavn='$fornavn', etternavn='$etternavn', epost='$epost'");
	
	// Basic validation
	if (empty($fornavn) || empty($etternavn) || empty($epost) || empty($passord) || empty($bekreft_passord)) {
		$missing_fields = [];
		if (empty($fornavn)) $missing_fields[] = 'fornavn';
		if (empty($etternavn)) $missing_fields[] = 'etternavn';
		if (empty($epost)) $missing_fields[] = 'epost';
		if (empty($passord)) $missing_fields[] = 'passord';
		if (empty($bekreft_passord)) $missing_fields[] = 'bekreft_passord';
		
		error_log("Missing fields: " . implode(', ', $missing_fields));
		throw new Exception("Vennligst fyll ut alle felt.");
	}
	
	// Validate email format
	if (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Ugyldig e-postadresse.");
	}
	
	// Validate password strength
	$password_validation = validate_password($passord);
	if (!$password_validation['valid']) {
		throw new Exception($password_validation['message']);
	}
	
	// Check if passwords match
	if ($passord !== $bekreft_passord) {
		throw new Exception("Passordene stemmer ikke overens.");
	}
	
	// Get database connection
	$conn = get_db_connection('guest');
	
	// Check if email already exists
	$stmt = $conn->prepare("SELECT epost FROM studenter WHERE epost = ?");
	if (!$stmt) {
		throw new Exception("Database query failed: " . $conn->error);
	}
	
	$stmt->bind_param("s", $epost);
	$stmt->execute();
	$result = $stmt->get_result();
	
	if ($result->num_rows > 0) {
		throw new Exception("Denne e-postadressen er allerede registrert.");
	}
	
	// Hash password with Argon2
	$hashed_password = password_hash($passord, PASSWORD_ARGON2ID, [
		'memory_cost' => 65536,
		'time_cost' => 4,
		'threads' => 3
	]);
	
	// Insert new student
	$stmt = $conn->prepare("INSERT INTO studenter (fornavn, etternavn, epost, passord) VALUES (?, ?, ?, ?)");
	if (!$stmt) {
		throw new Exception("Database query failed: " . $conn->error);
	}
	
	$stmt->bind_param("ssss", $fornavn, $etternavn, $epost, $hashed_password);
	
	if (!$stmt->execute()) {
		throw new Exception("Kunne ikke registrere student: " . $stmt->error);
	}
	
	// Close database connections
	$stmt->close();
	$conn->close();
	
	// Set success message and redirect
	$_SESSION['success_message'] = "Registrering vellykket! Du kan nå logge inn.";
	header("Location: ../pages/student_login.php");
	exit();
	
} catch (Exception $e) {
	error_log("Registration error: " . $e->getMessage());
	
	// Close database connections if they exist
	if (isset($stmt)) $stmt->close();
	if (isset($conn)) $conn->close();
	
	// Store form data and error message in session
	$_SESSION['form_data'] = [
		'fornavn' => $fornavn ?? '',
		'etternavn' => $etternavn ?? '',
		'epost' => $epost ?? ''
	];
	$_SESSION['error_message'] = $e->getMessage();
	
	// Redirect back to registration form
	header("Location: ../pages/registrer_student.php");
	exit();
}
?>

