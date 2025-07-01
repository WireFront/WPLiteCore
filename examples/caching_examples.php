<?php

/**
 * WPLiteCore Caching Examples
 * 
 * This file demonstrates how to use the caching system
 * for improved performance when making WordPress API requests.
 */

require_once 'vendor/autoload.php';

// Include your configuration file with caching settings
require_once 'setup-files/wlc_config.php';

use WPLite\WPLiteCore;

// Initialize WPLiteCore (caching will be auto-configured from wlc_config.php)
$wpLite = WPLiteCore::create(
    'https://your-wordpress-site.com/wp-json/wp/v2',
    'your-secret-key'
);

$api = $wpLite->api();

echo "=== WPLiteCore Caching Examples ===\n\n";

// Example 1: Basic usage (caching is automatic)
echo "1. Basic API Request (with automatic caching):\n";
$posts = $api->getData('posts', ['per_page' => 5]);

if ($posts->isSuccess()) {
    echo "   Retrieved " . count($posts->getItems()) . " posts\n";
    echo "   First request - data fetched from API and cached\n";
}

// Example 2: Same request again (will use cache)
echo "\n2. Same Request Again (from cache):\n";
$cachedPosts = $api->getData('posts', ['per_page' => 5]);

if ($cachedPosts->isSuccess()) {
    echo "   Retrieved " . count($cachedPosts->getItems()) . " posts\n";
    echo "   Second request - data served from cache (faster!)\n";
}

// Example 3: Disable caching for specific request
echo "\n3. Request Without Caching:\n";
$freshPosts = $api->getData('posts', ['per_page' => 5], null, false); // Last parameter disables cache
echo "   This request bypasses cache and fetches fresh data\n";

// Example 4: Check cache status
echo "\n4. Cache Information:\n";
echo "   Cache enabled: " . ($api->isCacheEnabled() ? 'Yes' : 'No') . "\n";

$stats = $api->getCacheStats();
if ($stats['enabled']) {
    echo "   Total cache files: " . $stats['total_files'] . "\n";
    echo "   Valid cache files: " . $stats['valid_files'] . "\n";
    echo "   Expired cache files: " . $stats['expired_files'] . "\n";
    echo "   Total cache size: " . $stats['total_size_formatted'] . "\n";
}

// Example 5: Clear specific cache
echo "\n5. Cache Management:\n";
$cleared = $api->clearCache('posts', ['per_page' => 5]);
echo "   Cleared specific cache entry: " . ($cleared ? 'Success' : 'Failed') . "\n";

// Example 6: Clear all cache
$allCleared = $api->clearAllCache();
echo "   Cleared all cache: " . ($allCleared ? 'Success' : 'Failed') . "\n";

// Example 7: Runtime cache control
echo "\n6. Runtime Cache Control:\n";
echo "   Current cache status: " . ($api->isCacheEnabled() ? 'Enabled' : 'Disabled') . "\n";

// Temporarily disable caching
$api->setCacheEnabled(false);
echo "   Cache disabled for next requests\n";

$noCachePosts = $api->getData('posts', ['per_page' => 3]);
echo "   Request made without caching\n";

// Re-enable caching
$api->setCacheEnabled(true);
echo "   Cache re-enabled\n";

// Example 8: Different endpoints have different cache TTL
echo "\n7. Different Cache Durations by Endpoint:\n";
echo "   Posts cache for: " . \WPLite\Core\Config::getCacheTtl('posts') . " seconds\n";
echo "   Pages cache for: " . \WPLite\Core\Config::getCacheTtl('pages') . " seconds\n";
echo "   Media cache for: " . \WPLite\Core\Config::getCacheTtl('media') . " seconds\n";
echo "   Comments cache for: " . \WPLite\Core\Config::getCacheTtl('comments') . " seconds\n";

echo "\n=== Configuration Tips ===\n";
echo "
To customize caching behavior, edit your wlc_config.php file:

1. Enable/Disable Caching:
   define('WLC_CACHE_ENABLED', true);

2. Set Default Cache Duration:
   define('WLC_CACHE_TTL', 3600); // 1 hour

3. Set Endpoint-Specific Durations:
   define('WLC_CACHE_TTL_POSTS', 1800);    // 30 minutes
   define('WLC_CACHE_TTL_PAGES', 7200);    // 2 hours
   define('WLC_CACHE_TTL_MEDIA', 86400);   // 24 hours

4. Custom Cache Directory:
   define('WLC_CACHE_DIR', '/path/to/your/cache');

5. Auto Cleanup:
   define('WLC_CACHE_AUTO_CLEANUP', true);
   define('WLC_CACHE_CLEANUP_PROBABILITY', 10); // 10% chance per request
";

echo "\n=== Performance Benefits ===\n";
echo "
Caching provides several benefits:

1. ⚡ Faster Response Times
   - Cached responses are served instantly
   - No network latency for cached data

2. 🔄 Reduced API Load
   - Fewer requests to your WordPress API
   - Less server resource usage

3. 💰 Lower Bandwidth Costs
   - Especially beneficial for high-traffic applications
   - Reduces external API calls

4. 🛡️ Improved Reliability
   - Cached data available even if API is temporarily down
   - Graceful degradation

5. 📱 Better User Experience
   - Faster page loads
   - More responsive applications
";

echo "\n=== Best Practices ===\n";
echo "
1. Choose appropriate TTL values:
   - Posts: 15-30 minutes (frequently updated)
   - Pages: 1-2 hours (less frequently updated)
   - Media: 24 hours (rarely changes)
   - Comments: 15 minutes (real-time feel)

2. Use cache clearing strategically:
   - Clear specific caches when content updates
   - Don't clear all cache unless necessary

3. Monitor cache performance:
   - Check cache hit rates
   - Monitor cache size growth
   - Clean up expired entries regularly

4. Consider your use case:
   - High-traffic sites benefit most from caching
   - Real-time applications may need shorter TTL
   - Static content sites can use longer TTL
";

echo "\nDone! Check your cache directory for stored files.\n";
