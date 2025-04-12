<?php

/**
 * Rate Limiter Implementation
 *
 * This file contains a rate limiting implementation to prevent DDoS attacks
 * by limiting the number of requests from a single IP address.
 *
 * PHP version 7.4
 *
 * @category   Security
 * @package    CleanSteg1
 * @subpackage Security
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

declare(strict_types=1);

namespace CleanSteg1\Security;

use Exception;

// Konstanter for rate limiting
define('MAX_REQUESTS', 60);    // Maksimal antall forespørsler per minutt
define('TIME_WINDOW', 60);     // Tidsvindu i sekunder (1 minutt)
define('BLOCK_TIME', 300);     // Blokkeringstid i sekunder (5 minutter)

/**
 * Apply rate limiting to the current request. If the limit is exceeded,
 * this function will terminate the request with a 429 status code.
 *
 * @return void
 */
function applyRateLimit(): void
{
    $limiter = new RateLimiter();

    // Record this request first
    $limiter->recordRequest();

    // Then check if we're over the limit
    if ($limiter->isLimited()) {
        http_response_code(429);
        header('Retry-After: ' . $limiter->getRemainingBlockTime());
        header('Content-Type: application/json');
        die(json_encode([
            'error' => 'Too Many Requests',
            'message' => 'For mange forespørsler. Vennligst vent ' .
                ceil($limiter->getRemainingBlockTime() / 60) . ' minutter.'
        ]));
    }
}

/**
 * Initialize secure session configuration
 *
 * @return void
 */
function initializeSession(): void
{
    // Diable i produksjon
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', '0');

    // Konfigurer sikre session-parametere før session startes
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');

    // Start session hvis en ikke allerede er aktiv
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Rate Limiter class for controlling request frequency
 *
 * @category   Security
 * @package    CleanSteg1
 * @subpackage Security
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */
class RateLimiter
{
    /**
     * IP address to rate limit
     *
     * @var string
     */
    private string $ip;

    /**
     * Current timestamp
     *
     * @var int
     */
    private int $currentTime;

    /**
     * Constructor for RateLimiter
     *
     * @param string|null $ip IP address to limit (defaults to current client IP)
     */
    public function __construct(?string $ip = null)
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $this->currentTime = time();

        // Initialize rate limit data structure if it doesn't exist
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        if (!isset($_SESSION['rate_limits'][$this->ip])) {
            $_SESSION['rate_limits'][$this->ip] = [
                'timestamps' => [],
                'blocked_until' => 0
            ];
        }

        // Clean up old data
        $this->cleanupOldData();
    }

    /**
     * Clean up old rate limit data
     */
    private function cleanupOldData(): void
    {
        if (!isset($_SESSION['rate_limits'])) {
            return;
        }

        foreach ($_SESSION['rate_limits'] as $ip => $data) {
            // Remove expired blocks
            if (isset($data['blocked_until']) && $data['blocked_until'] < $this->currentTime) {
                $_SESSION['rate_limits'][$ip]['blocked_until'] = 0;
            }

            // Remove old timestamps
            if (isset($data['timestamps'])) {
                $_SESSION['rate_limits'][$ip]['timestamps'] = array_filter(
                    $data['timestamps'],
                    function ($timestamp) {
                        return $timestamp > ($this->currentTime - TIME_WINDOW);
                    }
                );
            }

            // Remove empty entries
            if (
                empty($_SESSION['rate_limits'][$ip]['timestamps'])
                && $_SESSION['rate_limits'][$ip]['blocked_until'] === 0
            ) {
                unset($_SESSION['rate_limits'][$ip]);
            }
        }
    }

    /**
     * Check if the IP is currently rate limited
     */
    public function isLimited(): bool
    {
        $data = $_SESSION['rate_limits'][$this->ip] ?? null;
        if (!$data) {
            return false;
        }

        // Check if IP is blocked
        if ($data['blocked_until'] > $this->currentTime) {
            error_log("IP {$this->ip} is rate limited until " . date('Y-m-d H:i:s', $data['blocked_until']));
            return true;
        }

        // Check request count
        $recentRequests = array_filter(
            $data['timestamps'],
            function ($timestamp) {
                return $timestamp > ($this->currentTime - TIME_WINDOW);
            }
        );

        if (count($recentRequests) >= MAX_REQUESTS) {
            $this->blockIP();
            error_log("IP {$this->ip} has been rate limited due to too many requests");
            return true;
        }

        return false;
    }

    /**
     * Record a new request
     */
    public function recordRequest(): void
    {
        $_SESSION['rate_limits'][$this->ip]['timestamps'][] = $this->currentTime;
    }

    /**
     * Get remaining allowed requests
     */
    public function getRemainingRequests(): int
    {
        $data = $_SESSION['rate_limits'][$this->ip] ?? null;
        if (!$data) {
            return MAX_REQUESTS;
        }

        $recentRequests = array_filter(
            $data['timestamps'],
            function ($timestamp) {
                return $timestamp > ($this->currentTime - TIME_WINDOW);
            }
        );

        return MAX_REQUESTS - count($recentRequests);
    }

    /**
     * Get remaining block time
     */
    public function getRemainingBlockTime(): int
    {
        $data = $_SESSION['rate_limits'][$this->ip] ?? null;
        if (!$data || !isset($data['blocked_until'])) {
            return 0;
        }

        $remaining = $data['blocked_until'] - $this->currentTime;
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Block the IP
     */
    private function blockIP(): void
    {
        $_SESSION['rate_limits'][$this->ip]['blocked_until'] = $this->currentTime + BLOCK_TIME;
    }
}
