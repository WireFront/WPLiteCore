<?php

/**
 * Cached Router Functions
 * 
 * Enhanced routing functions that integrate with the WPLite Cache system.
 * These functions extend the existing router.php functionality with caching capabilities.
 */

require_once __DIR__ . '/vendor/autoload.php';

use WPLite\Core\CachedRouter;
use WPLite\Core\Cache;

// Global cached router instance
$_wpLite_cachedRouter = null;

/**
 * Initialize the cached router system
 *
 * @param array $config Cache configuration
 *   - enabled: Enable/disable caching (default: true)
 *   - cache_dir: Cache directory path (optional)
 *   - default_ttl: Default TTL in seconds (default: 3600)
 */
function init_cached_router(array $config = []): void
{
    global $_wpLite_cachedRouter;
    
    $enabled = $config['enabled'] ?? true;
    $cacheDir = $config['cache_dir'] ?? null;
    $defaultTtl = $config['default_ttl'] ?? 3600;
    
    $cache = new Cache($cacheDir, $defaultTtl, $enabled);
    $_wpLite_cachedRouter = new CachedRouter($cache, $enabled);
}

/**
 * Get the cached router instance
 *
 * @return CachedRouter
 */
function get_cached_router(): CachedRouter
{
    global $_wpLite_cachedRouter;
    
    if ($_wpLite_cachedRouter === null) {
        init_cached_router();
    }
    
    return $_wpLite_cachedRouter;
}

/**
 * Register a cacheable GET route
 *
 * @param string $route Route pattern
 * @param callable|string $handler Route handler
 * @param array $cacheOptions Cache options
 */
function cached_get(string $route, callable|string $handler, array $cacheOptions = []): void
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        cached_route($route, $handler, 'GET', $cacheOptions);
    }
}

/**
 * Register a cacheable POST route (typically for read-only POST operations)
 *
 * @param string $route Route pattern
 * @param callable|string $handler Route handler
 * @param array $cacheOptions Cache options
 */
function cached_post(string $route, callable|string $handler, array $cacheOptions = []): void
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        cached_route($route, $handler, 'POST', $cacheOptions);
    }
}

/**
 * Register a cacheable route for any HTTP method
 *
 * @param string $route Route pattern
 * @param callable|string $handler Route handler
 * @param array $cacheOptions Cache options
 */
function cached_any(string $route, callable|string $handler, array $cacheOptions = []): void
{
    cached_route($route, $handler, $_SERVER['REQUEST_METHOD'], $cacheOptions);
}

/**
 * Core cached route function
 *
 * @param string $route Route pattern
 * @param callable|string $handler Route handler
 * @param string $method HTTP method
 * @param array $cacheOptions Cache options
 */
function cached_route(string $route, callable|string $handler, string $method, array $cacheOptions = []): void
{
    $cachedRouter = get_cached_router();
    
    // Register the route as cacheable if cache options are provided
    if (!empty($cacheOptions) || $method === 'GET') {
        $defaultOptions = $method === 'GET' ? ['methods' => ['GET']] : ['methods' => [$method]];
        $options = array_merge($defaultOptions, $cacheOptions);
        $cachedRouter->registerCacheableRoute($route, $options);
    }
    
    // Parse the current request
    $requestInfo = parse_current_request();
    $currentRoute = $requestInfo['route'];
    $routeParams = $requestInfo['params'];
    $queryParams = $requestInfo['query'];
    
    // Check if this route matches the current request
    if (matches_route_pattern($route, $currentRoute)) {
        // Extract route parameters
        $extractedParams = extract_route_parameters($route, $currentRoute);
        
        try {
            // Check for cached response first
            $cachedResponse = $cachedRouter->getCachedResponse($currentRoute, $method, $extractedParams, $queryParams);
            
            if ($cachedResponse !== null) {
                // Serve from cache
                if (!headers_sent()) {
                    header('X-Cache-Status: HIT');
                    header('Content-Type: ' . ($cachedResponse['content_type'] ?? 'text/html'));
                }
                echo $cachedResponse['content'];
                exit();
            }
            
            // Cache miss - execute handler and cache result
            ob_start();
            
            if (is_callable($handler)) {
                $response = call_user_func_array($handler, array_values($extractedParams));
                if (is_string($response)) {
                    echo $response;
                }
            } else {
                // Include file handler (similar to original router)
                if (!strpos($handler, '.php')) {
                    $handler .= '.php';
                }
                
                // Security checks (from original router)
                $handler = sanitize_file_path($handler);
                $fullPath = getcwd() . "/" . $handler;
                $realPath = realpath($fullPath);
                $basePath = realpath(getcwd());
                
                if (!$realPath || strpos($realPath, $basePath) !== 0) {
                    error_log("WPLiteCore Security: Path traversal attempt blocked: " . $handler);
                    http_response_code(404);
                    if (file_exists(getcwd() . "/404.php")) {
                        include_once getcwd() . "/404.php";
                    } else {
                        echo '404 - Page Not Found';
                    }
                    exit();
                }
                
                // Make route parameters available to included file
                foreach ($extractedParams as $key => $value) {
                    $$key = $value;
                }
                
                include_once $fullPath;
            }
            
            $output = ob_get_contents();
            ob_end_clean();
            
            // Cache the response if the route should be cached
            if ($cachedRouter->shouldCacheRoute($currentRoute, $method)) {
                $cachedRouter->cacheResponse($currentRoute, $method, $output, $extractedParams, $queryParams);
            }
            
            // Send response
            if (!headers_sent()) {
                header('X-Cache-Status: MISS');
            }
            echo $output;
            exit();
            
        } catch (Exception $e) {
            // Log error
            error_log("WPLiteCore Cached Router Error: " . $e->getMessage());
            
            // Send error response
            http_response_code(500);
            echo "Internal Server Error";
            exit();
        }
    }
}

/**
 * API-specific cached route (optimized for API responses)
 *
 * @param string $route Route pattern
 * @param callable|string $handler Route handler
 * @param array $options API cache options
 */
function cached_api_route(string $route, callable|string $handler, array $options = []): void
{
    $defaultOptions = [
        'ttl' => 1800, // 30 minutes for API responses
        'methods' => ['GET', 'POST'],
        'vary_by_params' => true,
        'vary_by_headers' => ['Authorization', 'X-API-Key'],
        'cache_empty_responses' => false
    ];
    
    $cacheOptions = array_merge($defaultOptions, $options);
    
    // Set appropriate headers for API responses
    if (!headers_sent()) {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=' . $cacheOptions['ttl']);
    }
    
    cached_any($route, $handler, $cacheOptions);
}

/**
 * Cache management functions
 */

/**
 * Clear cache for a specific route pattern
 *
 * @param string $routePattern Route pattern to clear
 * @return int Number of entries cleared
 */
function clear_route_cache(string $routePattern): int
{
    return get_cached_router()->invalidateRoute($routePattern);
}

/**
 * Clear all route cache
 *
 * @return bool Success status
 */
function clear_all_route_cache(): bool
{
    return get_cached_router()->clearAllCache();
}

/**
 * Get route cache statistics
 *
 * @return array Cache statistics
 */
function get_route_cache_stats(): array
{
    return get_cached_router()->getCacheStats();
}

/**
 * Helper functions for route processing
 */

/**
 * Parse current request information
 *
 * @return array Request information
 */
function parse_current_request(): array
{
    $requestUrl = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    $requestUrl = rtrim($requestUrl, '/');
    $requestUrl = strtok($requestUrl, '?');
    
    // Remove potentially dangerous characters
    $requestUrl = preg_replace('/[^a-zA-Z0-9\/_\-.]/', '', $requestUrl);
    
    // Parse query parameters
    $queryParams = [];
    if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $queryParams);
    }
    
    return [
        'route' => $requestUrl,
        'params' => [],
        'query' => $queryParams,
        'method' => $_SERVER['REQUEST_METHOD']
    ];
}

/**
 * Check if a route pattern matches the current route
 *
 * @param string $pattern Route pattern
 * @param string $currentRoute Current route
 * @return bool Whether it matches
 */
function matches_route_pattern(string $pattern, string $currentRoute): bool
{
    $patternParts = explode('/', trim($pattern, '/'));
    $routeParts = explode('/', trim($currentRoute, '/'));
    
    if (count($patternParts) !== count($routeParts)) {
        return false;
    }
    
    for ($i = 0; $i < count($patternParts); $i++) {
        $patternPart = $patternParts[$i];
        $routePart = $routeParts[$i];
        
        // Skip variable parts (starting with $)
        if (strpos($patternPart, '$') === 0) {
            continue;
        }
        
        // Exact match required for static parts
        if ($patternPart !== $routePart) {
            return false;
        }
    }
    
    return true;
}

/**
 * Extract parameters from route based on pattern
 *
 * @param string $pattern Route pattern
 * @param string $currentRoute Current route
 * @return array Extracted parameters
 */
function extract_route_parameters(string $pattern, string $currentRoute): array
{
    $patternParts = explode('/', trim($pattern, '/'));
    $routeParts = explode('/', trim($currentRoute, '/'));
    $params = [];
    
    for ($i = 0; $i < count($patternParts); $i++) {
        $patternPart = $patternParts[$i];
        
        if (strpos($patternPart, '$') === 0) {
            $paramName = substr($patternPart, 1);
            $paramValue = $routeParts[$i] ?? '';
            
            // Sanitize parameter value
            $paramValue = sanitize_route_parameter($paramValue);
            $params[$paramName] = $paramValue;
        }
    }
    
    return $params;
}

/**
 * Enhanced route helper for WordPress API integration
 *
 * @param string $route Route pattern
 * @param callable $apiHandler Handler that returns API data
 * @param array $options Cache options
 */
function wp_api_cached_route(string $route, callable $apiHandler, array $options = []): void
{
    $defaultOptions = [
        'ttl' => 3600, // 1 hour for WordPress content
        'methods' => ['GET'],
        'vary_by_params' => true
    ];
    
    $cacheOptions = array_merge($defaultOptions, $options);
    
    cached_get($route, function(...$params) use ($apiHandler) {
        try {
            $data = call_user_func_array($apiHandler, $params);
            
            // Set appropriate headers
            if (!headers_sent()) {
                header('Content-Type: application/json');
                header('X-Powered-By: WPLiteCore');
            }
            
            // Return JSON response
            return json_encode($data, JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            // Handle API errors
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            
            return json_encode([
                'error' => true,
                'message' => 'API Error: ' . $e->getMessage()
            ]);
        }
    }, $cacheOptions);
}

/**
 * Middleware for cache warming
 *
 * @param array $routes Array of routes to warm
 */
function warm_route_cache(array $routes): void
{
    foreach ($routes as $route) {
        // This would typically be done via background job
        // For now, we'll just ensure the routes are registered
        if (isset($route['pattern']) && isset($route['handler'])) {
            $options = $route['options'] ?? [];
            get_cached_router()->registerCacheableRoute($route['pattern'], $options);
        }
    }
}

/**
 * Security functions (from original router.php)
 */

/**
 * Sanitize file path to prevent directory traversal attacks
 */
if (!function_exists('sanitize_file_path')) {
    function sanitize_file_path($path) {
        // Remove any attempt at directory traversal
        $path = str_replace(['../', '..\\', '..', './', '.\\'], '', $path);
        
        // Remove null bytes
        $path = str_replace("\0", '', $path);
        
        // Remove dangerous characters
        $path = preg_replace('/[^a-zA-Z0-9\/_\-.]/', '', $path);
        
        // Remove leading slashes and dots
        $path = ltrim($path, './\\');
        
        return $path;
    }
}

/**
 * Sanitize route parameters
 */
if (!function_exists('sanitize_route_parameter')) {
    function sanitize_route_parameter($param) {
        // Remove null bytes and dangerous characters
        $param = str_replace("\0", '', $param);
        
        // Basic sanitization - allow alphanumeric, hyphens, underscores
        $param = preg_replace('/[^a-zA-Z0-9\-_]/', '', $param);
        
        // Limit length
        $param = substr($param, 0, 255);
        
        return $param;
    }
}
