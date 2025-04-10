<?php

/**
 * Security Headers Configuration
 *
 * This file contains functions for setting security-related HTTP headers
 * that help protect against various web vulnerabilities.
 *
 * PHP version 8.0
 *
 * @category   Security
 * @package    CleanSteg1
 * @subpackage Security
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

/**
 * Sets security-related HTTP headers for the application
 * These headers help protect against various web vulnerabilities
 *
 * @return void
 */
function setSecurityHeaders()
{
    // Forhindre clickjacking
    header('X-Frame-Options: DENY');
// Aktiver nettleserens XSS-beskyttelse
    header('X-XSS-Protection: 1; mode=block');
// Forhindre MIME-type-sniffing
    header('X-Content-Type-Options: nosniff');
// Kontroller resurslast
    header("Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
        "style-src 'self' 'unsafe-inline';");
// Tving HTTPS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
// Kontroller referrer-informasjon
    header('Referrer-Policy: strict-origin-when-cross-origin');
// Forhindre caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

/**
 * Sets CORS headers for API endpoints
 *
 * @param bool $allowAllOrigins Om å tillate alle ruter (*) eller bare spesifikke
 *
 * @return void
 */
function setCORSHeaders($allowAllOrigins = false)
{
    if ($allowAllOrigins) {
        header('Access-Control-Allow-Origin: *');
    } else {
    // Erstatt med domene domene i produksjon
        header('Access-Control-Allow-Origin: http://localhost');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // 24 timer
}
