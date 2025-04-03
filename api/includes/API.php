<?php

/**
 * API Class Implementation
 *
 * This file contains the main API class that handles all API endpoints
 * and database interactions for the application.
 *
 * @category   API
 * @package    CleanSteg1
 * @subpackage API
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

namespace CleanSteg1\API;

use CleanSteg1\API\Database;
use mysqli;
use Exception;

class API
{
    private Database $db;
    private mysqli $conn;
    private string $upload_dir = '../img/';
    //   private $upload_dir = '../../img/'; på server for å samkjøre opplasningsmapper

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection('api');
    }

    /**
     * Henter studentinformasjon
     *
     * @param int $studentId Studentens ID
     * @return array Response med studentdata eller feilmelding
     */
    public function getStudent(int $studentId): array
    {
        try {
            // Bruker student_profile_view
            $stmt = $this->conn->prepare("SELECT * FROM student_profile_view WHERE student_id = ?");
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'Student ikke funnet'];
            }

            return ['status' => 'success', 'data' => $result->fetch_assoc()];
        } catch (Exception $e) {
            error_log("Feil i getStudent: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved henting av studentprofil'];
        }
    }

    /**
     * Henter foreleserinformasjon
     *
     * @param int $lecturerId Foreleserens ID
     * @return array Response med foreleserdata eller feilmelding
     */
    public function getLecturer(int $lecturerId): array
    {
        try {
            // Bruker lecturer_profile_view
            $stmt = $this->conn->prepare("SELECT * FROM lecturer_profile_view WHERE foreleser_id = ?");
            $stmt->bind_param('i', $lecturerId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'Foreleser ikke funnet'];
            }

            $data = $result->fetch_assoc();

            // Hent emner for foreleseren
            $stmt = $this->conn->prepare("SELECT * FROM lecturer_courses_view WHERE foreleser_id = ?");
            $stmt->bind_param('i', $lecturerId);
            $stmt->execute();
            $courses = $stmt->get_result();

            $data['emner'] = [];
            while ($course = $courses->fetch_assoc()) {
                $data['emner'][] = $course;
            }

            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            error_log("Feil i getLecturer: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved henting av foreleserprofil'];
        }
    }

    /**
     * Registrerer en ny student
     *
     * @param string $fornavn Studentens fornavn
     * @param string $etternavn Studentens etternavn
     * @param string $epost Studentens epost
     * @param string $passord Studentens passord
     * @return array Response med status og melding
     */
    public function registerStudent(string $fornavn, string $etternavn, string $epost, string $passord): array
    {
        try {
            // Bruker lagret prosedyre register_student
            $stmt = $this->conn->prepare("CALL register_student(?, ?, ?, ?)");
            $stmt->bind_param('ssss', $fornavn, $etternavn, $epost, $passord);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Student registrert'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i registerStudent: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved registrering av student'];
        }
    }

    /**
     * Registrerer en ny foreleser med emne
     *
     * @param string $fornavn Foreleserens fornavn
     * @param string $etternavn Foreleserens etternavn
     * @param string $epost Foreleserens epost
     * @param string $passord Foreleserens passord
     * @param array $bilde Foreleserens bilde
     * @param string $emne_navn Emnenavn
     * @param string $emne_kode Emnekode
     * @param string $pin_kode PIN-kode for emnet
     * @return array Response med status og melding
     */
    public function registerLecturer(
        string $fornavn,
        string $etternavn,
        string $epost,
        string $passord,
        array $bilde,
        string $emne_navn,
        string $emne_kode,
        string $pin_kode
    ): array {
        try {
            $this->conn->begin_transaction();

            // Håndter bildeopplasting
            $bilde_path = $this->handleImageUpload($bilde);

            // Bruker lagret prosedyre register_lecturer_with_course
            $stmt = $this->conn->prepare("CALL register_lecturer_with_course(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'ssssssss',
                $fornavn,
                $etternavn,
                $epost,
                $passord,
                $bilde_path,
                $emne_navn,
                $emne_kode,
                $pin_kode
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                $this->conn->commit();
                return ['status' => 'success', 'message' => 'Foreleser og emne registrert'];
            }

            $this->conn->rollback();
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Feil i registerLecturer: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved registrering av foreleser'];
        }
    }

    /**
     * Behandler forespørsel om tilbakestilling av passord
     *
     * @param string $email Brukerens epost
     * @return array Response med status og melding
     */
    public function requestPasswordReset(string $email): array
    {
        try {
            $stmt = $this->conn->prepare("CALL request_password_reset(?)");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Instruksjoner for tilbakestilling av passord er sendt'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i requestPasswordReset: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved forespørsel om tilbakestilling av passord'];
        }
    }

    /**
     * Endrer passord for en bruker
     *
     * @param int $user_id Brukerens ID
     * @param string $old_password Gammelt passord
     * @param string $new_password Nytt passord
     * @param string $user_type Brukertype (student/foreleser)
     * @return array Response med status og melding
     */
    public function changePassword(int $user_id, string $old_password, string $new_password, string $user_type): array
    {
        try {
            $proc_name = $user_type === 'foreleser' ? 'change_lecturer_password' : 'change_student_password';
            $stmt = $this->conn->prepare("CALL $proc_name(?, ?, ?)");
            $stmt->bind_param('iss', $user_id, $old_password, $new_password);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Passord oppdatert'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i changePassword: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved endring av passord'];
        }
    }

    /**
     * Henter informasjon om et emne
     *
     * @param int $emne_id Emnets ID
     * @param string $pin_kode PIN-kode for emnet
     * @return array Response med emnedata eller feilmelding
     */
    public function getCourseInfo(int $emne_id, string $pin_kode): array
    {
        try {
            // Bruker public_courses_view
            $stmt = $this->conn->prepare("SELECT * FROM public_courses_view WHERE emne_id = ? AND pin_kode = ?");
            $stmt->bind_param('is', $emne_id, $pin_kode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return ['status' => 'success', 'data' => $result->fetch_assoc()];
            }
            return ['status' => 'error', 'message' => 'Emne ikke funnet eller ugyldig PIN'];
        } catch (Exception $e) {
            error_log("Feil i getCourseInfo: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved henting av emneinformasjon'];
        }
    }

    /**
     * Henter alle tilgjengelige emner
     *
     * @return array Response med liste over emner eller feilmelding
     */
    public function getAvailableCourses(): array
    {
        try {
            // Bruker public_courses_view
            $stmt = $this->conn->prepare("SELECT * FROM public_courses_view");
            $stmt->execute();
            $result = $stmt->get_result();

            $courses = [];
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
            return ['status' => 'success', 'data' => $courses];
        } catch (Exception $e) {
            error_log("Feil i getAvailableCourses: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved henting av tilgjengelige emner'];
        }
    }

    /**
     * Håndterer bildeopplasting
     *
     * @param array $image Bildeinformasjon
     * @return string Filnavn på det opplastede bildet
     * @throws Exception Hvis opplasting feiler
     */
    private function handleImageUpload(array $image): string
    {
        try {
            if (!is_dir($this->upload_dir)) {
                mkdir($this->upload_dir, 0777, true);
            }

            $filename = uniqid() . '_' . basename($image['name']);
            $target_path = $this->upload_dir . $filename;

            if (move_uploaded_file($image['tmp_name'], $target_path)) {
                return $filename;
            }
            throw new Exception('Kunne ikke laste opp bilde');
        } catch (Exception $e) {
            error_log("Feil i handleImageUpload: " . $e->getMessage());
            throw new Exception('Feil ved opplasting av bilde');
        }
    }

    /**
     * Håndterer brukerinnlogging
     *
     * @param string $email Brukerens epost
     * @param string $password Brukerens passord
     * @return array Response med brukerdata eller feilmelding
     */
    public function login(string $email, string $password): array
    {
        try {
            $stmt = $this->conn->prepare("CALL authenticate_user(?, ?)");
            $stmt->bind_param('ss', $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return [
                    'status' => 'success',
                    'type' => $response['user_type'],
                    'data' => [
                        'id' => $response['user_id'],
                        'fornavn' => $response['fornavn'],
                        'etternavn' => $response['etternavn'],
                        'epost' => $response['epost'],
                        'bilde' => $response['bilde'] ?? null
                    ]
                ];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i login: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved innlogging'];
        }
    }

    /**
     * Sender en melding
     *
     * @param int $studentId Studentens ID
     * @param int $emneId Emnets ID
     * @param string $innhold Meldingens innhold
     * @return array Response med status og melding
     */
    public function sendMessage(int $studentId, int $emneId, string $innhold): array
    {
        try {
            // Bruker lagret prosedyre send_message
            $stmt = $this->conn->prepare("CALL send_message(?, ?, ?)");
            $stmt->bind_param('iis', $studentId, $emneId, $innhold);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Melding sendt'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i sendMessage: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved sending av melding'];
        }
    }

    /**
     * Henter meldinger for et emne
     *
     * @param int $emneId Emnets ID
     * @param string $pinKode PIN-kode for emnet
     * @return array Response med meldinger eller feilmelding
     */
    public function getMessages(int $emneId, string $pinKode): array
    {
        try {
            $stmt = $this->conn->prepare("CALL get_course_messages(?, ?)");
            $stmt->bind_param('is', $emneId, $pinKode);
            $stmt->execute();
            $result = $stmt->get_result();

            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            return ['status' => 'success', 'data' => $messages];
        } catch (Exception $e) {
            error_log("Feil i getMessages: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved henting av meldinger'];
        }
    }

    /**
     * Legger til svar på en melding
     *
     * @param int $meldingId Meldingens ID
     * @param int $foreleserId Foreleserens ID
     * @param string $innhold Svaret
     * @return array Response med status og melding
     */
    public function addResponse(int $meldingId, int $foreleserId, string $innhold): array
    {
        try {
            $stmt = $this->conn->prepare("CALL add_response(?, ?, ?)");
            $stmt->bind_param('iis', $meldingId, $foreleserId, $innhold);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Svar lagt til'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i addResponse: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved lagring av svar'];
        }
    }

    /**
     * Legger til kommentar på en melding
     *
     * @param int $meldingId Meldingens ID
     * @param string $innhold Kommentaren
     * @param string $ipAddress IP-adressen til kommentatoren
     * @return array Response med status og melding
     */
    public function addComment(int $meldingId, string $innhold, string $ipAddress): array
    {
        try {
            $stmt = $this->conn->prepare("CALL add_comment(?, ?, ?)");
            $stmt->bind_param('iss', $meldingId, $innhold, $ipAddress);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Kommentar lagt til'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i addComment: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved lagring av kommentar'];
        }
    }

    /**
     * Rapporterer en melding
     *
     * @param int $meldingId Meldingens ID
     * @param string $grunn Grunnen til rapporteringen
     * @param string $ipAddress IP-adressen til rapporteren
     * @return array Response med status og melding
     */
    public function reportMessage(int $meldingId, string $grunn, string $ipAddress): array
    {
        try {
            $stmt = $this->conn->prepare("CALL report_message(?, ?, ?)");
            $stmt->bind_param('iss', $meldingId, $grunn, $ipAddress);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_assoc();

            if ($response['success'] == 1) {
                return ['status' => 'success', 'message' => 'Melding rapportert'];
            }
            return ['status' => 'error', 'message' => $response['message']];
        } catch (Exception $e) {
            error_log("Feil i reportMessage: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved rapportering av melding'];
        }
    }
}
