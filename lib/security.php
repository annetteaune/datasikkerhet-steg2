<?php

/**
 * Security Configuration
 *
 * This file contains security configurations that should be applied
 * at the start of each request.
 *
 * PHP version 7.4
 */

declare(strict_types=1);

namespace CleanSteg1\Security;

// Include required files
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/security_headers.php';

// Initialize rate limiter
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

// Apply security headers
setSecurityHeaders();
