<?php
// Diable i produksjon
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// Konfigurer sikre session-parametere før session startes
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session hvis en ikke allerede er aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konstanter for rate limiting
define('MAX_REQUESTS', 60);  // Maksimal antall forespørsler per vindu
define('TIME_WINDOW', 60);    // Tidsvindu i sekunder (1 minutt)
define('BLOCK_TIME', 30);    // Blokkeringstid i sekunder (5 minutter)

class RateLimiter {
    private $ip;
    private $current_time;

    public function __construct($ip = null) {
        $this->ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $this->current_time = time();
    }

    // Sjekk om IP-en er nåværende rate limited
    public function isLimited() {
        $requests = $this->getRequests();
        
        if (!$requests) {
            return false;
        }

        // Sjekk om IP-en er blokkert
        if (isset($requests['blocked_until']) && $requests['blocked_until'] > $this->current_time) {
            return true;
        }

        // Rens gamle forespørsler utenfor tidsvinduet
        $requests['timestamps'] = array_filter($requests['timestamps'], function($timestamp) {
            return $timestamp > ($this->current_time - TIME_WINDOW);
        });

        // Hvis for mange forespørsler i tidsvinduet, blokker IP-en
        if (count($requests['timestamps']) >= MAX_REQUESTS) {
            $this->blockIP();
            return true;
        }

        return false;
    }

    // Registrer en ny forespørsel
    public function recordRequest() {
        $requests = $this->getRequests();

        if (!$requests) {
            $requests = [
                'timestamps' => [],
                'blocked_until' => 0
            ];
        }

        // Rens gamle forespørsler
        $requests['timestamps'] = array_filter($requests['timestamps'], function($timestamp) {
            return $timestamp > ($this->current_time - TIME_WINDOW);
        });

        // Legg til ny forespørselstidspunkt
        $requests['timestamps'][] = $this->current_time;

        $this->storeRequests($requests);
    }

    // Hent gjenstående forespørsler
    public function getRemainingRequests() {
        $requests = $this->getRequests();
        
        if (!$requests) {
            return MAX_REQUESTS;
        }

        // Rens gamle forespørsler
        $requests['timestamps'] = array_filter($requests['timestamps'], function($timestamp) {
            return $timestamp > ($this->current_time - TIME_WINDOW);
        });

        return MAX_REQUESTS - count($requests['timestamps']);
    }

    // Hent gjenstående blokkeringstid
    public function getRemainingBlockTime() {
        $requests = $this->getRequests();
        
        if (!$requests || !isset($requests['blocked_until'])) {
            return 0;
        }

        $remaining = $requests['blocked_until'] - $this->current_time;
        return $remaining > 0 ? $remaining : 0;
    }

    // Blokker IP-en
    private function blockIP() {
        $requests = $this->getRequests() ?? ['timestamps' => []];
        $requests['blocked_until'] = $this->current_time + BLOCK_TIME;
        $this->storeRequests($requests);
    }

    // Hent lagrede forespørsler for IP-en
    private function getRequests() {
        if (!isset($_SESSION['rate_limits'][$this->ip])) {
            return null;
        }
        return $_SESSION['rate_limits'][$this->ip];
    }

    // Lagre forespørsler for IP-en
    private function storeRequests($requests) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        $_SESSION['rate_limits'][$this->ip] = $requests;
    }
}

// Funksjon for å bruke rate limiting på  en side
function apply_rate_limit() {
    $rate_limiter = new RateLimiter();
    
    if ($rate_limiter->isLimited()) {
        http_response_code(429); // For mange forespørsler
        $remaining_time = $rate_limiter->getRemainingBlockTime();
        die(json_encode([
            'error' => 'For mange forespørsler',
            'message' => "You have exceeded the request limit. Please try again in {$remaining_time} seconds.",
            'retry_after' => $remaining_time
        ]));
    }
    
    $rate_limiter->recordRequest();
    
    // Legg til rate limit headers
    header('X-RateLimit-Limit: ' . MAX_REQUESTS);
    header('X-RateLimit-Remaining: ' . $rate_limiter->getRemainingRequests());
    header('X-RateLimit-Reset: ' . (time() + TIME_WINDOW));
} 