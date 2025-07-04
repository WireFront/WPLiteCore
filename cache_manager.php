<?php

/**
 * WPLiteCore Cache Management Script
 * 
 * Use this script to manage your WPLiteCore cache from the command line
 * 
 * Usage:
 *   php cache_manager.php [command] [options]
 * 
 * Commands:
 *   stats    - Show cache statistics
 *   clear    - Clear all cache
 *   cleanup  - Remove expired cache entries
 *   info     - Show cache configuration
 */

// Only include autoloader if WPLite classes are not already available
if (!class_exists('WPLite\Core\Cache')) {
    // Try to find the appropriate autoloader
    $possibleAutoloaders = [
        __DIR__ . '/vendor/autoload.php',                    // Package standalone
        __DIR__ . '/../../../autoload.php',                  // Package installed via Composer
        __DIR__ . '/../../../../autoload.php',               // Different nesting level
        getcwd() . '/vendor/autoload.php',                   // Main project autoloader
    ];
    
    $autoloaderFound = false;
    foreach ($possibleAutoloaders as $autoloader) {
        if (file_exists($autoloader)) {
            require_once $autoloader;
            $autoloaderFound = true;
            break;
        }
    }
    
    // If no autoloader found and classes still not available, show helpful error
    if (!$autoloaderFound || !class_exists('WPLite\Core\Cache')) {
        throw new Exception(
            'WPLiteCore autoloader not found. Please ensure Composer autoload is properly configured. ' .
            'Expected WPLite\Core\Cache class to be available.'
        );
    }
}

require_once 'setup-files/wlc_config.php';

use WPLite\Core\Cache;
use WPLite\Core\Config;

// Load configuration
Config::load();

// Initialize cache
$cache = new Cache(
    Config::getCacheDir(),
    Config::getCacheTtl(),
    Config::isCacheEnabled()
);

function printUsage() {
    echo "WPLiteCore Cache Manager\n";
    echo "Usage: php cache_manager.php [command]\n\n";
    echo "Commands:\n";
    echo "  stats    - Show cache statistics\n";
    echo "  clear    - Clear all cache files\n";
    echo "  cleanup  - Remove expired cache entries\n";
    echo "  info     - Show cache configuration\n";
    echo "  help     - Show this help message\n\n";
}

function showStats(Cache $cache) {
    echo "=== Cache Statistics ===\n";
    $stats = $cache->getStats();
    
    if (!$stats['enabled']) {
        echo "Cache is DISABLED\n";
        return;
    }
    
    echo "Cache Status: ENABLED\n";
    echo "Cache Directory: " . $stats['cache_dir'] . "\n";
    echo "Default TTL: " . $stats['default_ttl'] . " seconds\n";
    echo "Total Files: " . $stats['total_files'] . "\n";
    echo "Valid Files: " . $stats['valid_files'] . "\n";
    echo "Expired Files: " . $stats['expired_files'] . "\n";
    echo "Total Size: " . $stats['total_size_formatted'] . "\n";
    
    if ($stats['expired_files'] > 0) {
        echo "\n⚠️  You have " . $stats['expired_files'] . " expired cache files.\n";
        echo "   Run 'php cache_manager.php cleanup' to remove them.\n";
    }
}

function clearCache(Cache $cache) {
    echo "=== Clearing All Cache ===\n";
    
    if (!$cache->isEnabled()) {
        echo "Cache is disabled. Nothing to clear.\n";
        return;
    }
    
    $stats = $cache->getStats();
    echo "Found " . $stats['total_files'] . " cache files to clear...\n";
    
    if ($cache->clear()) {
        echo "✅ Successfully cleared all cache files!\n";
    } else {
        echo "❌ Failed to clear cache files.\n";
    }
}

function cleanupCache(Cache $cache) {
    echo "=== Cleaning Up Expired Cache ===\n";
    
    if (!$cache->isEnabled()) {
        echo "Cache is disabled. Nothing to cleanup.\n";
        return;
    }
    
    $stats = $cache->getStats();
    echo "Found " . $stats['expired_files'] . " expired cache files...\n";
    
    $cleaned = $cache->cleanup();
    
    if ($cleaned > 0) {
        echo "✅ Successfully cleaned up " . $cleaned . " expired cache files!\n";
    } else {
        echo "✨ No expired cache files found. Cache is clean!\n";
    }
}

function showInfo() {
    echo "=== Cache Configuration ===\n";
    
    echo "Cache Enabled: " . (Config::isCacheEnabled() ? 'Yes' : 'No') . "\n";
    echo "Cache Directory: " . (Config::getCacheDir() ?: 'Default') . "\n";
    echo "Default TTL: " . Config::getCacheTtl() . " seconds\n";
    echo "Auto Cleanup: " . (Config::isCacheAutoCleanupEnabled() ? 'Yes' : 'No') . "\n";
    echo "Cleanup Probability: " . Config::getCacheCleanupProbability() . "%\n";
    
    echo "\nEndpoint-Specific TTL:\n";
    echo "  Posts: " . Config::getCacheTtl('posts') . " seconds\n";
    echo "  Pages: " . Config::getCacheTtl('pages') . " seconds\n";
    echo "  Media: " . Config::getCacheTtl('media') . " seconds\n";
    echo "  Categories: " . Config::getCacheTtl('categories') . " seconds\n";
    echo "  Tags: " . Config::getCacheTtl('tags') . " seconds\n";
    echo "  Users: " . Config::getCacheTtl('users') . " seconds\n";
    echo "  Comments: " . Config::getCacheTtl('comments') . " seconds\n";
    
    echo "\nConfiguration Source:\n";
    echo "  You can modify these settings in 'setup-files/wlc_config.php'\n";
    echo "  or by setting environment variables (WPLITE_CACHE_*).\n";
}

// Handle command line arguments
$command = $argv[1] ?? 'help';

try {
    switch ($command) {
        case 'stats':
            showStats($cache);
            break;
            
        case 'clear':
            clearCache($cache);
            break;
            
        case 'cleanup':
            cleanupCache($cache);
            break;
            
        case 'info':
            showInfo();
            break;
            
        case 'help':
        default:
            printUsage();
            break;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
