<?php

/**
 * API Entry Point
 *
 * This file handles all API requests and routes them to appropriate handlers.
 *
 * PHP version 7.4
 *
 * @category   API
 * @package    CleanSteg1
 * @subpackage API
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

// Slå av i produksjon
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once __DIR__ . '/../lib/security_headers.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/API.php';
require_once __DIR__ . '/includes/APIFunctions.php';
require_once __DIR__ . '/includes/APIConstants.php';
require_once '../lib/rate_limiter.php';

use CleanSteg1\Database\Database;
use CleanSteg1\API\API;
use CleanSteg1\API\isAuthenticated;
use CleanSteg1\API\getMessages;
use function CleanSteg1\Security\applyRateLimit;
use CleanSteg1\API\{
    API_MAX_REQUESTS,
    API_TIME_WINDOW,
    API_BLOCK_TIME,
    MAX_REQUESTS,
    TIME_WINDOW,
    BLOCK_TIME
};

// Sett security headers
setSecurityHeaders();
// Sett CORS headers for API endepunkter
setCORSHeaders(true);

// Sørg for riktig innkoding
mb_internal_encoding('UTF-8');

// Sett riktig innholdstype og tegnsett
header('Content-Type: application/json; charset=utf-8');

// Tillat CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$db = new Database();
$conn = $db->getConnection('api');

$api = new API();
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$subresource = isset($_GET['subresource']) ? $_GET['subresource'] : '';

applyRateLimit();

error_log("Method: " . $method);
error_log("Endpoint: " . $endpoint);
error_log("Subresource: " . $subresource);

// Håndter CORS preflight-forespørsler
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
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

                $response = \CleanSteg1\API\getMessages($conn, [
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

            // Check authentication for courses endpoint
            if (!\CleanSteg1\API\isAuthenticated()) {
                throw new Exception('Du må være logget inn for å se emnelisten');
            }

            $response = $api->getAvailableCourses();
            break;

        case 'password':
            if ($method !== 'POST' || $subresource !== 'change') {
                throw new Exception('Ugyldig metode eller subresource for passord-endepunkt');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (
                !isset($data['user_id']) || !isset($data['old_password']) ||
                !isset($data['new_password']) || !isset($data['user_type'])
            ) {
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

    // Sørg for riktig JSON-koding av spesialtegn
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
} catch (Exception $e) {
    error_log("API Feil: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
