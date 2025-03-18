<?php
class API {
    private $db;
    private $conn;
    private $upload_dir = '../img/';
    //   private $upload_dir = '../../img/'; på server for å samkjøre opplasningsmapper 
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection('api');
    }

    public function getStudent($studentId) {
        try {
            $stmt = $this->conn->prepare("CALL get_student_profile(?)");
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
    
    public function getLecturer($lecturerId) {
        try {
            $stmt = $this->conn->prepare("CALL get_lecturer_profile(?)");
            $stmt->bind_param('i', $lecturerId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'Foreleser ikke funnet'];
            }
            
            $data = $result->fetch_assoc();
            if ($data['emner']) {
                $data['emner'] = explode(',', $data['emner']);
            } else {
                $data['emner'] = [];
            }
            
            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            error_log("Feil i getLecturer: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'En feil oppstod ved henting av foreleserprofil'];
        }
    }

    public function registerStudent($fornavn, $etternavn, $epost, $passord) {
        try {
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

    public function registerLecturer($fornavn, $etternavn, $epost, $passord, $bilde, $emne_navn, $emne_kode, $pin_kode) {
        try {
            $this->conn->begin_transaction();
            
            // Håndter bildeopplasting
            $bilde_path = $this->handleImageUpload($bilde);
            
            // Registrer foreleser og emne
            $stmt = $this->conn->prepare("CALL register_lecturer(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssss', $fornavn, $etternavn, $epost, $passord, $bilde_path, $emne_navn, $emne_kode, $pin_kode);
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

    public function requestPasswordReset($email) {
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

    public function changePassword($user_id, $old_password, $new_password, $user_type) {
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

    public function getCourseInfo($emne_id, $pin_kode) {
        try {
            $stmt = $this->conn->prepare("CALL get_course_info(?, ?)");
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

    public function getAvailableCourses() {
        try {
            $stmt = $this->conn->prepare("CALL get_available_courses()");
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

    private function handleImageUpload($image) {
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

    public function login($email, $password) {
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

    public function sendMessage($studentId, $emneId, $innhold) {
        try {
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

    public function getMessages($emneId, $pinKode) {
        try {
            $stmt = $this->conn->prepare("CALL get_course_messages_with_responses(?, ?)");
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

    public function addResponse($meldingId, $foreleserId, $innhold) {
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
            return ['status' => 'error', 'message' => 'En feil oppstod ved legging til svar'];
        }
    }

    public function addComment($meldingId, $innhold, $ipAddress) {
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
            return ['status' => 'error', 'message' => 'En feil oppstod ved legging til kommentar'];
        }
    }

    public function reportMessage($meldingId, $grunn, $ipAddress) {
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