# WPLiteCore Cached Routing System

## Overview

The WPLiteCore Cached Routing System integrates the existing Cache class with the routing system to provide high-performance, cached route responses. This is especially beneficial for:

- API endpoints with expensive operations
- Database-heavy routes
- WordPress API integrations
- Static content that changes infrequently
- User-specific content that can be cached per user

## Features

- **File-based caching** - Uses the existing WPLite Cache system
- **TTL (Time To Live) support** - Configurable cache expiration
- **Parameter variation** - Cache different responses for different route parameters
- **Header variation** - Cache different responses based on request headers
- **Method-specific caching** - Cache only specific HTTP methods
- **Cache invalidation** - Clear specific routes or all cache
- **Performance monitoring** - Built-in cache statistics
- **WordPress API integration** - Specialized functions for WordPress API caching

## Quick Start

### 1. Initialize the Cached Router

```php
<?php
require_once 'cached_router.php';

// Initialize with default settings
init_cached_router([
    'enabled' => true,
    'default_ttl' => 3600, // 1 hour
]);
```

### 2. Define Cached Routes

```php
// Basic cached GET route
cached_get('/api/posts', function() {
    // Expensive operation here
    return json_encode($posts);
}, [
    'ttl' => 1800, // 30 minutes
]);

// WordPress API integration
wp_api_cached_route('/api/posts', function() {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->posts()->getAll(['per_page' => 10]);
}, [
    'ttl' => 1800
]);
```

## Configuration Options

### Cache Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `ttl` | int | 3600 | Time to live in seconds |
| `methods` | array | ['GET'] | HTTP methods to cache |
| `vary_by_params` | bool | true | Cache separately for different query parameters |
| `vary_by_headers` | array | [] | Headers to vary cache by |
| `cache_empty_responses` | bool | false | Whether to cache empty responses |

### Router Configuration

```php
init_cached_router([
    'enabled' => true,           // Enable/disable caching
    'cache_dir' => '/custom/path', // Custom cache directory
    'default_ttl' => 3600,       // Default TTL in seconds
]);
```

## Available Functions

### Route Registration Functions

#### `cached_get($route, $handler, $options = [])`
Register a cacheable GET route.

```php
cached_get('/api/users', function() {
    return getUsersFromDatabase();
}, [
    'ttl' => 1800,
    'vary_by_params' => true
]);
```

#### `cached_post($route, $handler, $options = [])`
Register a cacheable POST route (for read-only POST operations).

```php
cached_post('/api/search', function() {
    $query = $_POST['query'];
    return searchDatabase($query);
}, [
    'ttl' => 900,
    'vary_by_params' => true
]);
```

#### `cached_any($route, $handler, $options = [])`
Register a cacheable route for any HTTP method.

#### `wp_api_cached_route($route, $handler, $options = [])`
Specialized function for WordPress API routes with JSON response handling.

```php
wp_api_cached_route('/api/posts/$id', function($id) {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->posts()->getById($id);
}, [
    'ttl' => 3600,
    'vary_by_params' => true
]);
```

### Cache Management Functions

#### `clear_route_cache($routePattern)`
Clear cache for a specific route pattern.

```php
$cleared = clear_route_cache('/api/posts');
echo "Cleared {$cleared} cache entries";
```

#### `clear_all_route_cache()`
Clear all route cache.

```php
$success = clear_all_route_cache();
```

#### `get_route_cache_stats()`
Get cache statistics.

```php
$stats = get_route_cache_stats();
echo "Cache files: " . $stats['total_files'];
echo "Cache size: " . $stats['total_size_formatted'];
```

## Advanced Usage

### Dynamic Routes with Parameters

```php
// Route with single parameter
cached_get('/user/$id', function($id) {
    return getUserById($id);
}, [
    'ttl' => 1800,
    'vary_by_params' => true // Cache different responses for different IDs
]);

// Route with multiple parameters
cached_get('/product/$category/$id', function($category, $id) {
    return getProduct($category, $id);
}, [
    'ttl' => 3600,
    'vary_by_params' => true
]);
```

### Header-Based Caching

```php
// Cache different responses based on user authorization
cached_get('/api/user/profile', function() {
    $userId = getCurrentUserId();
    return getUserProfile($userId);
}, [
    'ttl' => 600,
    'vary_by_headers' => ['Authorization', 'X-User-ID']
]);
```

### API Endpoints with Error Handling

```php
wp_api_cached_route('/api/posts/$id', function($id) {
    try {
        $wpLite = \WPLite\WPLiteCore::getInstance();
        $post = $wpLite->posts()->getById($id);
        
        if (!$post) {
            http_response_code(404);
            return ['error' => 'Post not found'];
        }
        
        return $post;
    } catch (Exception $e) {
        http_response_code(500);
        return ['error' => 'Internal server error'];
    }
}, [
    'ttl' => 3600,
    'vary_by_params' => true,
    'cache_empty_responses' => false // Don't cache error responses
]);
```

### Cache Management Routes

```php
// Cache statistics endpoint
cached_get('/admin/cache/stats', function() {
    return get_route_cache_stats();
}, ['ttl' => 60]);

// Cache clearing endpoint
post('/admin/cache/clear', function() {
    $route = $_POST['route'] ?? '';
    
    if (empty($route)) {
        $success = clear_all_route_cache();
        return json_encode(['success' => $success]);
    } else {
        $cleared = clear_route_cache($route);
        return json_encode(['cleared' => $cleared]);
    }
});
```

## Performance Benefits

### Typical Performance Improvements

- **API Routes**: 50-95% faster response times
- **Database Queries**: 80-99% reduction in query time
- **Complex Computations**: 90-99% faster execution
- **File Operations**: 70-90% faster file access

### Example Performance Test

```php
// Without caching
$start = microtime(true);
$posts = $wpLite->posts()->getAll(); // ~200ms
$time1 = microtime(true) - $start;

// With caching (second request)
$start = microtime(true);
$posts = getCachedPosts(); // ~2ms
$time2 = microtime(true) - $start;

// Performance improvement: ~100x faster
```

## Best Practices

### 1. Choose Appropriate TTL Values

```php
// Frequently changing content
cached_get('/api/news', $handler, ['ttl' => 300]); // 5 minutes

// Moderately changing content
cached_get('/api/posts', $handler, ['ttl' => 1800]); // 30 minutes

// Rarely changing content
cached_get('/api/pages', $handler, ['ttl' => 7200]); // 2 hours

// Static content
cached_get('/api/site-info', $handler, ['ttl' => 86400]); // 24 hours
```

### 2. Use Parameter Variation Wisely

```php
// Good: Cache varies by important parameters
cached_get('/api/search', $handler, [
    'vary_by_params' => true, // Different cache for different search queries
    'ttl' => 900
]);

// Avoid: Too many parameter variations can reduce cache efficiency
// Consider normalizing parameters or using shorter TTL
```

### 3. Implement Cache Invalidation

```php
// Clear specific caches when content changes
function onPostUpdate($postId) {
    clear_route_cache('/api/posts');
    clear_route_cache('/api/posts/' . $postId);
}

// Scheduled cache cleanup
function dailyCacheCleanup() {
    $router = get_cached_router();
    $router->cleanup(); // Remove expired entries
}
```

### 4. Monitor Cache Performance

```php
// Regular monitoring
$stats = get_route_cache_stats();
$hitRate = $stats['valid_files'] / $stats['total_files'];

if ($hitRate < 0.8) {
    // Consider adjusting TTL values or cache strategy
}
```

## Integration with Existing Routes

### Upgrading Existing Routes

```php
// Before (regular routing)
get('/api/posts', 'api/posts.php');

// After (with caching)
cached_get('/api/posts', 'api/posts.php', [
    'ttl' => 1800
]);
```

### Mixed Routing

```php
// Some routes cached
cached_get('/api/posts', $handler, ['ttl' => 1800]);
cached_get('/api/pages', $handler, ['ttl' => 3600]);

// Some routes not cached (user-specific, changing data)
post('/api/user/update', $handler); // Don't cache user updates
get('/api/user/notifications', $handler); // Real-time data
```

## Troubleshooting

### Common Issues

1. **Cache not working**
   - Check if caching is enabled: `get_cached_router()->isCacheEnabled()`
   - Verify cache directory permissions
   - Check TTL values are not too low

2. **Stale data**
   - Implement proper cache invalidation
   - Reduce TTL for frequently changing content
   - Clear cache manually when needed

3. **Poor cache hit rate**
   - Review parameter variation settings
   - Check if routes are properly registered as cacheable
   - Monitor cache statistics

4. **Memory usage**
   - Implement regular cache cleanup
   - Set appropriate TTL values
   - Monitor cache directory size

### Debug Mode

```php
// Enable debug headers
get('/debug/cache', function() {
    $stats = get_route_cache_stats();
    header('Content-Type: application/json');
    echo json_encode($stats, JSON_PRETTY_PRINT);
});
```

## Testing

Run the comprehensive test suite:

```bash
php test_cached_routing.php
```

This will test:
- Basic cache functionality
- Route registration and matching
- Performance improvements
- Error handling
- Integration capabilities

## Examples

Complete examples are available in:
- `examples/cached_routing_examples.php` - Comprehensive usage examples
- `setup-files/routes_with_cache.php` - Production-ready route configuration
- `test_cached_routing.php` - Test suite and performance benchmarks

## Compatibility

- **PHP**: 8.0+
- **WPLiteCore**: All versions with Cache class
- **WordPress**: Compatible with WordPress REST API
- **Server**: Works with Apache, Nginx, and other web servers

## Performance Considerations

- Cache files are stored in the filesystem
- Each cached route creates a separate file
- Memory usage is minimal (cache data not kept in memory)
- Disk I/O is optimized with file locking
- Automatic cleanup of expired entries

## Security

- Cache files are protected with .htaccess
- Route parameters are sanitized
- File paths are validated to prevent traversal attacks
- Cache keys are hashed to prevent direct access
