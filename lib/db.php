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
     * Database name
     */
    private const DBNAME = "db2";

    /**
     * Database users configuration
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
     * Get database connection based on role
     *
     * @param string $role User role (admin, student, lecturer, guest)
     *
     * @return \mysqli Database connection
     *
     * @throws \Exception If connection fails
     */
    public static function getConnection(string $role = 'guest'): \mysqli
    {
        if (!array_key_exists($role, self::DB_USERS)) {
            $role = 'guest'; // Default role if invalid role is specified
        }

        $user = self::DB_USERS[$role]['username'];
        $password = self::DB_USERS[$role]['password'];

        $conn = new \mysqli(self::HOST, $user, $password, self::DBNAME);

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            throw new \Exception("Database connection failed. Please try again later.");
        }

        return $conn;
    }

    /**
     * Close database connection
     *
     * @param \mysqli $conn Database connection to close
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
     * Validate password strength according to security requirements
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one number
     * - At least one special character
     *
     * @param string $password Password to validate
     *
     * @return array{valid: bool, message: string} Validation result
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        // Check minimum length
        if (strlen($password) < 8) {
            $errors[] = "Passordet må være minst 8 tegn langt.";
        }

        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Passordet må inneholde minst én stor bokstav.";
        }

        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Passordet må inneholde minst ett tall.";
        }

        // Check for special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Passordet må inneholde minst ett spesialtegn.";
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : implode('<br>', $errors)
        ];
    }
}
