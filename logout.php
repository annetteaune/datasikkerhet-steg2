<?php
session_start();
session_unset();
session_destroy();

// Omdiriger til login-side etter logout
header("Location: ../pages/login.php");
exit();
?>
