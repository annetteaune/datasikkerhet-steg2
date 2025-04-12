<?php

/**
 * Login Attempts Tracker
 *
 * This file contains functions for tracking and limiting login attempts
 * to prevent brute force attacks.
 *
 * PHP version 7.4
 */

declare(strict_types=1);

namespace CleanSteg1\Security;

// Konstanter for rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);    // Maksimal antall forsøk tillatt
define('LOCKOUT_TIME', 900);        // Lockout-tid i sekunder (15 minutter)
define('ATTEMPT_WINDOW', 300);      // Tidsvindu for forsøk i sekunder (5 minutter)

/**
 * Get the storage file path for login attempts
 * @return string Path to the storage file
 */
function get_attempts_file(): string
{
    $dir = sys_get_temp_dir() . '/login_attempts';
    if (!file_exists($dir)) {
        if (!@mkdir($dir, 0777, true)) {
            return sys_get_temp_dir() . '/login_attempts.json';
        }
    }
    return $dir . '/attempts.json';
}

/**
 * Get stored attempts for an IP
 * @param string $ip IP address to check
 * @return array|null Stored attempts or null if none exist
 */
function get_stored_attempts(string $ip): ?array
{
    $file = get_attempts_file();
    if (!file_exists($file)) {
        return null;
    }

    $data = @file_get_contents($file);
    if ($data === false) {
        error_log("Failed to read login attempts data for IP: " . $ip);
        return null;
    }

    $attempts = json_decode($data, true) ?? [];
    return $attempts[$ip] ?? null;
}

/**
 * Store attempts for an IP
 * @param string $ip IP address to store for
 * @param array $attempts Attempts data to store
 */
function store_attempts(string $ip, array $attempts): void
{
    $file = get_attempts_file();
    $data = [];

    if (file_exists($file)) {
        $existing = @file_get_contents($file);
        if ($existing !== false) {
            $data = json_decode($existing, true) ?? [];
        }
    }

    $data[$ip] = $attempts;
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Sjekk om den aktuelle IP-en er rate-begrenset
 * @return array Array som inneholder status og gjenstående tid hvis låst ut
 */
function check_rate_limit(): array
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $current_time = time();

    $attempts = get_stored_attempts($ip);
    if (!$attempts) {
        return ['locked' => false];
    }

    // Hvis for mange forsøk, sjekk låsningstid
    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        $time_remaining = LOCKOUT_TIME - ($current_time - $attempts['last_attempt']);
        if ($time_remaining > 0) {
            return [
                'locked' => true,
                'time_remaining' => ceil($time_remaining / 60) // Konverter til minutter
            ];
        }
        // Nullstill forsøk hvis låsningstid er over
        store_attempts($ip, [
            'count' => 0,
            'last_attempt' => $current_time
        ]);
    }

    return ['locked' => false];
}

/**
 * Registrer et mislykket innloggingsforsøk
 * @param string $ip IP-adressen til forsøket
 */
function record_failed_attempt(string $ip): void
{
    $current_time = time();
    $attempts = get_stored_attempts($ip) ?? [
        'count' => 0,
        'last_attempt' => $current_time
    ];

    // Sjekk om vi skal nullstille telleren (hvis det har gått mer enn ATTEMPT_WINDOW siden siste forsøk)
    if (($current_time - $attempts['last_attempt']) > ATTEMPT_WINDOW) {
        $attempts = [
            'count' => 0,
            'last_attempt' => $current_time
        ];
    }

    $attempts['count']++;
    $attempts['last_attempt'] = $current_time;
    store_attempts($ip, $attempts);
}

/**
 * Nullstill innloggingsforsøk for en IP-adresse
 * @param string $ip IP-adressen som skal nullstilles
 */
function reset_login_attempts(string $ip): void
{
    store_attempts($ip, [
        'count' => 0,
        'last_attempt' => time()
    ]);
}

/**
 * Saner og valider input
 * @param string $input Input som skal saneres
 * @return string Sanert input
 */
function sanitize_input(string $input): string
{
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

/**
 * Valider e-postformat
 * @param string $email E-posten som skal valideres
 * @return bool Om e-posten er gyldig
 */
function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
