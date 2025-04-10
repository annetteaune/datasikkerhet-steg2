<?php

/**
 * Database Connection Handler
 *
 * This file contains the Database class that manages database connections
 * for different user roles and provides methods for executing queries.
 *
 * PHP version 7.4
 *
 * @category   Database
 * @package    CleanSteg1
 * @subpackage Database
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

declare(strict_types=1);

namespace CleanSteg1\Database;

use mysqli;
use mysqli_result;
use Exception;

/**
 * Database class for managing database connections and queries
 *
 * This class handles database connections for different user roles
 * (student, lecturer, guest, api)
 * and provides methods for executing queries and stored procedures.
 *
 * @category   Database
 * @package    CleanSteg1
 * @subpackage Database
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */
class Database
{
    /**
     * Aktive databaseforbindelser for ulike roller
     *
     * @var array<mixed> Databaseforbindelser
     */
    private static array $connections = [];

    /**
     * Database config settings for ulike roller
     *
     * @var array<mixed> Database configs
     */
    private static array $config = [];

    /**
     * Init databaseconfig
     *
     * Laster inn og oppretter konfigurasjonen for ulike databaseroller
     * hvis den ikke allerede er initialisert.
     *
     * @return void
     */
    private static function initConfig(): void
    {
        if (empty(self::$config)) {
            self::$config = [
                'student' => [
                    'host' => getenv('DB_HOST'),
                    'user' => getenv('DB_STUDENT_USER') ?: 'db2_student',
                    'pass' => getenv('DB_STUDENT_PASS') ?: 'student_pass',
                    'db'   => getenv('DB_NAME') ?: 'db2'
                ],
                'lecturer' => [
                    'host' => getenv('DB_HOST'),
                    'user' => getenv('DB_LECTURER_USER') ?: 'db2_lecturer',
                    'pass' => getenv('DB_LECTURER_PASS') ?: 'lecturer_pass',
                    'db'   => getenv('DB_NAME') ?: 'db2'
                ],
                'guest' => [
                    'host' => getenv('DB_HOST'),
                    'user' => getenv('DB_GUEST_USER') ?: 'db2_guest',
                    'pass' => getenv('DB_GUEST_PASS') ?: 'guest_pass',
                    'db'   => getenv('DB_NAME') ?: 'db2'
                ],
                'api' => [
                    'host' => getenv('DB_HOST'),
                    'user' => getenv('DB_USER'),
                    'pass' => getenv('DB_PASS'),
                    'db'   => getenv('DB_NAME')
                ]
            ];

            // Logge konfigurasjonen
            foreach (self::$config as $role => $config) {
                error_log(sprintf(
                    'Database config for %s: host=%s, user=%s, db=%s',
                    $role,
                    $config['host'],
                    $config['user'],
                    $config['db']
                ));
            }
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        self::initConfig();
    }

    /**
     * Henter en databaseforbindelse for en bestemt rolle
     *
     * @param string $role Brukerrolle (student, lecturer, guest, api)
     *
     * @throws Exception Om forbindelsen mislykkes
     * @return mysqli   Databaseforbindelse
     */
    public static function getConnection(string $role = 'api'): mysqli
    {
        self::initConfig();

        try {
            // Valider rolle
            if (!isset(self::$config[$role])) {
                error_log("Invalid database role requested: " . $role);
                throw new Exception("Invalid database role: $role");
            }

            // Return eksisterende forbindelse hvis gyldig
            if (isset(self::$connections[$role])) {
                $conn = self::$connections[$role];
                if ($conn->ping()) {
                    return $conn;
                }
                // CLukker stale forbindelse
                error_log("Closing stale connection for role: " . $role);
                $conn->close();
                unset(self::$connections[$role]);
            }

            // Opprett ny forbindelse
            $config = self::$config[$role];

            // Logg forbindelsesforsøk
            error_log(sprintf(
                'Attempting to connect to database with user: %s@%s for role: %s',
                $config['user'],
                $config['host'],
                $role
            ));

            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['db']
            );

            // Sjekk for forbindelsesfeil
            if ($conn->connect_error) {
                error_log("Connection error for role " . $role . ": " . $conn->connect_error);
                throw new Exception("Connection error: " . $conn->connect_error);
            }

            // Set charset and other important settings
            $conn->set_charset("utf8mb4");
            $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

            // Store the connection
            self::$connections[$role] = $conn;

            error_log("Successfully connected to database for role: " . $role);
            return $conn;
        } catch (Exception $e) {
            error_log("Database connection error for role " . $role . ": " . $e->getMessage());
            throw new Exception(
                "Could not establish database connection: " . $e->getMessage()
            );
        }
    }

    /**
     * Utfører en SQL-forespørsel
     *
     * @param string       $role   Brukerrolle
     * @param string       $query  SQL-forespørsel
     * @param array<mixed> $params Forespørselsparametre
     * @param string       $types  Parametertyper (i, s, d, b)
     *
     * @throws Exception    Om forespørselen mislykkes
     * @return mysqli_result SQL-forespørselens resultat
     */
    public function executeQuery(
        string $role,
        string $query,
        array $params = [],
        string $types = ''
    ): mysqli_result {
        try {
            $conn = $this->getConnection($role);
            $stmt = $conn->prepare($query);

            if ($stmt === false) {
                throw new Exception("Preparation error: " . $conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execution error: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Could not execute query: " . $e->getMessage());
        }
    }

    /**
     * Utfører en lagret prosedyre
     *
     * @param string       $role      Brukerrolle
     * @param string       $procedure Prosedyrenavn
     * @param array<mixed> $params    Prosedyreparametre
     * @param string       $types     Parametertyper (i, s, d, b)
     *
     * @throws Exception    Om prosedyren mislykkes
     * @return mysqli_result Prosedyreresultat
     */
    public function executeStoredProcedure(
        string $role,
        string $procedure,
        array $params = [],
        string $types = ''
    ): mysqli_result {
        try {
            $conn = $this->getConnection($role);
            $placeholders = str_repeat('?,', count($params) - 1) . '?';
            $query = "CALL $procedure($placeholders)";

            $stmt = $conn->prepare($query);

            if ($stmt === false) {
                throw new Exception(
                    "Procedure preparation error: " . $conn->error
                );
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Procedure execution error: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Database procedure error: " . $e->getMessage());
            throw new Exception(
                "Could not execute procedure: " . $e->getMessage()
            );
        }
    }

    /**
     * Lukker alle databaseforbindelser
     *
     * @return void
     */
    public function closeConnections(): void
    {
        foreach (self::$connections as $conn) {
            if ($conn && $conn->ping()) {
                $conn->close();
            }
        }
        self::$connections = [];
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->closeConnections();
    }
}
