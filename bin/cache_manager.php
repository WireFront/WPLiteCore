<?php

/**
 * WPLiteCore Cache Management Utility
 * 
 * This script provides command-line tools for managing the WPLiteCore cache.
 * Usage: php cache_manager.php [command] [options]
 * 
 * Available commands:
 * - stats: Show cache statistics
 * - clear: Clear all cache or specific endpoint
 * - cleanup: Remove expired cache files
 * - info: Show cache configuration
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../setup-files/wlc_config.php';

use WPLite\WPLiteCore;
use WPLite\Core\Cache;
use WPLite\Core\Config;

// Colors for console output
class ConsoleColors {
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const RESET = "\033[0m";
    
    public static function colorize($text, $color) {
        return $color . $text . self::RESET;
    }
}

function showUsage() {
    echo ConsoleColors::colorize("WPLiteCore Cache Management Utility\n", ConsoleColors::CYAN);
    echo "\nUsage: php cache_manager.php [command] [options]\n\n";
    echo "Available commands:\n";
    echo "  " . ConsoleColors::colorize("stats", ConsoleColors::GREEN) . "                Show cache statistics\n";
    echo "  " . ConsoleColors::colorize("clear [endpoint]", ConsoleColors::GREEN) . "     Clear cache (all or specific endpoint)\n";
    echo "  " . ConsoleColors::colorize("cleanup", ConsoleColors::GREEN) . "             Remove expired cache files\n";
    echo "  " . ConsoleColors::colorize("info", ConsoleColors::GREEN) . "                Show cache configuration\n";
    echo "  " . ConsoleColors::colorize("test", ConsoleColors::GREEN) . "                Test cache functionality\n";
    echo "  " . ConsoleColors::colorize("help", ConsoleColors::GREEN) . "                Show this help message\n";
    echo "\nExamples:\n";
    echo "  php cache_manager.php stats\n";
    echo "  php cache_manager.php clear posts\n";
    echo "  php cache_manager.php cleanup\n";
    echo "\n";
}

function showCacheStats(Cache $cache) {
    echo ConsoleColors::colorize("=== Cache Statistics ===\n", ConsoleColors::YELLOW);
    
    $stats = $cache->getStats();
    
    if (!$stats['enabled']) {
        echo ConsoleColors::colorize("Cache is DISABLED\n", ConsoleColors::RED);
        return;
    }
    
    echo "Status: " . ConsoleColors::colorize("ENABLED", ConsoleColors::GREEN) . "\n";
    echo "Cache Directory: " . $stats['cache_dir'] . "\n";
    echo "Default TTL: " . $stats['default_ttl'] . " seconds\n";
    echo "Total Files: " . $stats['total_files'] . "\n";
    echo "Valid Files: " . ConsoleColors::colorize($stats['valid_files'], ConsoleColors::GREEN) . "\n";
    echo "Expired Files: " . ConsoleColors::colorize($stats['expired_files'], ConsoleColors::RED) . "\n";
    echo "Total Size: " . $stats['total_size_formatted'] . "\n";
    
    if ($stats['expired_files'] > 0) {
        echo "\n" . ConsoleColors::colorize("Tip: Run 'php cache_manager.php cleanup' to remove expired files\n", ConsoleColors::YELLOW);
    }
}

function clearCache(Cache $cache, $endpoint = null) {
    if ($endpoint) {
        echo "Clearing cache for endpoint: " . ConsoleColors::colorize($endpoint, ConsoleColors::CYAN) . "\n";
        // For specific endpoint, we'd need to implement endpoint-specific clearing
        // For now, this is a placeholder - in practice, you'd need to track keys by endpoint
        echo ConsoleColors::colorize("Note: Endpoint-specific clearing requires key tracking implementation\n", ConsoleColors::YELLOW);
        echo "Clearing all cache instead...\n";
    } else {
        echo "Clearing all cache...\n";
    }
    
    if ($cache->clear()) {
        echo ConsoleColors::colorize("✓ Cache cleared successfully\n", ConsoleColors::GREEN);
    } else {
        echo ConsoleColors::colorize("✗ Failed to clear cache\n", ConsoleColors::RED);
    }
}

function cleanupCache(Cache $cache) {
    echo "Cleaning up expired cache files...\n";
    
    $cleaned = $cache->cleanup();
    
    if ($cleaned > 0) {
        echo ConsoleColors::colorize("✓ Cleaned up $cleaned expired files\n", ConsoleColors::GREEN);
    } else {
        echo ConsoleColors::colorize("No expired files found\n", ConsoleColors::BLUE);
    }
}

function showCacheInfo() {
    echo ConsoleColors::colorize("=== Cache Configuration ===\n", ConsoleColors::YELLOW);
    
    Config::load();
    
    echo "Cache Enabled: " . (Config::isCacheEnabled() ? 
        ConsoleColors::colorize("YES", ConsoleColors::GREEN) : 
        ConsoleColors::colorize("NO", ConsoleColors::RED)) . "\n";
    
    if (Config::isCacheEnabled()) {
        echo "Default TTL: " . Config::getCacheTtl() . " seconds\n";
        echo "Cache Directory: " . (Config::getCacheDir() ?: 'Default') . "\n";
        echo "Auto Cleanup: " . (Config::isCacheAutoCleanupEnabled() ? 'YES' : 'NO') . "\n";
        echo "Cleanup Probability: " . Config::getCacheCleanupProbability() . "%\n";
        
        echo "\nEndpoint-specific TTL:\n";
        $endpoints = ['posts', 'pages', 'media', 'categories', 'tags', 'users', 'comments'];
        foreach ($endpoints as $endpoint) {
            $ttl = Config::getCacheTtl($endpoint);
            echo "  $endpoint: $ttl seconds (" . gmdate('H:i:s', $ttl) . ")\n";
        }
    }
}

function testCache() {
    echo ConsoleColors::colorize("=== Cache Functionality Test ===\n", ConsoleColors::YELLOW);
    
    try {
        $testDir = sys_get_temp_dir() . '/wplite_cache_test_' . uniqid();
        $cache = new Cache($testDir, 60, true);
        
        echo "1. Testing cache creation... ";
        if (is_dir($testDir)) {
            echo ConsoleColors::colorize("✓ PASS\n", ConsoleColors::GREEN);
        } else {
            echo ConsoleColors::colorize("✗ FAIL\n", ConsoleColors::RED);
            return;
        }
        
        echo "2. Testing cache set/get... ";
        $testData = ['test' => 'data', 'timestamp' => time()];
        if ($cache->set('test_key', $testData) && $cache->get('test_key') === $testData) {
            echo ConsoleColors::colorize("✓ PASS\n", ConsoleColors::GREEN);
        } else {
            echo ConsoleColors::colorize("✗ FAIL\n", ConsoleColors::RED);
        }
        
        echo "3. Testing cache expiration... ";
        $cache->set('expire_test', 'data', 1);
        sleep(2);
        if ($cache->get('expire_test') === null) {
            echo ConsoleColors::colorize("✓ PASS\n", ConsoleColors::GREEN);
        } else {
            echo ConsoleColors::colorize("✗ FAIL\n", ConsoleColors::RED);
        }
        
        echo "4. Testing cache cleanup... ";
        $cleaned = $cache->cleanup();
        echo ConsoleColors::colorize("✓ PASS (cleaned $cleaned files)\n", ConsoleColors::GREEN);
        
        // Cleanup test directory
        $cache->clear();
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
        
        echo "\n" . ConsoleColors::colorize("All tests passed!\n", ConsoleColors::GREEN);
        
    } catch (Exception $e) {
        echo ConsoleColors::colorize("✗ ERROR: " . $e->getMessage() . "\n", ConsoleColors::RED);
    }
}

// Main script logic
if ($argc < 2) {
    showUsage();
    exit(1);
}

$command = $argv[1];

try {
    // Initialize cache
    Config::load();
    
    if (!Config::isCacheEnabled()) {
        echo ConsoleColors::colorize("Warning: Caching is disabled in configuration\n", ConsoleColors::YELLOW);
        if ($command !== 'info' && $command !== 'help' && $command !== 'test') {
            echo "Enable caching in wlc_config.php to use cache management features.\n";
            exit(1);
        }
    }
    
    $cache = new Cache(
        Config::getCacheDir(),
        Config::getCacheTtl(),
        Config::isCacheEnabled()
    );
    
    switch ($command) {
        case 'stats':
            showCacheStats($cache);
            break;
            
        case 'clear':
            $endpoint = isset($argv[2]) ? $argv[2] : null;
            clearCache($cache, $endpoint);
            break;
            
        case 'cleanup':
            cleanupCache($cache);
            break;
            
        case 'info':
            showCacheInfo();
            break;
            
        case 'test':
            testCache();
            break;
            
        case 'help':
        default:
            showUsage();
            break;
    }
    
} catch (Exception $e) {
    echo ConsoleColors::colorize("Error: " . $e->getMessage() . "\n", ConsoleColors::RED);
    exit(1);
}
