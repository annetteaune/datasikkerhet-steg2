<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

require 'db.php';

try {
	// Validate form data
	$fname = trim($_POST['fname'] ?? '');
	$lname = trim($_POST['lname'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = trim($_POST['password'] ?? '');
	$confirm_password = trim($_POST['confirm_password'] ?? '');
	$subject_name = trim($_POST['subject'] ?? '');
	$subject_pin = trim($_POST['pin'] ?? '');
	$img = $_FILES['profile_picture'] ?? null;

	$errors = [];

	// Validate required fields
	if (empty($fname) || empty($lname) || empty($email) || empty($password) || 
		empty($confirm_password) || empty($subject_name) || empty($subject_pin)) {
		$errors[] = "Alle felt må fylles ut.";
	}

	// Validate PIN format
	if (!preg_match('/^[0-9]{4}$/', $subject_pin)) {
		$errors[] = "PIN-koden må bestå av 4 siffer.";
	}

	// Validate password match
	if ($password !== $confirm_password) {
		$errors[] = "Passordene må være like.";
	}

	// Validate password length and complexity
	if (strlen($password) < 8) {
		$errors[] = "Passordet må være minst 8 tegn langt.";
	}

	// Validate email format
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = "Ugyldig e-postadresse.";
	}

	// Validate and process image
	$serverFilePath = null;
	if ($img && $img['error'] === UPLOAD_ERR_OK) {
		$imgTmpPath = $img['tmp_name'];
		$imgMimeType = mime_content_type($imgTmpPath);
		$validTypes = ['image/jpeg', 'image/png', 'image/gif'];

		if (!in_array($imgMimeType, $validTypes)) {
			$errors[] = "Ugyldig filtype. Kun JPEG, PNG og GIF er tillatt.";
		} else {
			// Process image
			$imgDir = '../img/';
			
			// Ensure the directory exists and is writable
			if (!is_dir($imgDir)) {
				mkdir($imgDir, 0777, true);
			}
			
			if (!is_writable($imgDir)) {
				error_log("Image directory is not writable: " . $imgDir);
				throw new Exception("Kunne ikke laste opp bildet. Kontakt administrator.");
			}

			$uniqName = uniqid() . '-' . basename($img['name']);
			$serverFilePath = $imgDir . $uniqName;

			if (!move_uploaded_file($imgTmpPath, $serverFilePath)) {
				error_log("Failed to move uploaded file from {$imgTmpPath} to {$serverFilePath}");
				$errors[] = "Kunne ikke laste opp bildet. Vennligst prøv igjen.";
			}
		}
	} else {
		$uploadError = $img ? $img['error'] : 'No file uploaded';
		error_log("Image upload error: " . $uploadError);
		$errors[] = "Profilbilde er påkrevd.";
	}

	// If there are validation errors, redirect back with error messages
	if (!empty($errors)) {
		$_SESSION['error_messages'] = $errors;
		header("Location: ../pages/registrer_foreleser.php?error=1");
		exit();
	}

	// Get database connection with guest role (since non-logged in users are guests)
	$conn = get_db_connection('guest');

	// Check if PIN already exists
	$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM emner WHERE pin_kode = ?");
	if (!$check_stmt) {
		throw new Exception("Database query failed: " . $conn->error);
	}

	$check_stmt->bind_param("s", $subject_pin);
	$check_stmt->execute();
	$check_result = $check_stmt->get_result();
	$pin_count = $check_result->fetch_assoc()['count'];
	$check_stmt->close();

	if ($pin_count > 0) {
		throw new Exception("PIN-koden er allerede i bruk. Vennligst velg en annen PIN-kode.");
	}

	// Hash the password
	$hashed_password = password_hash($password, PASSWORD_DEFAULT);

	// Generate course code
	$prefix = strtoupper(substr($subject_name, 0, 4));
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomLetters = '';
	for ($i = 0; $i < 4; $i++) {
		$randomLetters .= $characters[rand(0, strlen($characters) - 1)];
	}
	$currentYear = date('Y');
	$subject_code = $prefix . $randomLetters . $currentYear;

	// Start transaction
	$conn->begin_transaction();

	try {
		// Call the register_lecturer_with_course stored procedure
		$stmt = $conn->prepare("CALL register_lecturer_with_course(?, ?, ?, ?, ?, ?, ?, ?)");
		if (!$stmt) {
			throw new Exception("Database query failed: " . $conn->error);
		}

		$stmt->bind_param("ssssssss", 
			$fname, 
			$lname, 
			$email, 
			$hashed_password, 
			$serverFilePath,
			$subject_name,
			$subject_code,
			$subject_pin
		);
		
		$stmt->execute();
		
		// Store the result to prevent "Commands out of sync" error
		do {
			if ($result = $stmt->get_result()) {
				$response = $result->fetch_assoc();
				$result->free();
			}
		} while ($stmt->more_results() && $stmt->next_result());

		if (!isset($response) || !$response) {
			throw new Exception("Failed to get result from registration procedure");
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
	// If there was an error and an image was uploaded, delete it
	if (isset($serverFilePath) && file_exists($serverFilePath)) {
		unlink($serverFilePath);
	}

	// Log error and show user-friendly message
	error_log("Registration error: " . $e->getMessage());
	$_SESSION['error_message'] = $e->getMessage();
	header("Location: ../pages/registrer_foreleser.php?error=1");
	exit();
}
?>

