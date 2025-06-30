<?php

namespace WPLite\Core;

/**
 * Security Configuration and Utilities
 * Centralized security settings and functions for WPLiteCore
 */
class Security
{
    // Rate limiting settings
    const DEFAULT_RATE_LIMIT = 100;
    const DEFAULT_TIME_WINDOW = 3600; // 1 hour
    
    // cURL security settings
    const CURL_TIMEOUT = 30;
    const CURL_CONNECT_TIMEOUT = 10;
    const MAX_REDIRECTS = 3;
    
    // Input validation limits
    const MAX_URL_LENGTH = 2048;
    const MAX_ENDPOINT_LENGTH = 100;
    const MAX_PARAMETER_LENGTH = 255;
    const MAX_PER_PAGE = 100;
    
    private static array $rateLimitData = [];
    
    /**
     * Get secure cURL options
     */
    public static function getSecureCurlOptions(): array
    {
        $options = [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CURL_CONNECT_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
            CURLOPT_USERAGENT => 'WPLiteCore/1.0'
        ];
        
        // Add SSL version if available
        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            $options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
        }
        
        // Add protocol restrictions if available
        if (defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        
        // Add DNS cache option if available
        if (defined('CURLOPT_DNS_USE_GLOBAL_CACHE')) {
            $options[CURLOPT_DNS_USE_GLOBAL_CACHE] = false;
        }
        
        // Add forbid reuse option if available
        if (defined('CURLOPT_FORBID_REUSE')) {
            $options[CURLOPT_FORBID_REUSE] = true;
        }
        
        return $options;
    }
    
    /**
     * Enhanced rate limiting with different strategies
     */
    public static function checkRateLimit(
        string $identifier, 
        int $maxRequests = self::DEFAULT_RATE_LIMIT, 
        int $timeWindow = self::DEFAULT_TIME_WINDOW,
        string $strategy = 'sliding_window'
    ): bool {
        switch ($strategy) {
            case 'token_bucket':
                return self::tokenBucketRateLimit($identifier, $maxRequests, $timeWindow);
            case 'fixed_window':
                return self::fixedWindowRateLimit($identifier, $maxRequests, $timeWindow);
            case 'sliding_window':
            default:
                return self::slidingWindowRateLimit($identifier, $maxRequests, $timeWindow);
        }
    }
    
    /**
     * Sliding window rate limiting
     */
    private static function slidingWindowRateLimit(string $identifier, int $maxRequests, int $timeWindow): bool
    {
        $key = 'sliding_' . md5($identifier);
        $now = time();
        
        if (!isset(self::$rateLimitData[$key])) {
            self::$rateLimitData[$key] = [];
        }
        
        // Remove old requests outside the time window
        self::$rateLimitData[$key] = array_filter(
            self::$rateLimitData[$key],
            fn($timestamp) => $now - $timestamp < $timeWindow
        );
        
        // Check if limit exceeded
        if (count(self::$rateLimitData[$key]) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        self::$rateLimitData[$key][] = $now;
        
        return true;
    }
    
    /**
     * Fixed window rate limiting
     */
    private static function fixedWindowRateLimit(string $identifier, int $maxRequests, int $timeWindow): bool
    {
        $key = 'fixed_' . md5($identifier);
        $now = time();
        $windowStart = floor($now / $timeWindow) * $timeWindow;
        
        if (!isset(self::$rateLimitData[$key]) || self::$rateLimitData[$key]['window'] !== $windowStart) {
            self::$rateLimitData[$key] = [
                'window' => $windowStart,
                'count' => 0
            ];
        }
        
        if (self::$rateLimitData[$key]['count'] >= $maxRequests) {
            return false;
        }
        
        self::$rateLimitData[$key]['count']++;
        
        return true;
    }
    
    /**
     * Token bucket rate limiting
     */
    private static function tokenBucketRateLimit(string $identifier, int $maxRequests, int $timeWindow): bool
    {
        $key = 'bucket_' . md5($identifier);
        $now = time();
        $fillRate = $maxRequests / $timeWindow; // tokens per second
        
        if (!isset(self::$rateLimitData[$key])) {
            self::$rateLimitData[$key] = [
                'tokens' => $maxRequests,
                'last_refill' => $now
            ];
        }
        
        // Refill tokens based on time passed
        $timePassed = $now - self::$rateLimitData[$key]['last_refill'];
        $tokensToAdd = $timePassed * $fillRate;
        
        self::$rateLimitData[$key]['tokens'] = min(
            $maxRequests,
            self::$rateLimitData[$key]['tokens'] + $tokensToAdd
        );
        self::$rateLimitData[$key]['last_refill'] = $now;
        
        // Check if we have tokens available
        if (self::$rateLimitData[$key]['tokens'] < 1) {
            return false;
        }
        
        // Consume a token
        self::$rateLimitData[$key]['tokens']--;
        
        return true;
    }
    
    /**
     * Validate IP address and check for suspicious patterns
     */
    public static function validateClientIp(): bool
    {
        $ip = self::getClientIp();
        
        // Check for private/internal IPs in production
        if (!self::isDebugMode() && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        
        // Basic IP reputation check (can be extended with external services)
        if (self::isKnownBadIp($ip)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get real client IP address
     */
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim($_SERVER[$header]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if IP is in known bad IP list
     */
    private static function isKnownBadIp(string $ip): bool
    {
        // This can be extended with external threat intelligence feeds
        $knownBadIps = [
            '192.0.2.0',  // RFC 3330 test range
            '198.51.100.0', // RFC 3330 test range
            '203.0.113.0'   // RFC 3330 test range
        ];
        
        return in_array($ip, $knownBadIps);
    }
    
    /**
     * Check if we're in debug mode
     */
    private static function isDebugMode(): bool
    {
        return (defined('WP_DEBUG') && WP_DEBUG) || 
               (defined('WPLITE_DEBUG') && WPLITE_DEBUG) ||
               (getenv('WPLITE_DEBUG') === 'true');
    }
    
    /**
     * Sanitize HTTP headers
     */
    public static function sanitizeHeaders(array $headers): array
    {
        $allowedHeaders = [
            'authorization',
            'content-type',
            'user-agent',
            'accept',
            'accept-language',
            'accept-encoding',
            'cache-control',
            'pragma',
            'x-requested-with'
        ];
        
        $sanitized = [];
        foreach ($headers as $name => $value) {
            $name = strtolower(trim($name));
            if (in_array($name, $allowedHeaders)) {
                $sanitized[$name] = Validator::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Generate secure random token
     */
    public static function generateSecureToken(int $length = 32): string
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            // Fallback (less secure)
            return bin2hex(substr(str_shuffle(str_repeat('0123456789abcdef', ceil($length / 16))), 0, $length / 2));
        }
    }
    
    /**
     * Hash sensitive data securely
     */
    public static function hashSensitiveData(string $data, string $salt = ''): string
    {
        if (empty($salt)) {
            $salt = self::generateSecureToken(16);
        }
        
        return hash_hmac('sha256', $data, $salt);
    }
    
    /**
     * Constant-time string comparison to prevent timing attacks
     */
    public static function secureCompare(string $a, string $b): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        
        // Fallback implementation
        if (strlen($a) !== strlen($b)) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result === 0;
    }
    
    /**
     * Clear rate limit data (useful for testing)
     */
    public static function clearRateLimitData(): void
    {
        self::$rateLimitData = [];
    }
}
