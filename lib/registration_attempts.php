<?php

namespace CleanSteg1\Security;

/**
 * RegistrationAttempts Class
 *
 * Handles rate limiting for user registrations to prevent abuse.
 *
 * @category   Security
 * @package    CleanSteg1
 * @subpackage Security
 */
class RegistrationAttempts
{
    /**
     * Maximum number of registrations allowed
     */
    private const MAX_REGISTRATIONS = 3;

    /**
     * Time window in seconds (5 minutes)
     */
    private const REGISTRATION_WINDOW = 300;

    /**
     * Lockout time in seconds (15 minutes)
     */
    private const LOCKOUT_TIME = 900;

    /**
     * Check if IP is rate limited for registrations
     *
     * @param string $ip IP address to check
     *
     * @return array{limited: bool, remaining_time?: int} Rate limit status
     */
    public static function checkLimit(string $ip): array
    {
        $currentTime = time();
        $attempts = self::getAttempts($ip);

        if (!$attempts) {
            return ['limited' => false];
        }

        // Check if IP is locked out
        if ($attempts['locked_until'] > $currentTime) {
            return [
                'limited' => true,
                'remaining_time' => $attempts['locked_until'] - $currentTime
            ];
        }

        // Check if within time window
        if (
            $attempts['count'] >= self::MAX_REGISTRATIONS &&
            $attempts['last_attempt'] > ($currentTime - self::REGISTRATION_WINDOW)
        ) {
            // Set lockout
            self::setLockout($ip, $currentTime + self::LOCKOUT_TIME);
            return [
                'limited' => true,
                'remaining_time' => self::LOCKOUT_TIME
            ];
        }

        return ['limited' => false];
    }

    /**
     * Record a registration attempt
     *
     * @param string $ip IP address to record
     *
     * @return void
     */
    public static function recordAttempt(string $ip): void
    {
        $currentTime = time();
        $attempts = self::getAttempts($ip);

        if (!$attempts) {
            // First attempt
            $attempts = [
                'count' => 1,
                'last_attempt' => $currentTime,
                'locked_until' => 0
            ];
        } else {
            // Check if we should reset the counter (outside time window)
            if ($attempts['last_attempt'] < ($currentTime - self::REGISTRATION_WINDOW)) {
                $attempts['count'] = 1;
            } else {
                $attempts['count']++;
            }
            $attempts['last_attempt'] = $currentTime;
        }

        self::storeAttempts($ip, $attempts);
    }

    /**
     * Reset registration attempts for an IP
     *
     * @param string $ip IP address to reset
     *
     * @return void
     */
    public static function resetAttempts(string $ip): void
    {
        if (isset($_SESSION['registration_attempts'][$ip])) {
            unset($_SESSION['registration_attempts'][$ip]);
        }
    }

    /**
     * Get registration attempts from session
     *
     * @param string $ip IP address to check
     *
     * @return array{count: int, last_attempt: int, locked_until: int}|null
     */
    private static function getAttempts(string $ip): ?array
    {
        if (!isset($_SESSION['registration_attempts'][$ip])) {
            return null;
        }
        return $_SESSION['registration_attempts'][$ip];
    }

    /**
     * Store registration attempts in session
     *
     * @param string $ip IP address to store
     * @param array $attempts Attempts data to store
     *
     * @return void
     */
    private static function storeAttempts(string $ip, array $attempts): void
    {
        if (!isset($_SESSION['registration_attempts'])) {
            $_SESSION['registration_attempts'] = [];
        }
        $_SESSION['registration_attempts'][$ip] = $attempts;
    }

    /**
     * Set registration lockout
     *
     * @param string $ip IP address to lock
     * @param int $lockoutTime Time until lockout expires
     *
     * @return void
     */
    private static function setLockout(string $ip, int $lockoutTime): void
    {
        $attempts = self::getAttempts($ip);
        if ($attempts) {
            $attempts['locked_until'] = $lockoutTime;
            self::storeAttempts($ip, $attempts);
        }
    }
}
