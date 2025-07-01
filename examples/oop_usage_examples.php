<?php

/**
 * WPLiteCore OOP Usage Examples
 * 
 * This file demonstrates how to use the new Object-Oriented classes
 * alongside the existing procedural functions from functions.php
 */

require_once 'vendor/autoload.php';

use WPLite\WPLiteCore;
use WPLite\Exceptions\ApiException;
use WPLite\Exceptions\ValidationException;
use WPLite\Exceptions\ConfigException;

// Include the original functions.php to show compatibility
// require_once 'functions.php';

echo "WPLiteCore OOP Examples\n";
echo "======================\n\n";

try {
    // Method 1: Using the main factory class (Singleton pattern)
    // This will use config from constants (api_url, HASH_KEY) if available
    echo "1. Initializing WPLiteCore (Singleton)\n";
    $wpLite = WPLiteCore::getInstance(
        'https://your-wordpress-site.com/wp-json/wp/v2',
        'your-secret-key',
        true // Debug mode
    );
    echo "✅ WPLiteCore initialized successfully\n\n";

    // Method 2: Creating multiple instances for different sites
    echo "2. Creating multiple instances\n";
    $site1 = WPLiteCore::create('https://site1.com/wp-json/wp/v2', 'key1');
    $site2 = WPLiteCore::create('https://site2.com/wp-json/wp/v2', 'key2');
    echo "✅ Multiple instances created\n\n";

    // Example 3: Getting posts (equivalent to wlc_get_api_data with posts endpoint)
    echo "3. Getting Posts (OOP vs Procedural)\n";
    
    // NEW OOP WAY:
    $postsResponse = $wpLite->posts()->getPosts([
        'per_page' => 5,
        'status' => 'publish'
    ]);
    
    if ($postsResponse->isSuccess()) {
        echo "✅ OOP: Found " . $postsResponse->getItemCount() . " posts\n";
        echo "   Total posts: " . $postsResponse->getTotalPosts() . "\n";
        echo "   Total pages: " . $postsResponse->getTotalPages() . "\n";
    }

    // OLD PROCEDURAL WAY (still works):
    /*
    $postsLegacy = wlc_get_api_data([
        'key' => 'your-secret-key',
        'api_url' => 'https://your-wordpress-site.com/wp-json/wp/v2',
        'endpoint' => 'posts',
        'parameters' => ['per_page' => 5, 'status' => 'publish']
    ]);
    
    if ($postsLegacy['result']) {
        echo "✅ Procedural: Found " . count($postsLegacy['items']) . " posts\n";
    }
    */
    
    echo "\n";

    // Example 4: Getting a single post (equivalent to wlc_single_post)
    echo "4. Getting Single Post\n";
    
    // NEW OOP WAY:
    $postResponse = $wpLite->posts()->getPostBySlug('sample-post', 'large');
    
    if ($postResponse->isSuccess()) {
        $post = $postResponse->getItems();
        echo "✅ OOP: Retrieved post - " . ($post['title'] ?? 'No title') . "\n";
        echo "   Featured image: " . ($post['featured_image'] ? 'Available' : 'None') . "\n";
        echo "   Comments: " . count($post['comments'] ?? []) . "\n";
    }

    // OLD PROCEDURAL WAY (still works):
    /*
    $postLegacy = wlc_single_post([
        'key' => 'your-secret-key',
        'api_url' => 'https://your-wordpress-site.com/wp-json/wp/v2',
        'type' => 'posts',
        'slug' => 'sample-post',
        'media_size' => 'large'
    ]);
    
    if (isset($postLegacy['title'])) {
        echo "✅ Procedural: Retrieved post - " . $postLegacy['title'] . "\n";
    }
    */
    
    echo "\n";

    // Example 5: Getting pages
    echo "5. Getting Pages\n";
    
    $pagesResponse = $wpLite->pages()->getPages(['per_page' => 3]);
    
    if ($pagesResponse->isSuccess()) {
        echo "✅ Found " . $pagesResponse->getItemCount() . " pages\n";
    }
    
    echo "\n";

    // Example 6: Getting media/featured images
    echo "6. Getting Media\n";
    
    $imageUrl = $wpLite->getFeaturedImage(123, 'medium');
    
    if ($imageUrl) {
        echo "✅ Featured image URL: " . $imageUrl . "\n";
    } else {
        echo "❌ Featured image not found\n";
    }
    
    echo "\n";

    // Example 7: Advanced usage with method chaining
    echo "7. Advanced Usage\n";
    
    // Get recent posts by category
    $recentPosts = $wpLite->posts()->getPostsByCategory(1, ['per_page' => 3]);
    
    // Search posts
    $searchResults = $wpLite->posts()->searchPosts('WordPress');
    
    // Get child pages
    $childPages = $wpLite->pages()->getChildPages(10);
    
    echo "✅ Advanced methods executed successfully\n\n";

    // Example 8: Error handling comparison
    echo "8. Error Handling\n";
    
    try {
        // This will fail gracefully with proper error handling
        $invalidResponse = $wpLite->posts()->getPostBySlug('non-existent-post');
        
        if ($invalidResponse->isFailure()) {
            echo "✅ Graceful error handling: Post not found\n";
        }
        
    } catch (ApiException $e) {
        echo "✅ API Exception caught: " . $e->getMessage() . "\n";
    } catch (ValidationException $e) {
        echo "✅ Validation Exception caught: " . json_encode($e->getErrors()) . "\n";
    }
    
    echo "\n";

    // Example 9: Backward compatibility - Converting OOP responses to legacy format
    echo "9. Backward Compatibility\n";
    
    $oopResponse = $wpLite->posts()->getPosts(['per_page' => 2]);
    $legacyFormat = $oopResponse->toArray(); // Converts to old format
    
    echo "✅ OOP response converted to legacy format:\n";
    echo "   Result: " . ($legacyFormat['result'] ? 'true' : 'false') . "\n";
    echo "   Items count: " . count($legacyFormat['items'] ?? []) . "\n";
    echo "   Total posts: " . ($legacyFormat['total_posts'] ?? 'null') . "\n";
    
    echo "\n";

    // Example 10: Using different clients for the same instance
    echo "10. Using Different Clients\n";
    
    // Direct API client access for custom endpoints
    $customResponse = $wpLite->api()->getData('categories', ['per_page' => 5]);
    
    // Specialized clients
    $postsClient = $wpLite->posts();
    $pagesClient = $wpLite->pages();
    $mediaClient = $wpLite->media();
    
    echo "✅ All clients accessible and working\n\n";

} catch (ConfigException $e) {
    echo "❌ Configuration Error: " . $e->getMessage() . "\n";
} catch (ApiException $e) {
    echo "❌ API Error: " . $e->getMessage() . "\n";
    echo "   HTTP Status: " . $e->getHttpStatusCode() . "\n";
} catch (ValidationException $e) {
    echo "❌ Validation Error: " . $e->getMessage() . "\n";
    echo "   Errors: " . json_encode($e->getErrors()) . "\n";
} catch (\Exception $e) {
    echo "❌ Unexpected Error: " . $e->getMessage() . "\n";
}

echo "\nComparison Summary:\n";
echo "===================\n";
echo "✅ PROCEDURAL (functions.php) - Still works exactly as before\n";
echo "✅ OOP (New classes) - Modern, type-safe, better error handling\n";
echo "✅ BOTH can be used together in the same project\n";
echo "✅ OOP responses can be converted to legacy format\n";
echo "✅ Gradual migration is possible\n\n";

echo "Migration Strategy:\n";
echo "==================\n";
echo "1. Start using OOP classes for NEW code\n";
echo "2. Keep existing procedural code unchanged\n";
echo "3. Gradually replace procedural calls with OOP when refactoring\n";
echo "4. Use ->toArray() method for compatibility with existing templates\n";
echo "5. Eventually phase out procedural functions when ready\n";
