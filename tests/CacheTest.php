<?php

namespace Tests;

use WPLite\Core\Cache;
use WPLite\Core\Config;
use WPLite\Api\ApiResponse;
use WPLite\Api\WordPressApiClient;
use WPLite\Exceptions\ConfigException;

/**
 * Cache System Tests
 */
class CacheTest extends BaseTestCase
{
    private Cache $cache;
    private string $testCacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary cache directory for testing
        $this->testCacheDir = sys_get_temp_dir() . '/wplite_cache_test_' . uniqid();
        
        // Create the directory manually first
        if (!is_dir($this->testCacheDir)) {
            mkdir($this->testCacheDir, 0755, true);
        }
        
        // Initialize cache with test directory (60 seconds TTL for faster testing)
        $this->cache = new Cache($this->testCacheDir, 60, true);
    }

    protected function tearDown(): void
    {
        // Clean up test cache directory recursively
        $this->removeDirectory($this->testCacheDir);
        
        parent::tearDown();
    }

    /**
     * Recursively remove directory and all its contents
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    public function testCacheDirectoryCreation(): void
    {
        $this->assertTrue(is_dir($this->testCacheDir));
        $this->assertTrue(is_writable($this->testCacheDir));
        
        // Check security files
        $this->assertTrue(file_exists($this->testCacheDir . '/.htaccess'));
        $this->assertTrue(file_exists($this->testCacheDir . '/index.php'));
    }

    public function testBasicCacheOperations(): void
    {
        $key = 'test_key';
        $data = ['message' => 'Hello, World!', 'timestamp' => time()];
        
        // Test set
        $this->assertTrue($this->cache->set($key, $data, 60));
        
        // Test get
        $retrieved = $this->cache->get($key);
        $this->assertEquals($data, $retrieved);
        
        // Test has
        $this->assertTrue($this->cache->has($key));
        
        // Test delete
        $this->assertTrue($this->cache->delete($key));
        $this->assertNull($this->cache->get($key));
        $this->assertFalse($this->cache->has($key));
    }

    public function testCacheExpiration(): void
    {
        $key = 'expiring_key';
        $data = 'expiring data';
        
        // Set cache with 1 second TTL
        $this->assertTrue($this->cache->set($key, $data, 1));
        
        // Should exist immediately
        $this->assertTrue($this->cache->has($key));
        
        // Wait for expiration
        sleep(2);
        
        // Should not exist after expiration
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    public function testCacheDelete(): void
    {
        $key = 'deletable_key';
        $data = 'deletable data';
        
        $this->cache->set($key, $data);
        $this->assertTrue($this->cache->has($key));
        
        $this->assertTrue($this->cache->delete($key));
        $this->assertFalse($this->cache->has($key));
    }

    public function testCacheClear(): void
    {
        // Set multiple cache entries
        $this->cache->set('key1', 'data1');
        $this->cache->set('key2', 'data2');
        $this->cache->set('key3', 'data3');
        
        // Verify they exist
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));
        
        // Clear all
        $this->assertTrue($this->cache->clear());
        
        // Verify they're gone
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testCacheKeyGeneration(): void
    {
        $key1 = Cache::generateApiCacheKey('posts', ['page' => 1, 'per_page' => 10]);
        $key2 = Cache::generateApiCacheKey('posts', ['per_page' => 10, 'page' => 1]); // Same params, different order
        $key3 = Cache::generateApiCacheKey('posts', ['page' => 2, 'per_page' => 10]); // Different params
        
        // Same parameters should generate same key regardless of order
        $this->assertEquals($key1, $key2);
        
        // Different parameters should generate different keys
        $this->assertNotEquals($key1, $key3);
        
        // Keys should be consistent format
        $this->assertStringStartsWith('api_', $key1);
        $this->assertEquals(68, strlen($key1)); // api_ + 64 char hash
    }

    public function testCacheStats(): void
    {
        // Initially empty
        $stats = $this->cache->getStats();
        $this->assertEquals(0, $stats['total_files']);
        $this->assertEquals(0, $stats['valid_files']);
        $this->assertEquals(0, $stats['expired_files']);
        
        // Add some cache entries
        $this->cache->set('key1', 'data1');
        $this->cache->set('key2', 'data2');
        
        $stats = $this->cache->getStats();
        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(2, $stats['valid_files']);
        $this->assertEquals(0, $stats['expired_files']);
        $this->assertGreaterThan(0, $stats['total_size']);
    }

    public function testCacheCleanup(): void
    {
        // Add entries with different TTLs
        $this->cache->set('valid_key', 'valid data', 60); // Valid for 60 seconds
        $this->cache->set('expired_key', 'expired data', 1); // Expires in 1 second
        
        // Wait for one to expire
        sleep(2);
        
        // Before cleanup
        $stats = $this->cache->getStats();
        $this->assertEquals(2, $stats['total_files']);
        
        // Run cleanup
        $cleaned = $this->cache->cleanup();
        $this->assertEquals(1, $cleaned);
        
        // After cleanup
        $stats = $this->cache->getStats();
        $this->assertEquals(1, $stats['total_files']);
        $this->assertEquals(1, $stats['valid_files']);
        $this->assertEquals(0, $stats['expired_files']);
    }

    public function testCacheDisabled(): void
    {
        $disabledCache = new Cache($this->testCacheDir, 60, false);
        
        $this->assertFalse($disabledCache->isEnabled());
        $this->assertFalse($disabledCache->set('key', 'data'));
        $this->assertNull($disabledCache->get('key'));
        $this->assertFalse($disabledCache->has('key'));
    }

    public function testConfigIntegration(): void
    {
        // Set test configuration
        Config::set('cache_enabled', true);
        Config::set('cache_ttl', 1800);
        Config::set('cache_ttl_posts', 900);
        Config::set('cache_ttl_pages', 3600);
        
        $this->assertTrue(Config::isCacheEnabled());
        $this->assertEquals(1800, Config::getCacheTtl());
        $this->assertEquals(900, Config::getCacheTtl('posts'));
        $this->assertEquals(3600, Config::getCacheTtl('pages'));
        $this->assertEquals(1800, Config::getCacheTtl('unknown')); // fallback to default
    }

    public function testApiClientCacheIntegration(): void
    {
        // This test requires a mock API or skip if no test URL available
        if (empty($_ENV['TEST_API_URL'])) {
            $this->markTestSkipped('No test API URL configured');
        }

        // Enable caching for this test
        Config::set('cache_enabled', true);
        Config::set('cache_ttl', 60);
        
        $client = $this->apiClient;
        
        $this->assertTrue($client->isCacheEnabled());
        
        // Make the same request twice - second should be from cache
        $endpoint = 'posts';
        $params = ['per_page' => 1, 'page' => 1];
        
        try {
            $response1 = $client->getData($endpoint, $params);
            $response2 = $client->getData($endpoint, $params);
            
            // Both should be successful
            $this->assertTrue($response1->isSuccess());
            $this->assertTrue($response2->isSuccess());
            
            // Should have same data (can't easily test timing here)
            $this->assertEquals($response1->getItems(), $response2->getItems());
            
            // Test cache clearing
            $this->assertTrue($client->clearCache($endpoint, $params));
            
        } catch (\Exception $e) {
            // Skip if API is not available
            $this->markTestSkipped('API not available for cache integration test: ' . $e->getMessage());
        }
    }

    public function testComplexDataCaching(): void
    {
        $complexData = [
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'Test Post',
                    'content' => 'This is test content',
                    'meta' => [
                        'featured_image' => 'https://example.com/image.jpg',
                        'categories' => [1, 2, 3]
                    ]
                ]
            ],
            'pagination' => [
                'total' => 100,
                'pages' => 10,
                'current' => 1
            ]
        ];
        
        $key = 'complex_data';
        
        $this->assertTrue($this->cache->set($key, $complexData));
        $retrieved = $this->cache->get($key);
        
        $this->assertEquals($complexData, $retrieved);
        $this->assertIsArray($retrieved['posts']);
        $this->assertIsArray($retrieved['pagination']);
        $this->assertEquals(1, $retrieved['posts'][0]['id']);
    }
}
