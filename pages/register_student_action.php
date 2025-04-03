<?php

// for aa se feilmeldinger
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db.php';
// data fra html form
$fname = $_POST['fname'];
$lname = $_POST['lname'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
//input sjekk
if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password)) {
    die("All fields are required.");
}

if ($password !== $confirm_password) {
    die("password do not match");
}

// se om mail finnes fra for av

$sql = "Select * FROM studenter WHERE epost = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$results = $stmt->get_result();
// hvis det finnes like epost fra for av saa er num_rows mer enn 0
if ($results->num_rows > 0) {
    die("Email already registered.");
}

//passord kryptering for database php sitt egen funksjon
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
//legg til i db
$sql = "INSERT INTO studenter (fornavn, etternavn, epost, passord) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $fname, $lname, $email, $hashed_password);
if ($stmt->execute()) {
    header("Location: ../pages/login.php?" . "&student_registered_successfully=" .
        urlencode("Student registered successfully"));
} else {
    echo "Error: " . $conn->error;
}

//steng kobling
$stmt->close();
$conn->close();
