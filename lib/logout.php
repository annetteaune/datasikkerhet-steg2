<?php
// Konfigurer sikre session-parametre før session start
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Fjern alle session-variabler
$_SESSION = array();

// Fjernsession-cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

session_destroy();

// Omdiriger til login-side etter logout
header("Location: ../pages/login.php");
exit();
?>
