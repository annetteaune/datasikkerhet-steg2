<?php
class Database {
    private $connections = [];
    private $config = [
        'student' => [
            'host' => DB_HOST,
            'user' => 'student_user',
            'pass' => 'student_pass',
            'db'   => 'db2'
        ],
        'lecturer' => [
            'host' => DB_HOST,
            'user' => 'lecturer_user',
            'pass' => 'lecturer_pass',
            'db'   => 'db2'
        ],
        'guest' => [
            'host' => DB_HOST,
            'user' => 'guest_user',
            'pass' => 'guest_pass',
            'db'   => 'db2'
        ],
        'api' => [
            'host' => DB_HOST,
            'user' => 'api_user',
            'pass' => 'api_pass',
            'db'   => 'db2'
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
            
            // Returner eksisterende tilkobling hvis den finnes
            if (isset($this->connections[$role]) && $this->connections[$role]->ping()) {
                return $this->connections[$role];
            }
            
            // Opprett ny tilkobling
            $config = $this->config[$role];
            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['db']
            );
            
            // Sjekk for tilkoblingsfeil
            if ($conn->connect_error) {
                throw new Exception("Tilkoblingsfeil: " . $conn->connect_error);
            }
            
            // Sett tegnsett
            $conn->set_charset("utf8mb4");
            
            // Lagre tilkoblingen
            $this->connections[$role] = $conn;
            
            return $conn;
            
        } catch (Exception $e) {
            error_log("Database tilkoblingsfeil: " . $e->getMessage());
            throw new Exception("Kunne ikke opprette databasetilkobling");
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
