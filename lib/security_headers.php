<?php
/**
 * Setter sikkerhetsrelaterte HTTP-headers for applikasjonen
 * Disse headersene hjelper mot ulike web-sikkerhetsproblemer
 */
function setSecurityHeaders() {
    // Forhindre clickjacking
    header('X-Frame-Options: DENY');
    
    // Aktiver browser's XSS-beskyttelse
    header('X-XSS-Protection: 1; mode=block');
    
    // Forhindre MIME-type-sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Kontroller resurslast
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
    
    // Tving HTTPS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    
    // Kontroller referrer-informasjon
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Forhindre caching av følsomme sider
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

/**
 * Setter CORS-headers for API-sluttpunkter
 * @param bool $allowAllOrigins Om å tillate alle ruter (*) eller bare spesifikke
 */
function setCORSHeaders($allowAllOrigins = false) {
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
?> 