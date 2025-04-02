<?php
// Configure secure session parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Constants for registration rate limiting
define('MAX_REGISTRATIONS', 3);  // Maximum number of registrations allowed
define('REGISTRATION_WINDOW', 300);  // Time window in seconds (5 minutes)
define('LOCKOUT_TIME', 900);  // Lockout time in seconds (15 minutes)

// Function to check if IP is rate limited for registrations
function check_registration_limit($ip) {
    $current_time = time();
    
    // Get registration attempts for this IP
    $attempts = get_registration_attempts($ip);
    
    if (!$attempts) {
        return ['limited' => false];
    }
    
    // Check if IP is locked out
    if ($attempts['locked_until'] > $current_time) {
        return [
            'limited' => true,
            'remaining_time' => $attempts['locked_until'] - $current_time
        ];
    }
    
    // Check if within time window
    if ($attempts['count'] >= MAX_REGISTRATIONS && 
        $attempts['last_attempt'] > ($current_time - REGISTRATION_WINDOW)) {
        // Set lockout
        set_registration_lockout($ip, $current_time + LOCKOUT_TIME);
        return [
            'limited' => true,
            'remaining_time' => LOCKOUT_TIME
        ];
    }
    
    return ['limited' => false];
}

// Function to record a registration attempt
function record_registration_attempt($ip) {
    $current_time = time();
    
    // Get existing attempts
    $attempts = get_registration_attempts($ip);
    
    if (!$attempts) {
        // First attempt
        $attempts = [
            'count' => 1,
            'last_attempt' => $current_time,
            'locked_until' => 0
        ];
    } else {
        // Check if we should reset the counter (outside time window)
        if ($attempts['last_attempt'] < ($current_time - REGISTRATION_WINDOW)) {
            $attempts['count'] = 1;
        } else {
            $attempts['count']++;
        }
        $attempts['last_attempt'] = $current_time;
    }
    
    // Store attempts
    store_registration_attempts($ip, $attempts);
}

// Function to reset registration attempts for an IP
function reset_registration_attempts($ip) {
    if (isset($_SESSION['registration_attempts'][$ip])) {
        unset($_SESSION['registration_attempts'][$ip]);
    }
}

// Helper function to get registration attempts from session
function get_registration_attempts($ip) {
    if (!isset($_SESSION['registration_attempts'][$ip])) {
        return null;
    }
    return $_SESSION['registration_attempts'][$ip];
}

// Helper function to store registration attempts in session
function store_registration_attempts($ip, $attempts) {
    if (!isset($_SESSION['registration_attempts'])) {
        $_SESSION['registration_attempts'] = [];
    }
    $_SESSION['registration_attempts'][$ip] = $attempts;
}

// Helper function to set registration lockout
function set_registration_lockout($ip, $lockout_time) {
    $attempts = get_registration_attempts($ip);
    if ($attempts) {
        $attempts['locked_until'] = $lockout_time;
        store_registration_attempts($ip, $attempts);
    }
}
?> 