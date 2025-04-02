<?php
// Constants for rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);  // Maximum number of attempts allowed
define('LOCKOUT_TIME', 900);      // Lockout time in seconds (15 minutes)
define('ATTEMPT_WINDOW', 300);    // Time window for attempts in seconds (5 minutes)

/**
 * Check if the current IP is rate limited
 * @return array Array containing status and remaining time if locked out
 */
function check_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $current_time = time();
    
    // Initialize attempts array if not exists
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    // Clean up old attempts
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt) use ($current_time) {
        return $attempt['last_attempt'] > ($current_time - ATTEMPT_WINDOW);
    });
    
    // Check if IP is locked out
    if (isset($_SESSION['login_attempts'][$ip])) {
        $attempts = $_SESSION['login_attempts'][$ip];
        
        // If too many attempts, check lockout
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
            $time_remaining = LOCKOUT_TIME - ($current_time - $attempts['last_attempt']);
            
            if ($time_remaining > 0) {
                return [
                    'locked' => true,
                    'time_remaining' => ceil($time_remaining / 60) // Convert to minutes
                ];
            } else {
                // Reset attempts if lockout period is over
                unset($_SESSION['login_attempts'][$ip]);
            }
        }
    }
    
    return ['locked' => false];
}

/**
 * Record a failed login attempt
 * @param string $ip IP address of the attempt
 */
function record_failed_attempt($ip) {
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
 * Reset login attempts for an IP
 * @param string $ip IP address to reset
 */
function reset_login_attempts($ip) {
    if (isset($_SESSION['login_attempts'][$ip])) {
        unset($_SESSION['login_attempts'][$ip]);
    }
}

/**
 * Sanitize and validate input
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool Whether email is valid
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
} 