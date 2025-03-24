<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/API.php';

$api = new API();
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$subresource = isset($_GET['subresource']) ? $_GET['subresource'] : '';

error_log("Method: " . $method);
error_log("Endpoint: " . $endpoint);
error_log("Subresource: " . $subresource);

// Håndter CORS preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sjekk om brukeren er autentisert for beskyttede endepunkter
function isAuthenticated() {
    session_start();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

try {
    switch ($endpoint) {
        case 'register':
            if ($method !== 'POST') {
                throw new Exception('Ugyldig metode for registrering');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($subresource === 'student') {
                $response = $api->registerStudent(
                    $data['fornavn'],
                    $data['etternavn'],
                    $data['epost'],
                    $data['passord']
                );
            } elseif ($subresource === 'lecturer') {
                $response = $api->registerLecturer(
                    $data['fornavn'],
                    $data['etternavn'],
                    $data['epost'],
                    $data['passord'],
                    $data['bilde'],
                    $data['emne_navn'],
                    $data['emne_kode'],
                    $data['pin_kode']
                );
            } else {
                throw new Exception('Ugyldig subresource for registrering');
            }
            break;
            
        case 'login':
            if ($method !== 'POST') {
                throw new Exception('Ugyldig metode for innlogging');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $api->login($data['email'], $data['password']);
            break;
            
        case 'student':
            if ($method !== 'GET') {
                throw new Exception('Ugyldig metode for studentendepunkt');
            }
            
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                throw new Exception('Student ID mangler');
            }
            
            $response = $api->getStudent($id);
            break;
            
        case 'lecturer':
            if ($method !== 'GET') {
                throw new Exception('Ugyldig metode for foreleserendepunkt');
            }
            
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                throw new Exception('Foreleser ID mangler');
            }
            
            $response = $api->getLecturer($id);
            break;
            
        case 'messages':
            if ($method === 'GET') {
                $emne_id = isset($_GET['emne_id']) ? $_GET['emne_id'] : null;
                $pin_kode = isset($_GET['pin_kode']) ? $_GET['pin_kode'] : null;
                
                if (!$emne_id || !$pin_kode) {
                    throw new Exception('Emne ID og PIN-kode er påkrevd');
                }
                
                $response = $api->getMessages($emne_id, $pin_kode);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['student_id']) || !isset($data['emne_id']) || !isset($data['innhold'])) {
                    throw new Exception('Student ID, emne ID og innhold er påkrevd');
                }
                
                $response = $api->sendMessage(
                    $data['student_id'],
                    $data['emne_id'],
                    $data['innhold']
                );
            } else {
                throw new Exception('Ugyldig metode for meldingsendepunkt');
            }
            break;
            
        case 'response':
            if ($method !== 'POST') {
                throw new Exception('Ugyldig metode for svar-endepunkt');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['melding_id']) || !isset($data['foreleser_id']) || !isset($data['innhold'])) {
                throw new Exception('Melding ID, foreleser ID og innhold er påkrevd');
            }
            
            $response = $api->addResponse(
                $data['melding_id'],
                $data['foreleser_id'],
                $data['innhold']
            );
            break;
            
        case 'comment':
            if ($method !== 'POST') {
                throw new Exception('Ugyldig metode for kommentar-endepunkt');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['melding_id']) || !isset($data['innhold'])) {
                throw new Exception('Melding ID og innhold er påkrevd');
            }
            
            $response = $api->addComment(
                $data['melding_id'],
                $data['innhold'],
                $_SERVER['REMOTE_ADDR']
            );
            break;
            
        case 'report':
            if ($method !== 'POST') {
                throw new Exception('Ugyldig metode for rapporteringsendepunkt');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['melding_id']) || !isset($data['grunn'])) {
                throw new Exception('Melding ID og grunn er påkrevd');
            }
            
            $response = $api->reportMessage(
                $data['melding_id'],
                $data['grunn'],
                $_SERVER['REMOTE_ADDR']
            );
            break;
            
        case 'course':
            if ($method !== 'GET') {
                throw new Exception('Ugyldig metode for emne-endepunkt');
            }
            
            $emne_id = isset($_GET['emne_id']) ? $_GET['emne_id'] : null;
            $pin_kode = isset($_GET['pin_kode']) ? $_GET['pin_kode'] : null;
            
            if (!$emne_id || !$pin_kode) {
                throw new Exception('Emne ID og PIN-kode er påkrevd');
            }
            
            $response = $api->getCourseInfo($emne_id, $pin_kode);
            break;
            
        case 'courses':
            if ($method !== 'GET') {
                throw new Exception('Ugyldig metode for emneliste-endepunkt');
            }
            
            // Sjekk autentisering for courses-endepunktet
            if (!isAuthenticated()) {
                throw new Exception('Du må være logget inn for å se emnelisten');
            }
            
            $response = $api->getAvailableCourses();
            break;
            
        case 'password':
            if ($method !== 'POST' || $subresource !== 'change') {
                throw new Exception('Ugyldig metode eller subresource for passord-endepunkt');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['user_id']) || !isset($data['old_password']) || !isset($data['new_password']) || !isset($data['user_type'])) {
                throw new Exception('Alle passordfelter er påkrevd');
            }
            
            $response = $api->changePassword(
                $data['user_id'],
                $data['old_password'],
                $data['new_password'],
                $data['user_type']
            );
            break;
            
        default:
            throw new Exception('Ugyldig endepunkt');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("API Feil: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}