<?php

// Konstanter for rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);
// Maksimal antall forsøk tillatt
define('LOCKOUT_TIME', 900);
// Lockout-tid i sekunder (15 minutter)
define('ATTEMPT_WINDOW', 300);    // Tidsvindu for forsøk i sekunder (5 minutter)

/**
 * Sjekk om den aktuelle IP-en er rate-begrenset
 * @return array Array som inneholder status og gjenstående tid hvis låst ut
 */
function check_rate_limit()
{

    $ip = $_SERVER['REMOTE_ADDR'];
    $current_time = time();
// Initialiser forsøksarray hvis den ikke eksisterer
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    // Rens opp gamle forsøk
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function ($attempt) use ($current_time) {

        return $attempt['last_attempt'] > ($current_time - ATTEMPT_WINDOW);
    });
// Sjekk om IP-en er låst ut
    if (isset($_SESSION['login_attempts'][$ip])) {
        $attempts = $_SESSION['login_attempts'][$ip];
// Hvis for mange forsøk, sjekk låsningstid
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
            $time_remaining = LOCKOUT_TIME - ($current_time - $attempts['last_attempt']);
            if ($time_remaining > 0) {
                return [
                    'locked' => true,
                    'time_remaining' => ceil($time_remaining / 60) // Convert to minutes
                ];
            } else {
            // Nullstill forsøk hvis låsningstid er over
                unset($_SESSION['login_attempts'][$ip]);
            }
        }
    }

    return ['locked' => false];
}

/**
 * Registrer et mislykket innloggingsforsøk
 * @param string $ip IP-adressen til forsøket
 */
function record_failed_attempt($ip)
{

    $current_time = time();
    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = [
            'count' => 0,
            'last_attempt' => $current_time
        ];
    }

    $_SESSION['login_attempts'][$ip]['count']++;
    $_SESSION['login_attempts'][$ip]['last_attempt'] = $current_time;
}

/**
 * Nullstill innloggingsforsøk for en IP-adresse
 * @param string $ip IP-adressen som skal nullstilles
 */
function reset_login_attempts($ip)
{

    if (isset($_SESSION['login_attempts'][$ip])) {
        unset($_SESSION['login_attempts'][$ip]);
    }
}

/**
 * Saner og valider input
 * @param string $input Input som skal saneres
 * @return string Sanert input
 */
function sanitize_input($input)
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
function validate_email($email)
{

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
