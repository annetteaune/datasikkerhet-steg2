<?php 
// for  se feilmeldinger
error_reporting(E_ALL);
ini_set('display_errors', 1);

//db login
$host = "localhost";
$user = "root";
$password = "";
$dbname = "database";

//db kobling 
$conn = new mysqli($host, $user, $password, $dbname);

//kobling test

if ($conn->connect_error){
    echo "Connection failed: " . $conn->connect_error;
    exit; // Stop script execution
}


?>
