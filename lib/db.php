<?php 
// For å se feilmeldinger
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database konfigurasjon
$host = "localhost";
$dbname = "db2";

// Brukerroller og tilgangsinformasjon
$db_users = [
    'admin' => [
        'username' => 'db2_admin',
        'password' => 'StrongAdminPassword123!'
    ],
    'student' => [
        'username' => 'db2_student',
        'password' => 'StudentPassword123!'
    ],
    'lecturer' => [
        'username' => 'db2_lecturer',
        'password' => 'LecturerPassword123!'
    ],
    'guest' => [
        'username' => 'db2_guest',
        'password' => 'GuestPassword123!'
    ]
];

// Funksjon for å opprette databasetilkobling basert på rolle
function get_db_connection($role = 'guest') {
    global $host, $dbname, $db_users;
    
    if (!array_key_exists($role, $db_users)) {
        $role = 'guest'; // Standard rolle hvis ugyldig rolle er spesifisert
    }
    
    $user = $db_users[$role]['username'];
    $password = $db_users[$role]['password'];
    
    $conn = new mysqli($host, $user, $password, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed. Please try again later.");
    }
    
    return $conn;
}

// Funksjon for å lukke databasetilkobling
function close_db_connection($conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}

/**
 * Validerer passordstyrke etter sikkerhetskrav
 * - Minimum 8 tegn
 * - Minst én stor bokstav
 * - Minst étt tall
 * - Minst étt spesialtegn
 * 
 * @param string $password Passordet som skal valideres
 * @return array Array med 'valid' boolean og 'message' string
 */
function validate_password($password) {
    $errors = [];
    
    // Sjekk minimum lengde
    if (strlen($password) < 8) {
        $errors[] = "Passordet må være minst 8 tegn langt.";
    }
    
    // Sjekk for stor bokstav
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Passordet må inneholde minst én stor bokstav.";
    }
    
    // Sjekk for tall
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Passordet må inneholde minst ett tall.";
    }
    
    // Sjekk for spesialtegn
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Passordet må inneholde minst ett spesialtegn.";
    }
    
    return [
        'valid' => empty($errors),
        'message' => empty($errors) ? '' : implode('<br>', $errors)
    ];
}
?>
