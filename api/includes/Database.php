<?php
class Database {
    private $connections = [];
    private $config = [
        'student' => [
            'host' => DB_HOST,
            'user' => 'db2_student',
            'pass' => 'student_pass',
            'db'   => 'db2'
        ],
        'lecturer' => [
            'host' => DB_HOST,
            'user' => 'db2_lecturer',
            'pass' => 'lecturer_pass',
            'db'   => 'db2'
        ],
        'guest' => [
            'host' => DB_HOST,
            'user' => 'db2_guest',
            'pass' => 'guest_pass',
            'db'   => 'db2'
        ],
        'api' => [
            'host' => DB_HOST,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'db'   => DB_NAME
        ]
    ];
    
    public function __construct() {
        // Ingen tilkobling opprettes i konstruktøren
    }
    
    public function getConnection($role = 'api') {
        try {
            // Valider rollen
            if (!isset($this->config[$role])) {
                throw new Exception("Ugyldig databaserolle: $role");
            }
            
            // Returner eksisterende tilkobling hvis den finnes og er gyldig
            if (isset($this->connections[$role])) {
                $conn = $this->connections[$role];
                if ($conn->ping()) {
                    return $conn;
                }
                // Hvis tilkoblingen er ugyldig, lukk den
                $conn->close();
                unset($this->connections[$role]);
            }
            
            // Opprett ny tilkobling
            $config = $this->config[$role];
            
            // Logg tilkoblingsforsøk
            error_log("Forsøker å koble til database med bruker: {$config['user']}@{$config['host']}");
            
            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['db']
            );
            
            // Sjekk for tilkoblingsfeil
            if ($conn->connect_error) {
                error_log("Tilkoblingsfeil: " . $conn->connect_error);
                throw new Exception("Tilkoblingsfeil: " . $conn->connect_error);
            }
            
            // Sett tegnsett og andre viktige innstillinger
            $conn->set_charset("utf8mb4");
            $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
            
            // Lagre tilkoblingen
            $this->connections[$role] = $conn;
            
            error_log("Vellykket tilkobling til database med bruker: {$config['user']}");
            return $conn;
            
        } catch (Exception $e) {
            error_log("Database tilkoblingsfeil: " . $e->getMessage());
            throw new Exception("Kunne ikke opprette databasetilkobling: " . $e->getMessage());
        }
    }
    
    public function executeQuery($role, $query, $params = [], $types = '') {
        try {
            $conn = $this->getConnection($role);
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Forberedelsesfeil: " . $conn->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Utførelsesfeil: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database spørringsfeil: " . $e->getMessage());
            throw new Exception("Kunne ikke utføre spørring: " . $e->getMessage());
        }
    }
    
    public function executeStoredProcedure($role, $procedure, $params = [], $types = '') {
        try {
            $conn = $this->getConnection($role);
            $placeholders = str_repeat('?,', count($params) - 1) . '?';
            $query = "CALL $procedure($placeholders)";
            
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Forberedelsesfeil for prosedyre: " . $conn->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Utførelsesfeil for prosedyre: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database prosedyrefeil: " . $e->getMessage());
            throw new Exception("Kunne ikke utføre prosedyre: " . $e->getMessage());
        }
    }
    
    public function closeConnections() {
        foreach ($this->connections as $conn) {
            if ($conn && $conn->ping()) {
                $conn->close();
            }
        }
        $this->connections = [];
    }
    
    public function __destruct() {
        $this->closeConnections();
    }
}
