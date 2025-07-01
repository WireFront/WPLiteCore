<?php

/**
 * Example implementation of WPLiteCore Cached Routing System
 * 
 * This file demonstrates how to use the cached routing system
 * with various scenarios including API endpoints, static content,
 * and dynamic content.
 */

require_once __DIR__ . '/cached_router.php';
require_once __DIR__ . '/classes/WPLiteCore.php';

// Initialize the cached router system
init_cached_router([
    'enabled' => true,
    'default_ttl' => 3600, // 1 hour default cache
]);

// Example 1: Basic cached GET route
cached_get('/cached-hello', function() {
    return "Hello World! Generated at: " . date('Y-m-d H:i:s');
}, [
    'ttl' => 300, // Cache for 5 minutes
]);

// Example 2: WordPress API integration with caching
wp_api_cached_route('/api/posts', function() {
    try {
        $wpLite = \WPLite\WPLiteCore::getInstance();
        $posts = $wpLite->posts()->getAll(['per_page' => 10]);
        return $posts;
    } catch (Exception $e) {
        throw new Exception('Failed to fetch posts: ' . $e->getMessage());
    }
}, [
    'ttl' => 1800, // Cache for 30 minutes
]);

// Example 3: Dynamic route with parameters
cached_get('/api/post/$id', function($id) {
    try {
        $wpLite = \WPLite\WPLiteCore::getInstance();
        $post = $wpLite->posts()->getById($id);
        
        if (!$post) {
            http_response_code(404);
            return json_encode(['error' => 'Post not found']);
        }
        
        return json_encode($post);
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(['error' => $e->getMessage()]);
    }
}, [
    'ttl' => 3600, // Cache for 1 hour
    'vary_by_params' => true
]);

// Example 4: User-specific content (varies by headers)
cached_get('/api/user/profile', function() {
    // This would typically get user info based on authentication
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? 'anonymous';
    
    return json_encode([
        'user_id' => $userId,
        'profile' => [
            'name' => 'Sample User',
            'last_login' => date('Y-m-d H:i:s')
        ]
    ]);
}, [
    'ttl' => 600, // Cache for 10 minutes
    'vary_by_headers' => ['X-User-ID', 'Authorization']
]);

// Example 5: API endpoint with query parameter variation
cached_get('/api/search', function() {
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    
    // Simulate search results
    $results = [
        'query' => $query,
        'results' => [
            ['title' => 'Sample Result 1', 'score' => 95],
            ['title' => 'Sample Result 2', 'score' => 89],
        ],
        'total' => 2,
        'limit' => $limit,
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
    return json_encode($results);
}, [
    'ttl' => 900, // Cache for 15 minutes
    'vary_by_params' => true
]);

// Example 6: Heavy computation with long cache time
cached_get('/api/analytics/report', function() {
    // Simulate heavy computation
    sleep(2); // Simulate processing time
    
    $report = [
        'total_visitors' => rand(1000, 5000),
        'page_views' => rand(5000, 25000),
        'bounce_rate' => rand(20, 80) . '%',
        'generated_at' => date('Y-m-d H:i:s'),
        'processing_time' => '2 seconds (simulated)'
    ];
    
    return json_encode($report, JSON_PRETTY_PRINT);
}, [
    'ttl' => 7200, // Cache for 2 hours
]);

// Example 7: Cache management endpoint
cached_get('/admin/cache/stats', function() {
    $stats = get_route_cache_stats();
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    return json_encode([
        'cache_stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}, [
    'ttl' => 60, // Cache stats for 1 minute
]);

// Example 8: Cache invalidation endpoint
cached_post('/admin/cache/clear', function() {
    $route = $_POST['route'] ?? '';
    
    if (empty($route)) {
        // Clear all cache
        $success = clear_all_route_cache();
        return json_encode([
            'success' => $success,
            'message' => $success ? 'All cache cleared' : 'Failed to clear cache'
        ]);
    } else {
        // Clear specific route
        $cleared = clear_route_cache($route);
        return json_encode([
            'success' => $cleared > 0,
            'message' => "Cleared {$cleared} cache entries for route: {$route}"
        ]);
    }
}, [
    'ttl' => 0, // Don't cache this endpoint
]);

// Example 9: File-based cached route
cached_get('/static/about', 'pages/about.php', [
    'ttl' => 86400, // Cache for 24 hours (static content)
]);

// Example 10: Complex WordPress content aggregation
wp_api_cached_route('/api/homepage-data', function() {
    try {
        $wpLite = \WPLite\WPLiteCore::getInstance();
        
        // Fetch multiple types of content
        $data = [
            'latest_posts' => $wpLite->posts()->getAll(['per_page' => 5]),
            'featured_pages' => $wpLite->pages()->getAll(['per_page' => 3]),
            'site_info' => [
                'name' => 'WPLiteCore Demo',
                'description' => 'Cached WordPress API Demo',
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ];
        
        return $data;
    } catch (Exception $e) {
        throw new Exception('Failed to fetch homepage data: ' . $e->getMessage());
    }
}, [
    'ttl' => 2400, // Cache for 40 minutes
]);

// Fallback for non-cached routes (404 handling)
any('/404', function() {
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The requested page could not be found.</p>";
    echo "<p><a href='/'>Go back to homepage</a></p>";
});

// Debug information endpoint (not cached)
get('/debug/routing', function() {
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    
    echo "=== WPLiteCore Cached Router Debug Info ===\n\n";
    echo "Current Request: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    $stats = get_route_cache_stats();
    echo "Cache Stats:\n";
    foreach ($stats as $key => $value) {
        echo "- {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
    
    echo "\nCache is " . (get_cached_router()->isCacheEnabled() ? 'ENABLED' : 'DISABLED') . "\n";
});

// Cache warming example (could be called via cron job)
if (isset($_GET['warm_cache']) && $_GET['warm_cache'] === 'true') {
    warm_route_cache([
        [
            'pattern' => '/api/posts',
            'handler' => function() { /* handler */ },
            'options' => ['ttl' => 1800]
        ],
        [
            'pattern' => '/api/homepage-data',
            'handler' => function() { /* handler */ },
            'options' => ['ttl' => 2400]
        ]
    ]);
    
    echo "Cache warming initiated...\n";
}
