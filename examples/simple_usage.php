<?php

/**
 * WPLiteCore Simple Usage Examples
 * 
 * This file shows how to use WPLiteCore in your projects
 * without needing any complex configuration setup.
 */

require_once 'vendor/autoload.php';

use WPLite\WPLiteCore;

// Example 1: Basic usage with your API
$wpLite = WPLiteCore::create(
    'https://your-wordpress-api.com/wp-json/wp/v2',
    'your-secret-key'
);

// Get posts
$posts = $wpLite->posts()->getPosts(['per_page' => 5]);
if ($posts->isSuccess()) {
    foreach ($posts->getItems() as $post) {
        echo "Post: " . $post['title']['rendered'] . "\n";
    }
}

// Example 2: Get a specific post with featured image
$post = $wpLite->posts()->getPostById(1, 'medium');
if ($post->isSuccess()) {
    $postData = $post->getItems();
    echo "Title: " . $postData['title']['rendered'] . "\n";
    echo "Featured Image: " . ($postData['featured_image'] ?? 'None') . "\n";
}

// Example 3: Using procedural functions (backward compatibility)
// You'll need to define constants first:
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

// Example 4: Error handling
try {
    $nonExistentPost = $wpLite->posts()->getPostById(99999);
    if ($nonExistentPost->isFailure()) {
        echo "Post not found\n";
    }
} catch (\WPLite\Exceptions\ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}

echo "\n✅ That's it! No .env files or complex setup needed for end users.\n";
