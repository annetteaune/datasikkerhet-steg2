<?php

/**
 * Database Utility Class
 *
 * This file contains the Database class that provides utility methods
 * for database operations and password validation.
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

/**
 * Database Class
 *
 * Handles database connections and password validation
 *
 * @category   Database
 * @package    CleanSteg1
 * @subpackage Database
 */
class Database
{
    /**
     * Database host
     */
    private const HOST = "localhost";

    /**
     * Database navn
     */
    private const DBNAME = "db2";

    /**
     * Database brukerkonfigurasjon
     */
    private const DB_USERS = [
        'admin' => [
            'username' => 'db2_admin',
            'password' => 'StrongAdminPassword123!'
        ],
        'student' => [
            'username' => 'db2_student',
            'password' => 'StudentPassword123!'
        ],
        'lecturer' => [
            'username' => 'db2_lecturer',
            'password' => 'LecturerPassword123!'
        ],
        'guest' => [
            'username' => 'db2_guest',
            'password' => 'GuestPassword123!'
        ]
    ];

    /**
     * Hent databaseforbindelse basert på rolle
     *
     * @param string $role Brukerrolle (admin, student, foreleser, gjest)
     *
     * @return \mysqli Databaseforbindelse
     *
     * @throws \Exception Om forbindelsen mislykkes
     */
    public static function getConnection(string $role = 'guest'): \mysqli
    {
        error_log("Attempting to get database connection for role: " . $role);

        if (!array_key_exists($role, self::DB_USERS)) {
            error_log("Invalid role specified: " . $role . ". Defaulting to guest.");
            $role = 'guest'; // Default rolle hvis ugyldig rolle er spesifisert
        }

        $user = self::DB_USERS[$role]['username'];
        $password = self::DB_USERS[$role]['password'];

        error_log("Connecting to database with user: " . $user);

        try {
            $conn = new \mysqli(self::HOST, $user, $password, self::DBNAME);

            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                throw new \Exception("Database connection failed: " . $conn->connect_error);
            }

            $conn->set_charset("utf8mb4");
            $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->query("SET CHARACTER SET utf8mb4");

            error_log("Database connection successful");
            return $conn;
        } catch (\Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            error_log("Connection details: HOST=" . self::HOST . ", USER=" . $user . ", DB=" . self::DBNAME);
            throw $e;
        }
    }

    /**
     * Lukk databaseforbindelse
     *
     * @param \mysqli $conn Databaseforbindelse som skal lukkes
     *
     * @return void
     */
    public static function closeConnection(\mysqli $conn): void
    {
        if ($conn && !$conn->connect_error) {
            $conn->close();
        }
    }

    /**
     * Valider passordstyrke etter sikkerhetskrav
     * - Minimum 8 tegn
     * - Minst én stor bokstav
     * - Minst étt tall
     * - Minst ét spesialtegn
     *
     * @param string $password Passord som skal valideres
     *
     * @return array{valid: bool, message: string} Valideringsresultat
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        // Sjekk minimum lengde
        if (strlen($password) < 8) {
            $errors[] = "Passordet må være minst 8 tegn langt.";
        }

        // Sjekk for stor bokstav
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Passordet må inneholde minst én stor bokstav.";
        }

        // Sjekk for tall
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Passordet må inneholde minst ett tall.";
        }

        // Sjekk for spesialtegn
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Passordet må inneholde minst ett spesialtegn.";
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : implode('<br>', $errors)
        ];
    }
}
