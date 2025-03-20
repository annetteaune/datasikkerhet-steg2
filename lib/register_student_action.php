<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';

try {
	// Validate form data
	$fname = trim($_POST['fornavn'] ?? '');
	$lname = trim($_POST['etternavn'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = trim($_POST['passord'] ?? '');
	$confirm_password = trim($_POST['bekreft_passord'] ?? '');

	$errors = [];

	// Validate required fields
	if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password)) {
		$errors[] = "Alle felt må fylles ut.";
	}

	// Validate password match
	if ($password !== $confirm_password) {
		$errors[] = "Passordene må være like.";
	}

	// Validate password length
	if (strlen($password) < 8) {
		$errors[] = "Passordet må være minst 8 tegn langt.";
	}

	// If there are validation errors, redirect back with error messages
	if (!empty($errors)) {
		$_SESSION['error_messages'] = $errors;
		header("Location: ../pages/registrer_student.php?error=1");
		exit();
	}

	// Get database connection with admin role (needed for registration)
	$conn = get_db_connection('admin');

	// Start transaction
	$conn->begin_transaction();

	try {
		// Hash the password
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		error_log("Generated password hash: " . $hashed_password);

		// Call the register_student stored procedure
		$stmt = $conn->prepare("CALL register_student(?, ?, ?, ?)");
		if (!$stmt) {
			throw new Exception("Database query failed: " . $conn->error);
		}

		$stmt->bind_param("ssss", $fname, $lname, $email, $hashed_password);
		error_log("Attempting to register student with email: " . $email);
		$stmt->execute();
		
		// Store all results to prevent "Commands out of sync" error
		do {
			if ($result = $stmt->get_result()) {
				$response = $result->fetch_assoc();
				$result->free();
			}
		} while ($stmt->more_results() && $stmt->next_result());

		if (!isset($response) || !$response) {
			throw new Exception("Kunne ikke hente resultat fra registreringsprosedyren");
		}

		if ($response['result'] === 'SUCCESS') {
			// Commit transaction
			$conn->commit();
			
			// Close resources
			$stmt->close();
			close_db_connection($conn);

			// Set success message and redirect
			$_SESSION['success_message'] = "Registrering vellykket! Du kan nå logge inn.";
			header("Location: ../pages/login.php?registration=success");
			exit();
		} else {
			throw new Exception($response['message'] ?? "En feil oppstod under registrering.");
		}

	} catch (Exception $e) {
		// Rollback transaction
		$conn->rollback();
		throw $e;
	}

} catch (Exception $e) {
	// Log error and show user-friendly message
	error_log("Registration error: " . $e->getMessage());
	$_SESSION['error_message'] = $e->getMessage();
	header("Location: ../pages/registrer_student.php?error=1");
	exit();
}
?>

