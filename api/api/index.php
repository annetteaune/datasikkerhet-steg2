<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/API.php';

use CleanSteg1\API\API;

$api = new API();
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$subresource = isset($_GET['subresource']) ? $_GET['subresource'] : '';

error_log("Method: " . $method);
error_log("Endpoint: " . $endpoint);
error_log("Subresource: " . $subresource);

try {
    if ($method === 'GET') {
        switch ($endpoint) {
            case 'student':
                if (!isset($_GET['id'])) {
                    throw new Exception('Mangler student ID');
                }
                echo json_encode($api->getStudent($_GET['id']));
                break;

            case 'lecturer':
                if (!isset($_GET['id'])) {
                    throw new Exception('Mangler foreleser ID');
                }
                echo json_encode($api->getLecturer($_GET['id']));
                break;

            case 'courses':
                echo json_encode($api->getAvailableCourses());
                break;

            case 'messages':
                if (!isset($_GET['emne_id']) || !isset($_GET['pin_kode'])) {
                    throw new Exception('Mangler påkrevde parametre: emne_id og pin_kode');
                }
                echo json_encode($api->getMessages(
                    $_GET['emne_id'],
                    $_GET['pin_kode']
                ));
                break;

            case 'course':
                if (!isset($_GET['emne_id']) || !isset($_GET['pin_kode'])) {
                    throw new Exception('Mangler påkrevde parametre: emne_id og pin_kode');
                }
                echo json_encode($api->getCourseInfo(
                    $_GET['emne_id'],
                    $_GET['pin_kode']
                ));
                break;

            default:
                echo json_encode([
                    'status' => 'success',
                    'message' => 'API er aktiv',
                    'endpoints' => [
                        'GET /api/index.php?endpoint=courses' => 'Liste alle emner',
                        'GET /api/index.php?endpoint=course&emne_id=X&pin_kode=Y' => 'Hente emneinfo',
                        'GET /api/index.php?endpoint=messages&emne_id=X&pin_kode=Y' => 'Hente meldinger',
                        'GET /api/index.php?endpoint=student&id=X' => 'Hente studentinfo',
                        'GET /api/index.php?endpoint=lecturer&id=X' => 'Hente foreleserinfo'
                    ]
                ]);
                break;
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($endpoint === 'register') {
            if ($subresource === 'student') {
                echo json_encode($api->registerStudent(
                    $data['fornavn'],
                    $data['etternavn'],
                    $data['epost'],
                    $data['passord']
                ));
            } elseif ($subresource === 'lecturer') {
                echo json_encode($api->registerLecturer(
                    $_POST['fornavn'],
                    $_POST['etternavn'],
                    $_POST['epost'],
                    $_POST['passord'],
                    $_FILES['bilde'],
                    $_POST['emne_navn'],
                    $_POST['emne_kode'],
                    $_POST['pin_kode']
                ));
            } else {
                throw new Exception('Ugyldig registreringstype');
            }
        } elseif ($endpoint === 'password' && $subresource === 'change') {
            echo json_encode($api->changePassword(
                $data['user_id'],
                $data['old_password'],
                $data['new_password'],
                $data['user_type']
            ));
        } else {
            switch ($endpoint) {
                case 'login':
                    echo json_encode($api->login($data['email'], $data['password']));
                    break;

                case 'messages':
                    echo json_encode($api->sendMessage(
                        $data['student_id'],
                        $data['emne_id'],
                        $data['innhold']
                    ));
                    break;

                case 'response':
                    echo json_encode($api->addResponse(
                        $data['melding_id'],
                        $data['foreleser_id'],
                        $data['innhold']
                    ));
                    break;

                case 'comment':
                    echo json_encode($api->addComment(
                        $data['melding_id'],
                        $data['innhold'],
                        $_SERVER['REMOTE_ADDR']
                    ));
                    break;

                case 'report':
                    echo json_encode($api->reportMessage(
                        $data['melding_id'],
                        $data['grunn'],
                        $_SERVER['REMOTE_ADDR']
                    ));
                    break;

                default:
                    throw new Exception('Ukjent endepunkt');
            }
        }
    } else {
        throw new Exception('HTTP-metode ikke tillatt');
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
