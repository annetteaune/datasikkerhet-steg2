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
     * Active database connections for different roles
     *
     * @var array<mixed> Database connections
     */
    private static array $connections = [];

    /**
     * Database configuration settings for different roles
     *
     * @var array<mixed> Database configuration
     */
    private static array $config = [];

    /**
     * Initializes the database configuration settings
     *
     * Loads and sets up the configuration for different database roles
     * if not already initialized.
     *
     * @return void
     */
    private static function initConfig(): void
    {
        if (empty(self::$config)) {
            self::$config = [
                'student' => [
                    'host' => getenv('DB_HOST'),
                    'user' => 'db2_student',
                    'pass' => 'student_pass',
                    'db'   => 'db2'
                ],
                'lecturer' => [
                    'host' => getenv('DB_HOST'),
                    'user' => 'db2_lecturer',
                    'pass' => 'lecturer_pass',
                    'db'   => 'db2'
                ],
                'guest' => [
                    'host' => getenv('DB_HOST'),
                    'user' => 'db2_guest',
                    'pass' => 'guest_pass',
                    'db'   => 'db2'
                ],
                'api' => [
                    'host' => getenv('DB_HOST'),
                    'user' => getenv('DB_USER'),
                    'pass' => getenv('DB_PASS'),
                    'db'   => getenv('DB_NAME')
                ]
            ];
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
     * Gets a database connection for a specific role
     *
     * @param string $role User role (student, lecturer, guest, api)
     *
     * @throws Exception If connection fails
     * @return mysqli   Database connection
     */
    public static function getConnection(string $role = 'api'): mysqli
    {
        self::initConfig();

        try {
            // Validate role
            if (!isset(self::$config[$role])) {
                throw new Exception("Invalid database role: $role");
            }

            // Return existing connection if valid
            if (isset(self::$connections[$role])) {
                $conn = self::$connections[$role];
                if ($conn->ping()) {
                    return $conn;
                }
                // Close invalid connection
                $conn->close();
                unset(self::$connections[$role]);
            }

            // Create new connection
            $config = self::$config[$role];

            // Log connection attempt
            error_log(
                sprintf(
                    'Attempting to connect to database with user: %s@%s',
                    $config['user'],
                    $config['host']
                )
            );

            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['db']
            );

            // Check for connection errors
            if ($conn->connect_error) {
                error_log("Connection error: " . $conn->connect_error);
                throw new Exception("Connection error: " . $conn->connect_error);
            }

            // Set charset and other important settings
            $conn->set_charset("utf8mb4");
            $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

            // Store the connection
            self::$connections[$role] = $conn;

            error_log(
                sprintf(
                    'Successfully connected to database with user: %s',
                    $config['user']
                )
            );
            return $conn;
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception(
                "Could not establish database connection: " . $e->getMessage()
            );
        }
    }

    /**
     * Executes an SQL query
     *
     * @param string       $role   User role
     * @param string       $query  SQL query
     * @param array<mixed> $params Query parameters
     * @param string       $types  Parameter types (i, s, d, b)
     *
     * @throws Exception    If query fails
     * @return mysqli_result Query result
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
     * Executes a stored procedure
     *
     * @param string       $role      User role
     * @param string       $procedure Procedure name
     * @param array<mixed> $params    Procedure parameters
     * @param string       $types     Parameter types (i, s, d, b)
     *
     * @throws Exception    If procedure fails
     * @return mysqli_result Procedure result
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
     * Closes all database connections
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
