<?php

/**
 * Rate Limiter Implementation
 *
 * This file contains a rate limiting implementation to prevent abuse of the API
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

// Konstanter for rate limiting
define('MAX_REQUESTS', 60);  // Maksimal antall forespørsler per vindu
define('TIME_WINDOW', 60);   // Tidsvindu i sekunder (1 minutt)
define('BLOCK_TIME', 30);    // Blokkeringstid i sekunder (5 minutter)

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
        $this->ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $this->currentTime = time();
    }

    /**
     * Sjekk om IP-en er nåværende rate limited
     *
     * @return bool True if IP is rate limited, false otherwise
     */
    public function isLimited(): bool
    {
        $requests = $this->getRequests();
        if (!$requests) {
            return false;
        }

        // Sjekk om IP-en er blokkert
        if (
            isset($requests['blocked_until'])
            && $requests['blocked_until'] > $this->currentTime
        ) {
            return true;
        }

        // Rens gamle forespørsler utenfor tidsvinduet
        $requests['timestamps'] = array_filter(
            $requests['timestamps'],
            function ($timestamp) {
                return $timestamp > ($this->currentTime - TIME_WINDOW);
            }
        );

        // Hvis for mange forespørsler i tidsvinduet, blokker IP-en
        if (count($requests['timestamps']) >= MAX_REQUESTS) {
            $this->blockIP();
            return true;
        }

        return false;
    }

    /**
     * Registrer en ny forespørsel
     *
     * @return void
     */
    public function recordRequest(): void
    {
        $requests = $this->getRequests();
        if (!$requests) {
            $requests = [
                'timestamps' => [],
                'blocked_until' => 0
            ];
        }

        // Rens gamle forespørsler
        $requests['timestamps'] = array_filter(
            $requests['timestamps'],
            function ($timestamp) {
                return $timestamp > ($this->currentTime - TIME_WINDOW);
            }
        );

        // Legg til ny forespørselstidspunkt
        $requests['timestamps'][] = $this->currentTime;
        $this->storeRequests($requests);
    }

    /**
     * Hent gjenstående forespørsler
     *
     * @return int Number of remaining requests
     */
    public function getRemainingRequests(): int
    {
        $requests = $this->getRequests();
        if (!$requests) {
            return MAX_REQUESTS;
        }

        // Rens gamle forespørsler
        $requests['timestamps'] = array_filter(
            $requests['timestamps'],
            function ($timestamp) {
                return $timestamp > ($this->currentTime - TIME_WINDOW);
            }
        );
        return MAX_REQUESTS - count($requests['timestamps']);
    }

    /**
     * Hent gjenstående blokkeringstid
     *
     * @return int Remaining block time in seconds
     */
    public function getRemainingBlockTime(): int
    {
        $requests = $this->getRequests();
        if (!$requests || !isset($requests['blocked_until'])) {
            return 0;
        }

        $remaining = $requests['blocked_until'] - $this->currentTime;
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Blokker IP-en
     *
     * @return void
     */
    private function blockIP(): void
    {
        $requests = $this->getRequests() ?? ['timestamps' => []];
        $requests['blocked_until'] = $this->currentTime + BLOCK_TIME;
        $this->storeRequests($requests);
    }

    /**
     * Hent lagrede forespørsler for IP-en
     *
     * @return array|null Array of requests or null if none exist
     */
    private function getRequests(): ?array
    {
        if (!isset($_SESSION['rate_limits'][$this->ip])) {
            return null;
        }
        return $_SESSION['rate_limits'][$this->ip];
    }

    /**
     * Lagre forespørsler for IP-en
     *
     * @param array $requests Array of requests to store
     *
     * @return void
     */
    private function storeRequests(array $requests): void
    {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        $_SESSION['rate_limits'][$this->ip] = $requests;
    }
}

/**
 * Apply rate limiting to the current request
 *
 * @return void
 */
function applyRateLimit(): void
{
    $limiter = new RateLimiter();
    if ($limiter->isLimited()) {
        http_response_code(429);
        header(
            sprintf(
                'Retry-After: %d',
                $limiter->getRemainingBlockTime()
            )
        );
        exit('Too Many Requests');
    }
    $limiter->recordRequest();
}
