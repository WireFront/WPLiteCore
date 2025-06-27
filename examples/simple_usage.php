<?php

/**
 * WPLiteCore Simple Usage Examples
 * 
 * This file shows how to use WPLiteCore in your projects
 * with and without JWT authentication.
 */

require_once 'vendor/autoload.php';

use WPLite\WPLiteCore;

// Example 1: Basic usage WITHOUT authentication (for public APIs)
echo "🔓 Example 1: Public API (no authentication)\n";
$wpLitePublic = WPLiteCore::create(
    'https://your-wordpress-api.com/wp-json/wp/v2',
    null  // No secret key needed for public content
);

// Example 2: Basic usage WITH authentication (for protected content)
echo "🔐 Example 2: Protected API (with authentication)\n";
$wpLitePrivate = WPLiteCore::create(
    'https://your-wordpress-api.com/wp-json/wp/v2',
    'your-secret-key'
);

// Get posts (works with both approaches)
$posts = $wpLitePublic->posts()->getPosts(['per_page' => 5]);
if ($posts->isSuccess()) {
    foreach ($posts->getItems() as $post) {
        echo "Post: " . $post['title']['rendered'] . "\n";
    }
}

// Example 2: Get a specific post with featured image
$post = $wpLitePublic->posts()->getPostById(1, 'medium');
if ($post->isSuccess()) {
    $postData = $post->getItems();
    echo "Title: " . $postData['title']['rendered'] . "\n";
    echo "Featured Image: " . ($postData['featured_image'] ?? 'None') . "\n";
}

// Example 3: Using procedural functions WITHOUT authentication
echo "🔓 Example 3: Procedural function (no authentication)\n";
$result = wlc_single_post([
    'key' => null,  // No authentication needed
    'api_url' => 'https://your-wordpress-api.com/wp-json/wp/v2/',
    'id' => 1,
    'type' => 'posts',
    'media_size' => 'medium'
]);

// Example 4: Using procedural functions WITH authentication  
echo "🔐 Example 4: Procedural function (with authentication)\n";
if (!defined('HASH_KEY')) {
    define('HASH_KEY', 'your-secret-key');
}
if (!defined('api_url')) {
    define('api_url', 'https://your-wordpress-api.com/wp-json/wp/v2');
}

$result = wlc_single_post([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-wordpress-api.com/wp-json/wp/v2/',
    'id' => 1,
    'type' => 'posts',
    'media_size' => 'medium'
]);

if ($result && isset($result['title'])) {
    echo "Post Title: " . $result['title'] . "\n";
}

// Example 5: Error handling
try {
    $nonExistentPost = $wpLitePublic->posts()->getPostById(99999);
    if ($nonExistentPost->isFailure()) {
        echo "Post not found\n";
    }
} catch (\WPLite\Exceptions\ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}

echo "\n✅ That's it! No JWT tokens required for public content.\n";
echo "🔐 Use JWT tokens only when accessing protected/private content.\n";
