<?php

/**
 * Enhanced Routes Configuration with Caching Support
 * 
 * This file demonstrates how to integrate the cached routing system
 * into your WPLiteCore application setup.
 */

// Check if wlc_config.php exists
if (!file_exists('wlc_config.php')) {
    die('Please run create_requirements() function to create the necessary files and folders to effectively use this library.');
} else {
    // Include wlc_config.php
    include_once 'wlc_config.php';
}

// Require the autoload file for the project
require_once __DIR__ . '/../vendor/autoload.php';

// Include cached router functionality
require_once __DIR__ . '/../cached_router.php';

// Set the subfolder based on the current request URI
$uri = rtrim($_SERVER['REQUEST_URI'], '/');
$subFolder = preg_replace('/\/[^\/]+\.[a-zA-Z0-9]+$/', '', $uri);
$subFolder = $subFolder === '' ? '/' : $subFolder;

// Initialize cached router with configuration
init_cached_router([
    'enabled' => true,
    'default_ttl' => 3600, // 1 hour default
]);

// =========================
// CACHED ROUTES
// =========================

// Homepage - cached for 30 minutes
cached_get($subFolder.'', 'pages/index.php', [
    'ttl' => 1800,
    'cache_empty_responses' => false
]);

// API Routes with caching
wp_api_cached_route($subFolder.'/api/posts', function() {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->posts()->getAll(['per_page' => 10]);
}, [
    'ttl' => 1800 // 30 minutes
]);

wp_api_cached_route($subFolder.'/api/posts/$id', function($id) {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    $post = $wpLite->posts()->getById($id);
    
    if (!$post) {
        http_response_code(404);
        return ['error' => 'Post not found'];
    }
    
    return $post;
}, [
    'ttl' => 3600, // 1 hour
    'vary_by_params' => true
]);

wp_api_cached_route($subFolder.'/api/pages', function() {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->pages()->getAll(['per_page' => 10]);
}, [
    'ttl' => 7200 // 2 hours (pages change less frequently)
]);

wp_api_cached_route($subFolder.'/api/pages/$id', function($id) {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    $page = $wpLite->pages()->getById($id);
    
    if (!$page) {
        http_response_code(404);
        return ['error' => 'Page not found'];
    }
    
    return $page;
}, [
    'ttl' => 7200, // 2 hours
    'vary_by_params' => true
]);

// Search endpoint with query parameter caching
cached_get($subFolder.'/api/search', function() {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'posts';
    $limit = min((int)($_GET['limit'] ?? 10), 50); // Max 50 results
    
    if (empty($query)) {
        return json_encode(['error' => 'Query parameter required']);
    }
    
    try {
        $wpLite = \WPLite\WPLiteCore::getInstance();
        
        $results = [];
        if ($type === 'posts' || $type === 'all') {
            $posts = $wpLite->posts()->getAll([
                'search' => $query,
                'per_page' => $limit
            ]);
            $results['posts'] = $posts;
        }
        
        if ($type === 'pages' || $type === 'all') {
            $pages = $wpLite->pages()->getAll([
                'search' => $query,
                'per_page' => $limit
            ]);
            $results['pages'] = $pages;
        }
        
        return json_encode([
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'cached_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
    }
}, [
    'ttl' => 900, // 15 minutes
    'vary_by_params' => true
]);

// User profile endpoint (varies by authorization header)
cached_get($subFolder.'/api/user/profile', function() {
    // Check for authorization
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (empty($authHeader)) {
        http_response_code(401);
        return json_encode(['error' => 'Authorization required']);
    }
    
    // Simulate user profile data
    $userData = [
        'id' => hash('sha256', $authHeader), // Simulate user ID from auth
        'name' => 'Sample User',
        'email' => 'user@example.com',
        'last_login' => date('Y-m-d H:i:s'),
        'preferences' => [
            'theme' => 'light',
            'notifications' => true
        ]
    ];
    
    return json_encode($userData);
}, [
    'ttl' => 600, // 10 minutes
    'vary_by_headers' => ['Authorization']
]);

// Dashboard data aggregation
cached_get($subFolder.'/api/dashboard', function() {
    try {
        $wpLite = \WPLite\WPLiteCore::getInstance();
        
        $dashboard = [
            'recent_posts' => $wpLite->posts()->getAll(['per_page' => 5]),
            'recent_pages' => $wpLite->pages()->getAll(['per_page' => 3]),
            'stats' => [
                'cache_stats' => get_route_cache_stats(),
                'generated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        return json_encode($dashboard, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(['error' => 'Dashboard data failed: ' . $e->getMessage()]);
    }
}, [
    'ttl' => 1200 // 20 minutes
]);

// =========================
// CACHE MANAGEMENT ROUTES
// =========================

// Cache statistics endpoint
cached_get($subFolder.'/admin/cache/stats', function() {
    $stats = get_route_cache_stats();
    
    return json_encode([
        'cache_statistics' => $stats,
        'server_time' => date('Y-m-d H:i:s'),
        'cache_enabled' => get_cached_router()->isCacheEnabled()
    ], JSON_PRETTY_PRINT);
}, [
    'ttl' => 60 // Cache for 1 minute
]);

// Cache clearing endpoint (POST)
post($subFolder.'/admin/cache/clear', function() {
    $route = $_POST['route'] ?? '';
    
    if (empty($route)) {
        $success = clear_all_route_cache();
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'All route cache cleared successfully' : 'Failed to clear all cache'
        ]);
    } else {
        $cleared = clear_route_cache($route);
        echo json_encode([
            'success' => $cleared > 0,
            'cleared_entries' => $cleared,
            'message' => "Cleared {$cleared} cache entries for route pattern: {$route}"
        ]);
    }
});

// =========================
// REGULAR ROUTES (NON-CACHED)
// =========================

// Admin panel (should not be cached)
get($subFolder.'/admin', 'admin/index.php');

// User authentication (should not be cached)
post($subFolder.'/auth/login', 'auth/login.php');
post($subFolder.'/auth/logout', 'auth/logout.php');

// Contact form submission (should not be cached)
post($subFolder.'/contact/submit', 'contact/submit.php');

// =========================
// DYNAMIC EXAMPLES
// =========================

// Dynamic GET with 1 variable (cached)
cached_get($subFolder.'/user/$id', function($id) {
    // Simulate user data
    $userData = [
        'id' => $id,
        'name' => 'User ' . $id,
        'profile_url' => '/user/' . $id,
        'joined' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')),
        'cached_at' => date('Y-m-d H:i:s')
    ];
    
    return json_encode($userData, JSON_PRETTY_PRINT);
}, [
    'ttl' => 1800,
    'vary_by_params' => true
]);

// Dynamic GET with 2 variables (cached)
cached_get($subFolder.'/user/$name/$lastname', function($name, $lastname) {
    $fullName = ucfirst($name) . ' ' . ucfirst($lastname);
    
    $userData = [
        'name' => $name,
        'lastname' => $lastname,
        'full_name' => $fullName,
        'slug' => strtolower($name . '-' . $lastname),
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
    return json_encode($userData, JSON_PRETTY_PRINT);
}, [
    'ttl' => 3600,
    'vary_by_params' => true
]);

// Product catalog with categories (cached)
cached_get($subFolder.'/product/$type/color/$color', function($type, $color) {
    $product = [
        'type' => $type,
        'color' => $color,
        'id' => hash('crc32', $type . $color),
        'name' => ucfirst($color) . ' ' . ucfirst($type),
        'price' => rand(20, 200),
        'in_stock' => rand(0, 1) ? true : false,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    return json_encode($product, JSON_PRETTY_PRINT);
}, [
    'ttl' => 2400, // 40 minutes
    'vary_by_params' => true
]);

// =========================
// FALLBACK ROUTES
// =========================

// 404 error handling
any($subFolder.'/404', '404.php');

// Health check endpoint (not cached, for monitoring)
get($subFolder.'/health', function() {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'cache_enabled' => get_cached_router()->isCacheEnabled(),
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'uptime' => $_SERVER['REQUEST_TIME'] - (int)file_get_contents('/proc/uptime')
    ];
    
    header('Content-Type: application/json');
    echo json_encode($health, JSON_PRETTY_PRINT);
});

// =========================
// MIDDLEWARE EXAMPLES
// =========================

// Example of cache warming (can be called via cron)
get($subFolder.'/admin/cache/warm', function() {
    // Define routes to warm
    $routesToWarm = [
        '/api/posts',
        '/api/pages',
        '/api/dashboard'
    ];
    
    $warmed = 0;
    foreach ($routesToWarm as $route) {
        // Simulate request to warm cache
        $warmed++;
    }
    
    echo json_encode([
        'message' => 'Cache warming completed',
        'routes_warmed' => $warmed,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
});

// Debug route for developers
get($subFolder.'/debug/routes', function() {
    header('Content-Type: text/plain');
    
    echo "=== WPLiteCore Cached Router Debug ===\n\n";
    echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
    
    $stats = get_route_cache_stats();
    echo "Cache Statistics:\n";
    foreach ($stats as $key => $value) {
        $displayValue = is_array($value) ? json_encode($value) : $value;
        echo "  {$key}: {$displayValue}\n";
    }
    
    echo "\nServer Info:\n";
    echo "  PHP Version: " . PHP_VERSION . "\n";
    echo "  Memory Usage: " . memory_get_usage(true) . " bytes\n";
    echo "  Cache Status: " . (get_cached_router()->isCacheEnabled() ? 'ENABLED' : 'DISABLED') . "\n";
});
