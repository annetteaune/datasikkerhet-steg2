<?php
// Disable error reporting for notices and warnings in production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// Configure secure session parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Constants for rate limiting
define('MAX_REQUESTS', 60);  // Maximum number of requests allowed per window
define('TIME_WINDOW', 60);    // Time window in seconds (1 minute)
define('BLOCK_TIME', 30);    // Block time in seconds (5 minutes)

class RateLimiter {
    private $ip;
    private $current_time;

    public function __construct($ip = null) {
        $this->ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $this->current_time = time();
    }

    // Check if the IP is currently rate limited
    public function isLimited() {
        $requests = $this->getRequests();
        
        if (!$requests) {
            return false;
        }

        // Check if IP is blocked
        if (isset($requests['blocked_until']) && $requests['blocked_until'] > $this->current_time) {
            return true;
        }

        // Clean old requests outside the time window
        $requests['timestamps'] = array_filter($requests['timestamps'], function($timestamp) {
            return $timestamp > ($this->current_time - TIME_WINDOW);
        });

        // If too many requests in the time window, block the IP
        if (count($requests['timestamps']) >= MAX_REQUESTS) {
            $this->blockIP();
            return true;
        }

        return false;
    }

    // Record a new request
    public function recordRequest() {
        $requests = $this->getRequests();

        if (!$requests) {
            $requests = [
                'timestamps' => [],
                'blocked_until' => 0
            ];
        }

        // Clean old requests
        $requests['timestamps'] = array_filter($requests['timestamps'], function($timestamp) {
            return $timestamp > ($this->current_time - TIME_WINDOW);
        });

        // Add new request timestamp
        $requests['timestamps'][] = $this->current_time;

        $this->storeRequests($requests);
    }

    // Get remaining requests allowed
    public function getRemainingRequests() {
        $requests = $this->getRequests();
        
        if (!$requests) {
            return MAX_REQUESTS;
        }

        // Clean old requests
        $requests['timestamps'] = array_filter($requests['timestamps'], function($timestamp) {
            return $timestamp > ($this->current_time - TIME_WINDOW);
        });

        return MAX_REQUESTS - count($requests['timestamps']);
    }

    // Get remaining block time
    public function getRemainingBlockTime() {
        $requests = $this->getRequests();
        
        if (!$requests || !isset($requests['blocked_until'])) {
            return 0;
        }

        $remaining = $requests['blocked_until'] - $this->current_time;
        return $remaining > 0 ? $remaining : 0;
    }

    // Block the IP
    private function blockIP() {
        $requests = $this->getRequests() ?? ['timestamps' => []];
        $requests['blocked_until'] = $this->current_time + BLOCK_TIME;
        $this->storeRequests($requests);
    }

    // Get stored requests for the IP
    private function getRequests() {
        if (!isset($_SESSION['rate_limits'][$this->ip])) {
            return null;
        }
        return $_SESSION['rate_limits'][$this->ip];
    }

    // Store requests for the IP
    private function storeRequests($requests) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        $_SESSION['rate_limits'][$this->ip] = $requests;
    }
}

// Function to apply rate limiting to a page
function apply_rate_limit() {
    $rate_limiter = new RateLimiter();
    
    if ($rate_limiter->isLimited()) {
        http_response_code(429); // Too Many Requests
        $remaining_time = $rate_limiter->getRemainingBlockTime();
        die(json_encode([
            'error' => 'Too many requests',
            'message' => "You have exceeded the request limit. Please try again in {$remaining_time} seconds.",
            'retry_after' => $remaining_time
        ]));
    }
    
    $rate_limiter->recordRequest();
    
    // Add rate limit headers
    header('X-RateLimit-Limit: ' . MAX_REQUESTS);
    header('X-RateLimit-Remaining: ' . $rate_limiter->getRemainingRequests());
    header('X-RateLimit-Reset: ' . (time() + TIME_WINDOW));
} 