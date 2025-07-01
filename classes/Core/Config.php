<?php

/**
 * WPLiteCore Configuration Handler
 * 
 * This class handles loading configuration from environment variables
 * and provides fallback to default values or config files.
 * 
 * Note: This is primarily used for library development and testing.
 * End users can simply use: WPLiteCore::create('api-url', 'secret-key')
 */

namespace WPLite\Core;

class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from environment and .env file
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Try to load .env file if it exists
        self::loadEnvFile();

        // Load configuration with fallbacks
        self::$config = [
            'api_url' => self::getEnv('WPLITE_API_URL', 'https://api.example.com/v2'),
            'hash_key' => self::getEnv('WPLITE_HASH_KEY', null),
            'site_url' => self::getEnv('WPLITE_SITE_URL', 'https://example.com'),
            'debug' => self::getEnv('WPLITE_DEBUG', 'false') === 'true',
            
            // Cache configuration
            'cache_enabled' => self::getEnv('WPLITE_CACHE_ENABLED', self::getConstant('WLC_CACHE_ENABLED', true)),
            'cache_ttl' => (int) self::getEnv('WPLITE_CACHE_TTL', self::getConstant('WLC_CACHE_TTL', 3600)),
            'cache_dir' => self::getEnv('WPLITE_CACHE_DIR', self::getConstant('WLC_CACHE_DIR', null)),
            'cache_ttl_posts' => (int) self::getEnv('WPLITE_CACHE_TTL_POSTS', self::getConstant('WLC_CACHE_TTL_POSTS', 1800)),
            'cache_ttl_pages' => (int) self::getEnv('WPLITE_CACHE_TTL_PAGES', self::getConstant('WLC_CACHE_TTL_PAGES', 7200)),
            'cache_ttl_media' => (int) self::getEnv('WPLITE_CACHE_TTL_MEDIA', self::getConstant('WLC_CACHE_TTL_MEDIA', 86400)),
            'cache_ttl_categories' => (int) self::getEnv('WPLITE_CACHE_TTL_CATEGORIES', self::getConstant('WLC_CACHE_TTL_CATEGORIES', 3600)),
            'cache_ttl_tags' => (int) self::getEnv('WPLITE_CACHE_TTL_TAGS', self::getConstant('WLC_CACHE_TTL_TAGS', 3600)),
            'cache_ttl_users' => (int) self::getEnv('WPLITE_CACHE_TTL_USERS', self::getConstant('WLC_CACHE_TTL_USERS', 7200)),
            'cache_ttl_comments' => (int) self::getEnv('WPLITE_CACHE_TTL_COMMENTS', self::getConstant('WLC_CACHE_TTL_COMMENTS', 900)),
            'cache_auto_cleanup' => self::getEnv('WPLITE_CACHE_AUTO_CLEANUP', self::getConstant('WLC_CACHE_AUTO_CLEANUP', true)),
            'cache_cleanup_probability' => (int) self::getEnv('WPLITE_CACHE_CLEANUP_PROBABILITY', self::getConstant('WLC_CACHE_CLEANUP_PROBABILITY', 10)),
            
            // Test configuration
            'test_post_id' => (int) self::getEnv('WPLITE_TEST_POST_ID', '32'),
            'test_media_id' => (int) self::getEnv('WPLITE_TEST_MEDIA_ID', '41'),
            'test_category_id' => (int) self::getEnv('WPLITE_TEST_CATEGORY_ID', '1'),
            'test_user_id' => (int) self::getEnv('WPLITE_TEST_USER_ID', '1'),
            'test_tag_id' => (int) self::getEnv('WPLITE_TEST_TAG_ID', '1'),
            
            // Additional API keys
            'wirefront_api_key' => self::getEnv('WIREFRONT_API_KEY', null),
        ];

        // Define constants for backward compatibility
        self::defineConstants();

        self::$loaded = true;
    }

    /**
     * Force reload configuration (useful for testing)
     */
    public static function reload(): void
    {
        self::$loaded = false;
        self::load();
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set(string $key, $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        self::$config[$key] = $value;
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Check if configuration is loaded and hash key is available
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return self::get('hash_key') !== null;
    }

    /**
     * Get hash key with validation
     *
     * @throws \RuntimeException If hash key is not configured
     * @return string
     */
    public static function getHashKey(): string
    {
        $hashKey = self::get('hash_key');
        
        if ($hashKey === null) {
            throw new \RuntimeException(
                'HASH_KEY is not configured. Please set WPLITE_HASH_KEY environment variable or create a .env file.'
            );
        }

        return $hashKey;
    }

    /**
     * Get hash key safely (returns null if not configured)
     *
     * @return string|null
     */
    public static function getHashKeySafe(): ?string
    {
        return self::get('hash_key');
    }

    /**
     * Get API URL
     *
     * @return string
     */
    public static function getApiUrl(): string
    {
        return self::get('api_url');
    }

    /**
     * Get site URL
     *
     * @return string
     */
    public static function getSiteUrl(): string
    {
        return self::get('site_url');
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public static function isDebug(): bool
    {
        return self::get('debug', false);
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public static function isCacheEnabled(): bool
    {
        return (bool) self::get('cache_enabled', true);
    }

    /**
     * Get cache TTL for specific endpoint type
     *
     * @param string $endpoint Endpoint type (posts, pages, media, etc.)
     * @return int TTL in seconds
     */
    public static function getCacheTtl(string $endpoint = ''): int
    {
        $endpoint = strtolower($endpoint);
        
        // Check for specific endpoint TTL
        switch ($endpoint) {
            case 'posts':
                return (int) self::get('cache_ttl_posts', 1800);
            case 'pages':
                return (int) self::get('cache_ttl_pages', 7200);
            case 'media':
                return (int) self::get('cache_ttl_media', 86400);
            case 'categories':
                return (int) self::get('cache_ttl_categories', 3600);
            case 'tags':
                return (int) self::get('cache_ttl_tags', 3600);
            case 'users':
                return (int) self::get('cache_ttl_users', 7200);
            case 'comments':
                return (int) self::get('cache_ttl_comments', 900);
            default:
                return (int) self::get('cache_ttl', 3600);
        }
    }

    /**
     * Get cache directory
     *
     * @return string|null
     */
    public static function getCacheDir(): ?string
    {
        return self::get('cache_dir');
    }

    /**
     * Check if auto cleanup is enabled
     *
     * @return bool
     */
    public static function isCacheAutoCleanupEnabled(): bool
    {
        return (bool) self::get('cache_auto_cleanup', true);
    }

    /**
     * Get cache cleanup probability percentage
     *
     * @return int
     */
    public static function getCacheCleanupProbability(): int
    {
        return (int) self::get('cache_cleanup_probability', 10);
    }

    /**
     * Load .env file if it exists
     */
    private static function loadEnvFile(): void
    {
        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    
                    // Set environment variable if not already set
                    if (!isset($_ENV[$key]) && getenv($key) === false) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Get environment variable with fallback
     *
     * @param string $key Environment variable name
     * @param mixed $default Default value
     * @return mixed
     */
    private static function getEnv(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        // Return default if value is false (not set) or empty string
        if ($value === false || $value === '') {
            return $default;
        }
        
        return $value;
    }

    /**
     * Get constant value with fallback
     *
     * @param string $name Constant name
     * @param mixed $default Default value
     * @return mixed
     */
    private static function getConstant(string $name, $default = null)
    {
        return defined($name) ? constant($name) : $default;
    }

    /**
     * Define constants for backward compatibility
     */
    private static function defineConstants(): void
    {
        // Only define if not already defined
        if (!defined('HASH_KEY') && self::$config['hash_key'] !== null) {
            define('HASH_KEY', self::$config['hash_key']);
        }
        
        if (!defined('api_url')) {
            define('api_url', self::$config['api_url']);
        }
        
        if (!defined('site_url')) {
            define('site_url', self::$config['site_url']);
        }
    }

    /**
     * Reset configuration (mainly for testing)
     */
    public static function reset(): void
    {
        self::$config = [];
        self::$loaded = false;
    }
}
