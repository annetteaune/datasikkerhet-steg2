<?php

/**
 * API Endpoint Permissions
 *
 * Defines which user roles can access which API endpoints
 *
 * PHP version 7.4
 *
 * @category  API
 * @package   CleanSteg1
 * @author    Your Name <your.email@example.com>
 * @license   MIT License
 * @link      https://github.com/yourusername/cleanSteg1
 */

declare(strict_types=1);

namespace CleanSteg1\API;

/**
 * Maps endpoints to allowed user roles based on database permissions
 */
class EndpointPermissions
{
    /**
     * Definer hvilke roller som har tilgang til hvilke endepunkter
     *
     * @var array<string, array<string>>
     */
    private static array $permissions = [
        // Student endepunkter
        'send_message' => ['student'],
        'get_student_messages' => ['student'],
        'change_student_password' => ['student'],
        'request_password_reset' => ['student'],
        'reset_password_with_token' => ['student'],

        // Foreleser endepunkter
        'get_course_messages' => ['lecturer'],
        'send_response' => ['lecturer'],
        'change_lecturer_password' => ['lecturer'],
        'get_lecturer_unanswered_messages' => ['lecturer'],
        'add_comment' => ['lecturer'],

        // Gjest endepunkter
        'get_course_messages' => ['guest'],
        'get_message_comments' => ['guest'],
        'verify_course_pin' => ['guest'],

        // Offentlige endepunkter (ingen autentisering påkrevd)
        'courses' => ['*'],
        'register_student' => ['*'],
        'register_lecturer_with_course' => ['*'],
        'login_student' => ['*'],
        'login_lecturer' => ['*'],
        'user_login' => ['*']
    ];

    /**
     * Sjekker om en rolle har tilgang til et endepunkt
     *
     * @param string $endpoint Endepunktet som skal sjekkes
     * @param string $role     Brukerrolle
     *
     * @return bool True hvis rolle har tilgang, false ellers
     */
    public static function hasPermission(string $endpoint, string $role): bool
    {
        if (!isset(self::$permissions[$endpoint])) {
            return false;
        }

        return in_array('*', self::$permissions[$endpoint]) ||
               in_array($role, self::$permissions[$endpoint]);
    }
}
