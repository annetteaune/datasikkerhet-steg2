<?php
// Deaktiver feilmeldinger for varsler og advarsler i produksjon
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/API.php';
require_once '../lib/rate_limiter.php';

$db = new Database();
$conn = $db->getConnection('api');

$api = new API();
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$subresource = isset($_GET['subresource']) ? $_GET['subresource'] : '';

// Konfigurer mer restriktive grenser for API-endepunkter
define('API_MAX_REQUESTS', 30);  // max requests per vindu
define('API_TIME_WINDOW', 60);   // tid i sekunder
define('API_BLOCK_TIME', 300);   // blokkeringstid i sekunder, 300s=5min

// Overstyr standard rate limit-konstanter for API-endepunkter
define('MAX_REQUESTS', API_MAX_REQUESTS);
define('TIME_WINDOW', API_TIME_WINDOW);
define('BLOCK_TIME', API_BLOCK_TIME);

apply_rate_limit();

error_log("Method: " . $method);
error_log("Endpoint: " . $endpoint);
error_log("Subresource: " . $subresource);

// Håndter CORS preflight-forespørsler
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sjekk om brukeren er autentisert for beskyttede endepunkter
function isAuthenticated() {
    session_start();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function getMessages($conn, $params) {
    try {
        // Valider påkrevde parametere
        if (!isset($params['emne_kode']) || !isset($params['pin_kode'])) {
            return array(
                'status' => 'error',
                'message' => 'Mangler påkrevde parametere (emne_kode og pin_kode)'
            );
        }

        // Hent emne_id basert på emne_kode
        $stmt = $conn->prepare("SELECT emne_id FROM emner WHERE emne_kode = ?");
        if (!$stmt) {
            throw new Exception("Feil ved forberedelse av emne-spørring: " . $conn->error);
        }

        $stmt->bind_param("s", $params['emne_kode']);
        if (!$stmt->execute()) {
            throw new Exception("Feil ved utførelse av emne-spørring: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $emne = $result->fetch_assoc();
        $stmt->close();

        if (!$emne) {
            return array(
                'status' => 'error',
                'message' => 'Emne ikke funnet'
            );
        }

        // Kall prosedyren med emne_id og pin_kode
        $stmt = $conn->prepare("CALL get_course_messages(?, ?)");
        if (!$stmt) {
            throw new Exception("Feil ved forberedelse av meldingsprosedyre: " . $conn->error);
        }

        $stmt->bind_param("is", $emne['emne_id'], $params['pin_kode']);
        if (!$stmt->execute()) {
            throw new Exception("Feil ved utførelse av meldingsprosedyre: " . $stmt->error);
        }

        // Første resultat er status
        $result = $stmt->get_result();
        $status = $result->fetch_assoc();

        if ($status['result'] === 'ERROR') {
            return array(
                'status' => 'error',
                'message' => $status['message']
            );
        }

        // Neste resultat er meldingene
        $stmt->next_result();
        $result = $stmt->get_result();
        $messages = array();
        
        while ($row = $result->fetch_assoc()) {
            $messages[] = array(
                'melding_id' => $row['melding_id'],
                'melding_innhold' => $row['melding_innhold'],
                'melding_tidspunkt' => $row['melding_tidspunkt'],
                'student_navn' => 'Anonym student',
                'emne_navn' => $row['emne_navn'],
                'emne_kode' => $row['emne_kode'],
                'svar' => $row['svar_id'] ? array(
                    'svar_id' => $row['svar_id'],
                    'innhold' => $row['svar_innhold'],
                    'tidspunkt' => $row['svar_tidspunkt'],
                    'foreleser_navn' => $row['foreleser_fornavn'] . ' ' . $row['foreleser_etternavn']
                ) : null
            );
        }

        $stmt->close();

        return array(
            'status' => 'success',
            'data' => $messages
        );

    } catch (Exception $e) {
        error_log("Feil i getMessages: " . $e->getMessage());
        return array(
            'status' => 'error',
            'message' => 'En feil oppstod ved henting av meldinger: ' . $e->getMessage()
        );
    }
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
                $emne_kode = isset($_GET['emne_kode']) ? $_GET['emne_kode'] : null;
                $pin_kode = isset($_GET['pin_kode']) ? $_GET['pin_kode'] : null;
                
                if (!$emne_kode || !$pin_kode) {
                    throw new Exception('Mangler påkrevde parametere (emne_kode og pin_kode)');
                }
                
                $response = getMessages($conn, [
                    'emne_kode' => $emne_kode,
                    'pin_kode' => $pin_kode
                ]);
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