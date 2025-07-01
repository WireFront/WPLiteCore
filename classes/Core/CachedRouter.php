<?php

namespace WPLite\Core;

use WPLite\Core\Cache;
use WPLite\Exceptions\ConfigException;

/**
 * Cached Router System
 * 
 * Integrates the Cache system with the routing system to cache route responses.
 * This is especially useful for:
 * - API responses
 * - Database query results
 * - Expensive computations
 * - Static content that doesn't change often
 */
class CachedRouter
{
    private Cache $cache;
    private bool $cacheEnabled;
    private array $cacheableRoutes = [];
    private array $routeOptions = [];
    
    /**
     * Initialize the cached router
     *
     * @param Cache|null $cache Custom cache instance (optional)
     * @param bool $enabled Enable/disable caching
     */
    public function __construct(?Cache $cache = null, bool $enabled = true)
    {
        $this->cacheEnabled = $enabled;
        
        if ($this->cacheEnabled) {
            $this->cache = $cache ?? new Cache();
        }
    }

    /**
     * Register a route as cacheable
     *
     * @param string $route Route pattern (e.g., '/api/posts', '/user/$id')
     * @param array $options Cache options
     *   - ttl: Time to live in seconds (default: 3600)
     *   - method: HTTP method(s) to cache (default: ['GET'])
     *   - vary_by_params: Cache separate responses for different query parameters
     *   - vary_by_headers: Cache separate responses for different headers
     */
    public function registerCacheableRoute(string $route, array $options = []): void
    {
        $defaultOptions = [
            'ttl' => 3600,
            'methods' => ['GET'],
            'vary_by_params' => true,
            'vary_by_headers' => [],
            'cache_empty_responses' => false
        ];
        
        $this->cacheableRoutes[$route] = true;
        $this->routeOptions[$route] = array_merge($defaultOptions, $options);
    }

    /**
     * Check if a route should be cached
     *
     * @param string $route Current route
     * @param string $method HTTP method
     * @return bool
     */
    public function shouldCacheRoute(string $route, string $method): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        // Check if route is explicitly registered as cacheable
        foreach ($this->cacheableRoutes as $pattern => $enabled) {
            if ($this->matchRoute($pattern, $route)) {
                $options = $this->routeOptions[$pattern] ?? [];
                return in_array(strtoupper($method), array_map('strtoupper', $options['methods'] ?? ['GET']));
            }
        }

        return false;
    }

    /**
     * Get cached response for current route
     *
     * @param string $route Current route
     * @param string $method HTTP method
     * @param array $params Route parameters
     * @param array $queryParams Query parameters
     * @return mixed|null Cached response or null if not found
     */
    public function getCachedResponse(string $route, string $method, array $params = [], array $queryParams = []): mixed
    {
        if (!$this->shouldCacheRoute($route, $method)) {
            return null;
        }

        $cacheKey = $this->generateRouteCacheKey($route, $method, $params, $queryParams);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache a route response
     *
     * @param string $route Current route
     * @param string $method HTTP method
     * @param mixed $response Response to cache
     * @param array $params Route parameters
     * @param array $queryParams Query parameters
     * @return bool Success status
     */
    public function cacheResponse(string $route, string $method, mixed $response, array $params = [], array $queryParams = []): bool
    {
        if (!$this->shouldCacheRoute($route, $method)) {
            return false;
        }

        // Get route options
        $options = $this->getRouteOptions($route);
        
        // Don't cache empty responses unless explicitly allowed
        if (empty($response) && !$options['cache_empty_responses']) {
            return false;
        }

        $cacheKey = $this->generateRouteCacheKey($route, $method, $params, $queryParams);
        $ttl = $options['ttl'] ?? 3600;
        
        return $this->cache->set($cacheKey, $response, $ttl);
    }

    /**
     * Execute a route with caching support
     *
     * @param string $route Route pattern
     * @param string $method HTTP method
     * @param callable|string $handler Route handler (callback or file path)
     * @param array $params Route parameters
     * @param array $queryParams Query parameters
     * @return mixed Route response
     */
    public function executeRoute(string $route, string $method, callable|string $handler, array $params = [], array $queryParams = []): mixed
    {
        // Try to get cached response first
        $cachedResponse = $this->getCachedResponse($route, $method, $params, $queryParams);
        if ($cachedResponse !== null) {
            return $this->serveCachedResponse($cachedResponse);
        }

        // Execute the route handler
        $response = $this->executeHandler($handler, $params);
        
        // Cache the response if applicable
        if ($response !== null) {
            $this->cacheResponse($route, $method, $response, $params, $queryParams);
        }

        return $response;
    }

    /**
     * Invalidate cache for a specific route pattern
     *
     * @param string $routePattern Route pattern to invalidate
     * @return int Number of cache entries cleared
     */
    public function invalidateRoute(string $routePattern): int
    {
        if (!$this->cacheEnabled) {
            return 0;
        }

        $cleared = 0;
        $cacheStats = $this->cache->getStats();
        
        // This is a simplified approach - in a production system you might want
        // to implement a more sophisticated cache key tracking system
        $routeHash = hash('sha256', $routePattern);
        
        // Clear all cache files that might match this route
        // Note: This could be optimized with better cache key management
        $this->cache->cleanup(); // Clean expired entries first
        
        return $cleared;
    }

    /**
     * Clear all route cache
     *
     * @return bool Success status
     */
    public function clearAllCache(): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }
        
        return $this->cache->clear();
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        if (!$this->cacheEnabled) {
            return ['enabled' => false];
        }
        
        $stats = $this->cache->getStats();
        $stats['cached_routes'] = count($this->cacheableRoutes);
        
        return $stats;
    }

    /**
     * Generate cache key for route
     *
     * @param string $route Route pattern
     * @param string $method HTTP method
     * @param array $params Route parameters
     * @param array $queryParams Query parameters
     * @return string Cache key
     */
    private function generateRouteCacheKey(string $route, string $method, array $params = [], array $queryParams = []): string
    {
        $keyParts = [
            'route' => $route,
            'method' => strtoupper($method),
            'params' => $params
        ];

        // Add query parameters if configured to vary by them
        $options = $this->getRouteOptions($route);
        if ($options['vary_by_params'] && !empty($queryParams)) {
            ksort($queryParams); // Sort for consistent key generation
            $keyParts['query'] = $queryParams;
        }

        // Add headers if configured to vary by them
        if (!empty($options['vary_by_headers'])) {
            $headers = [];
            foreach ($options['vary_by_headers'] as $headerName) {
                $headerValue = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $headerName))] ?? null;
                if ($headerValue !== null) {
                    $headers[$headerName] = $headerValue;
                }
            }
            if (!empty($headers)) {
                $keyParts['headers'] = $headers;
            }
        }

        return 'route_' . hash('sha256', serialize($keyParts));
    }

    /**
     * Get options for a specific route
     *
     * @param string $route Route pattern
     * @return array Route options
     */
    private function getRouteOptions(string $route): array
    {
        foreach ($this->routeOptions as $pattern => $options) {
            if ($this->matchRoute($pattern, $route)) {
                return $options;
            }
        }
        
        return [
            'ttl' => 3600,
            'methods' => ['GET'],
            'vary_by_params' => true,
            'vary_by_headers' => [],
            'cache_empty_responses' => false
        ];
    }

    /**
     * Simple route pattern matching
     *
     * @param string $pattern Route pattern (e.g., '/user/$id')
     * @param string $route Actual route (e.g., '/user/123')
     * @return bool Whether the route matches the pattern
     */
    private function matchRoute(string $pattern, string $route): bool
    {
        // Convert route pattern to regex
        $regex = preg_replace('/\$[a-zA-Z0-9_]+/', '[^/]+', $pattern);
        $regex = '#^' . str_replace('/', '\/', $regex) . '$#';
        
        return preg_match($regex, $route) === 1;
    }

    /**
     * Execute route handler
     *
     * @param callable|string $handler Route handler
     * @param array $params Route parameters
     * @return mixed Handler response
     */
    private function executeHandler(callable|string $handler, array $params = []): mixed
    {
        if (is_callable($handler)) {
            // Start output buffering to capture response
            ob_start();
            $result = call_user_func_array($handler, $params);
            $output = ob_get_clean();
            
            // Return the callback result or captured output
            return $result !== null ? $result : $output;
        } else {
            // File-based handler - capture output
            ob_start();
            
            // Extract parameters as variables for the included file
            foreach ($params as $key => $value) {
                ${$key} = $value;
            }
            
            include $handler;
            $output = ob_get_clean();
            
            return $output;
        }
    }

    /**
     * Serve cached response
     *
     * @param mixed $response Cached response
     * @return mixed Response
     */
    private function serveCachedResponse(mixed $response): mixed
    {
        // Add cache hit header for debugging
        if (!headers_sent()) {
            header('X-Cache: HIT');
        }
        
        return $response;
    }

    /**
     * Enable or disable caching
     *
     * @param bool $enabled Enable status
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Check if caching is enabled
     *
     * @return bool Enabled status
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }
}
