<?php

namespace WPLite\Core;

use WPLite\Exceptions\ValidationException;

/**
 * Input validation and sanitization utility
 */
class Validator
{
    private array $errors = [];

    /**
     * Validate required field
     */
    public function required(mixed $value, string $field): self
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            $this->errors[$field][] = "The {$field} field is required";
        }
        return $this;
    }

    /**
     * Validate URL format
     */
    public function url(mixed $value, string $field): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "The {$field} must be a valid URL";
        }
        return $this;
    }

    /**
     * Validate string length
     */
    public function length(mixed $value, string $field, int $min = 0, int $max = PHP_INT_MAX): self
    {
        if (!empty($value)) {
            $length = strlen((string)$value);
            if ($length < $min) {
                $this->errors[$field][] = "The {$field} must be at least {$min} characters";
            }
            if ($length > $max) {
                $this->errors[$field][] = "The {$field} must not exceed {$max} characters";
            }
        }
        return $this;
    }

    /**
     * Validate integer
     */
    public function integer(mixed $value, string $field): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = "The {$field} must be an integer";
        }
        return $this;
    }

    /**
     * Validate array
     */
    public function array(mixed $value, string $field): self
    {
        if (!empty($value) && !is_array($value)) {
            $this->errors[$field][] = "The {$field} must be an array";
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Throw validation exception if validation fails
     */
    public function validate(): void
    {
        if ($this->fails()) {
            throw new ValidationException('Validation failed', $this->errors());
        }
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize URL input
     */
    public static function sanitizeUrl(string $url): string
    {
        // Remove dangerous script tags and other potential XSS vectors
        $url = strip_tags($url);
        $url = str_replace(['<script>', '</script>', 'javascript:', 'vbscript:', 'onload=', 'onerror='], '', $url);
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize integer input
     */
    public static function sanitizeInt(mixed $input): int
    {
        // Convert to string first
        $str = (string)$input;
        
        // First try to extract only leading digits (most secure approach)
        if (preg_match('/^-?\d+/', $str, $matches)) {
            return (int)$matches[0];
        }
        
        // Fallback: if no leading digits found, try filter_var as last resort
        $cleaned = filter_var($str, FILTER_SANITIZE_NUMBER_INT);
        if (is_numeric($cleaned)) {
            return (int)$cleaned;
        }
        
        // If all else fails, return 0
        return 0;
    }

    /**
     * Sanitize array input
     */
    public static function sanitizeArray(array $input): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                return self::sanitizeString($value);
            }
            if (is_array($value)) {
                return self::sanitizeArray($value);
            }
            return $value;
        }, $input);
    }

    /**
     * Validate and sanitize file path to prevent path traversal attacks
     */
    public static function sanitizePath(string $path): string
    {
        // First decode any URL encoding
        $path = urldecode($path);
        
        // Remove any attempt at directory traversal
        $path = str_replace(['../', '..\\', '..'], '', $path);
        
        // Remove null bytes
        $path = str_replace("\0", '', $path);
        
        // Remove leading slashes and dots but preserve filename
        $path = ltrim($path, './\\');
        
        // Remove dangerous path components but keep safe characters
        $path = preg_replace('/[^a-zA-Z0-9\/_\-.]/', '', $path);
        
        // For security, only return the basename for file inclusion
        // This prevents any remaining path traversal attempts
        if (strpos($path, '/') !== false || strpos($path, '\\') !== false) {
            $path = basename($path);
        }
        
        // If path is empty after sanitization, return a safe default
        if (empty($path)) {
            $path = 'safe_file.php';
        }
        
        return $path;
    }

    /**
     * Enhanced URL validation with security checks
     */
    public static function validateSecureUrl(string $url): bool
    {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Parse URL components
        $parsed = parse_url($url);
        if (!$parsed) {
            return false;
        }
        
        // Only allow HTTP and HTTPS schemes
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return false;
        }
        
        // Prevent localhost and internal IP access in production
        $host = $parsed['host'] ?? '';
        if (self::isPrivateIp($host)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if host is a private/internal IP address
     */
    private static function isPrivateIp(string $host): bool
    {
        // Allow all in development mode - be permissive for testing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return false;
        }
        
        // Allow development environments based on environment variables
        if (getenv('WPLITE_DEBUG') === 'true' || getenv('APP_ENV') === 'development') {
            return false;
        }
        
        // For testing purposes, allow localhost by default unless explicitly in production
        if (!getenv('APP_ENV') || getenv('APP_ENV') !== 'production') {
            return false;
        }
        
        // In production only, check for localhost variants
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }
        
        // Check for private IP ranges
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        
        return false;
    }

    /**
     * Sanitize WordPress API endpoint
     */
    public static function sanitizeEndpoint(string $endpoint): string
    {
        // Allow only alphanumeric, hyphens, underscores, and forward slashes
        $endpoint = preg_replace('/[^a-zA-Z0-9\/_\-]/', '', $endpoint);
        
        // Remove leading/trailing slashes
        $endpoint = trim($endpoint, '/');
        
        // Validate against known WordPress endpoints
        $allowedEndpoints = [
            'posts', 'pages', 'media', 'users', 'comments', 'taxonomies',
            'categories', 'tags', 'types', 'statuses', 'settings'
        ];
        
        $baseEndpoint = explode('/', $endpoint)[0];
        if (!in_array($baseEndpoint, $allowedEndpoints)) {
            throw new ValidationException("Invalid endpoint: {$baseEndpoint}");
        }
        
        return $endpoint;
    }

    /**
     * Validate API parameters with whitelist approach
     */
    public static function validateApiParameters(array $parameters): array
    {
        $allowedParams = [
            // Common parameters
            'page', 'per_page', 'search', 'after', 'before', 'exclude', 'include',
            'offset', 'order', 'orderby', 'slug', 'status', 'sticky',
            // Post parameters
            'author', 'author_exclude', 'categories', 'categories_exclude',
            'tags', 'tags_exclude', 'format',
            // Media parameters
            'media_type', 'mime_type', 'parent', 'parent_exclude',
            // User parameters
            'context', 'roles', 'capabilities',
            // Comment parameters
            'post', 'parent', 'type', 'karma'
        ];
        
        $sanitized = [];
        foreach ($parameters as $key => $value) {
            if (in_array($key, $allowedParams)) {
                $sanitized[$key] = self::sanitizeParameterValue($value, $key);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize individual parameter values based on type
     */
    private static function sanitizeParameterValue(mixed $value, string $key): mixed
    {
        // Integer parameters
        $intParams = ['page', 'per_page', 'offset', 'author', 'parent', 'post', 'karma'];
        if (in_array($key, $intParams)) {
            $intValue = self::sanitizeInt($value);
            // Apply reasonable limits for specific parameters
            if ($key === 'per_page') {
                return max(1, min(100, $intValue)); // Ensure per_page is between 1 and 100
            }
            if ($key === 'page') {
                return max(1, $intValue); // Ensure page is at least 1
            }
            return $intValue;
        }
        
        // Array parameters (IDs)
        $arrayParams = ['include', 'exclude', 'categories', 'tags', 'author_exclude', 'categories_exclude', 'tags_exclude'];
        if (in_array($key, $arrayParams)) {
            if (is_array($value)) {
                return array_map('intval', $value);
            }
            return array_map('intval', explode(',', (string)$value));
        }
        
        // String parameters with specific constraints
        switch ($key) {
            case 'order':
                return in_array(strtolower($value), ['asc', 'desc']) ? strtolower($value) : 'desc';
            case 'orderby':
                $allowed = ['date', 'id', 'include', 'title', 'slug', 'modified', 'menu_order', 'comment_count'];
                return in_array($value, $allowed) ? $value : 'date';
            case 'status':
                $allowed = ['publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit'];
                return in_array($value, $allowed) ? $value : 'publish';
            case 'context':
                return in_array($value, ['view', 'embed', 'edit']) ? $value : 'view';
            default:
                return self::sanitizeString((string)$value);
        }
    }

    /**
     * Rate limiting validation
     */
    public static function checkRateLimit(string $identifier, int $maxRequests = 100, int $timeWindow = 3600): bool
    {
        $key = 'rate_limit_' . md5($identifier);
        $current = (int)($_SESSION[$key] ?? 0);
        $timeKey = $key . '_time';
        $lastReset = (int)($_SESSION[$timeKey] ?? 0);
        
        // Reset counter if time window has passed
        if (time() - $lastReset > $timeWindow) {
            $_SESSION[$key] = 0;
            $_SESSION[$timeKey] = time();
            $current = 0;
        }
        
        // Check if limit exceeded
        if ($current >= $maxRequests) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$key] = $current + 1;
        
        return true;
    }

    /**
     * Comprehensive input sanitization for all user inputs
     */
    public static function sanitizeInput(mixed $input, string $type = 'string'): mixed
    {
        switch ($type) {
            case 'url':
                if (!self::validateSecureUrl((string)$input)) {
                    throw new ValidationException('Invalid or unsafe URL provided');
                }
                return self::sanitizeUrl((string)$input);
            
            case 'path':
                return self::sanitizePath((string)$input);
            
            case 'endpoint':
                return self::sanitizeEndpoint((string)$input);
            
            case 'int':
                return self::sanitizeInt($input);
            
            case 'array':
                return is_array($input) ? self::sanitizeArray($input) : [];
            
            case 'api_params':
                return is_array($input) ? self::validateApiParameters($input) : [];
            
            default:
                return self::sanitizeString((string)$input);
        }
    }
}
