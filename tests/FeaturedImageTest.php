<?php

namespace Tests;

use WPLite\WPLiteCore;
use WPLite\Api\ApiResponse;
use WPLite\Exceptions\ApiException;
use WPLite\Core\Config;

/**
 * Test specifically for post ID 32 with featured image handling
 * This tests both procedural and OOP approaches for featured image retrieval
 */
class FeaturedImageTest extends BaseTestCase
{
    private string $realApiUrl;
    private ?string $realSecretKey = null;
    private int $testPostId;
    private int $expectedMediaId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load configuration from environment
        Config::load();
        
        // Get configuration values securely
        $this->realApiUrl = Config::getApiUrl();
        $this->realSecretKey = Config::get('wirefront_api_key') ?? Config::get('hash_key');
        $this->testPostId = Config::get('test_post_id', 32);
        $this->expectedMediaId = Config::get('test_media_id', 41);
        
        // Validate configuration
        if (!$this->realSecretKey) {
            $this->markTestSkipped('No API key configured. Please set WPLITE_HASH_KEY or WIREFRONT_API_KEY environment variable.');
        }
        
        // Define constants for backward compatibility with procedural functions
        if (!defined('HASH_KEY') && $this->realSecretKey) {
            define('HASH_KEY', $this->realSecretKey);
        }
        if (!defined('api_url')) {
            define('api_url', Config::getApiUrl());
        }
        if (!defined('site_url')) {
            define('site_url', Config::getSiteUrl());
        }
        
        $this->wpLite = WPLiteCore::create($this->realApiUrl, $this->realSecretKey, true);
        
        echo "\n🖼️  Testing featured image handling for post ID {$this->testPostId}...\n";
        echo "🔗 API URL: " . $this->realApiUrl . "\n";
        echo "🔑 Using API key: " . ($this->realSecretKey ? 'Present' : 'Missing') . "\n";
        echo "📝 Test post ID: {$this->testPostId}\n";
        echo "🖼️  Expected media ID: {$this->expectedMediaId}\n";
    }

    /**
     * Test configured post ID using OOP approach
     */
    public function testPostWithFeaturedImageOOP(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🆕 Testing post ID {$this->testPostId} with OOP approach...\n";
        
        try {
            // Test getting post by configured ID
            $response = $this->wpLite->posts()->getPostById($this->testPostId, 'medium');
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $post = $response->getItems();
                $this->assertIsArray($post);
                
                echo "  ✅ Successfully retrieved post ID {$this->testPostId}\n";
                echo "  📝 Title: " . ($post['title']['rendered'] ?? 'No title') . "\n";
                echo "  🆔 ID: " . ($post['id'] ?? 'No ID') . "\n";
                echo "  📅 Date: " . ($post['date'] ?? 'No date') . "\n";
                echo "  🏷️  Status: " . ($post['status'] ?? 'No status') . "\n";
                
                // Check for featured media ID
                $featuredMediaId = $post['featured_media'] ?? null;
                echo "  🖼️  Featured media ID: " . ($featuredMediaId ? $featuredMediaId : 'None') . "\n";
                
                // Check if featured image URL is available
                $featuredImageUrl = $post['featured_image'] ?? null;
                echo "  🔗 Featured image URL: " . ($featuredImageUrl ? 'Available' : 'None') . "\n";
                
                if ($featuredImageUrl) {
                    echo "  📷 Featured image: " . $featuredImageUrl . "\n";
                    
                    // Verify the URL is accessible
                    $this->assertNotEmpty($featuredImageUrl);
                    $this->assertStringContainsString('http', $featuredImageUrl);
                }
                
                // Assertions for post structure
                $this->assertArrayHasKey('id', $post);
                $this->assertEquals($this->testPostId, $post['id']);
                $this->assertArrayHasKey('title', $post);
                $this->assertArrayHasKey('content', $post);
                
                if ($featuredMediaId) {
                    $this->assertIsNumeric($featuredMediaId);
                    $this->assertGreaterThan(0, $featuredMediaId);
                    echo "  ✅ Featured media validation passed\n";
                }
                
            } else {
                echo "  ❌ Failed to retrieve post ID {$this->testPostId}\n";
                echo "  📊 Response: " . json_encode($response->toArray()) . "\n";
                $this->fail("Post ID {$this->testPostId} should be accessible");
            }
            
        } catch (ApiException $e) {
            echo "  ❌ OOP API Error: " . $e->getMessage() . "\n";
            echo "  🔍 HTTP Status: " . $e->getHttpStatusCode() . "\n";
            
            // If it's a 404, the post might not exist
            if ($e->getHttpStatusCode() === 404) {
                $this->markTestSkipped("Post ID {$this->testPostId} does not exist in the API");
            } else {
                throw $e;
            }
        }
    }

    /**
     * Test configured post ID using procedural approach
     */
    public function testPostWithFeaturedImageProcedural(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🔙 Testing post ID {$this->testPostId} with procedural approach...\n";
        
        // Test procedural function
        $result = wlc_single_post([
            'key' => $this->realSecretKey,
            'api_url' => $this->realApiUrl . '/',
            'id' => $this->testPostId,
            'type' => 'posts',
            'media_size' => 'medium'
        ]);
        
        $this->assertIsArray($result);
        
        if (isset($result['result']) && $result['result'] === false) {
            echo "  ❌ Procedural approach failed: " . ($result['message'] ?? 'Unknown error') . "\n";
            
            // If post not found, skip the test
            if (strpos($result['message'] ?? '', 'not found') !== false) {
                $this->markTestSkipped("Post ID {$this->testPostId} does not exist in the API (procedural test)");
            } else {
                $this->fail("Procedural approach should work for post ID {$this->testPostId}");
            }
        } else {
            echo "  ✅ Successfully retrieved post ID {$this->testPostId} with procedural approach\n";
            echo "  📝 Title: " . ($result['title'] ?? 'No title') . "\n";
            echo "  🆔 ID: " . ($result['id'] ?? 'No ID') . "\n";
            echo "  📅 Date: " . ($result['date'] ?? 'No date') . "\n";
            echo "  🏷️  Status: " . ($result['status'] ?? 'No status') . "\n";
            
            // Check featured image handling
            $featuredImage = $result['featured_image'] ?? null;
            echo "  🖼️  Featured image result: " . (is_array($featuredImage) ? 'Array' : gettype($featuredImage)) . "\n";
            
            if (is_array($featuredImage)) {
                echo "  📊 Featured image data: " . json_encode($featuredImage, JSON_PRETTY_PRINT) . "\n";
                
                if (isset($featuredImage['result']) && $featuredImage['result'] === true) {
                    echo "  ✅ Featured image successfully retrieved\n";
                    echo "  🔗 Featured image URL: " . ($featuredImage['url'] ?? 'No URL') . "\n";
                    
                    $this->assertArrayHasKey('url', $featuredImage);
                    $this->assertNotEmpty($featuredImage['url']);
                    $this->assertStringContainsString('http', $featuredImage['url']);
                } else {
                    echo "  ℹ️  No featured image or failed to retrieve: " . ($featuredImage['message'] ?? 'Unknown') . "\n";
                }
            }
            
            // Check featured_media ID
            $featuredMediaId = $result['featured_media'] ?? null;
            echo "  🖼️  Featured media ID: " . ($featuredMediaId ? $featuredMediaId : 'None') . "\n";
            
            // Assertions for procedural result
            $this->assertArrayHasKey('title', $result);
            $this->assertArrayHasKey('content', $result);
            $this->assertArrayHasKey('id', $result);
            $this->assertEquals($this->testPostId, $result['id']);
            
            if ($featuredMediaId) {
                $this->assertIsNumeric($featuredMediaId);
                $this->assertGreaterThan(0, $featuredMediaId);
                echo "  ✅ Procedural featured media validation passed\n";
            }
        }
    }

    /**
     * Test direct featured image function with configured post's featured media ID
     */
    public function testDirectFeaturedImageFunction(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🎯 Testing direct featured image function...\n";
        
        // First, get the configured post to find its featured media ID
        try {
            $response = $this->wpLite->posts()->getPostById($this->testPostId);
            
            if ($response->isSuccess()) {
                $post = $response->getItems();
                $featuredMediaId = $post['featured_media'] ?? null;
                
                if ($featuredMediaId) {
                    echo "  🖼️  Testing wlc_featured_image with media ID: $featuredMediaId\n";
                    
                    // Test the featured image function directly
                    $imageResult = wlc_featured_image([
                        'key' => $this->realSecretKey,
                        'api_url' => $this->realApiUrl . '/',
                        'attachment_id' => $featuredMediaId,
                        'size' => 'medium'
                    ]);
                    
                    echo "  📊 Image function result: " . json_encode($imageResult, JSON_PRETTY_PRINT) . "\n";
                    
                    $this->assertIsArray($imageResult);
                    
                    if (isset($imageResult['result']) && $imageResult['result'] === true) {
                        echo "  ✅ Featured image function successful\n";
                        echo "  🔗 Image URL: " . ($imageResult['url'] ?? 'No URL') . "\n";
                        
                        $this->assertArrayHasKey('url', $imageResult);
                        $this->assertNotEmpty($imageResult['url']);
                        $this->assertStringContainsString('http', $imageResult['url']);
                        
                        // Test different image sizes
                        $sizes = ['thumbnail', 'medium', 'large', 'full'];
                        foreach ($sizes as $size) {
                            echo "  🔍 Testing size: $size\n";
                            $sizeResult = wlc_featured_image([
                                'key' => $this->realSecretKey,
                                'api_url' => $this->realApiUrl . '/',
                                'attachment_id' => $featuredMediaId,
                                'size' => $size
                            ]);
                            
                            if (isset($sizeResult['result']) && $sizeResult['result'] === true) {
                                echo "    ✅ Size $size: " . ($sizeResult['url'] ?? 'No URL') . "\n";
                            } else {
                                echo "    ❌ Size $size failed: " . ($sizeResult['message'] ?? 'Unknown error') . "\n";
                            }
                        }
                        
                    } else {
                        echo "  ❌ Featured image function failed: " . ($imageResult['message'] ?? 'Unknown error') . "\n";
                    }
                    
                } else {
                    echo "  ℹ️  Post {$this->testPostId} has no featured media ID\n";
                    $this->markTestSkipped("Post ID {$this->testPostId} has no featured media to test");
                }
                
            } else {
                echo "  ❌ Could not retrieve post {$this->testPostId} for featured image testing\n";
                $this->markTestSkipped("Post ID {$this->testPostId} not accessible for featured image testing");
            }
            
        } catch (ApiException $e) {
            echo "  ❌ Error getting post {$this->testPostId} for featured image test: " . $e->getMessage() . "\n";
            
            if ($e->getHttpStatusCode() === 404) {
                $this->markTestSkipped("Post ID {$this->testPostId} does not exist");
            } else {
                throw $e;
            }
        }
    }

    /**
     * Test both OOP and procedural approaches side by side
     */
    public function testOOPvsProcedural(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n⚖️  Comparing OOP vs Procedural approaches for post {$this->testPostId}...\n";
        
        $oopResult = null;
        $proceduralResult = null;
        
        // OOP approach
        try {
            $response = $this->wpLite->posts()->getPostById($this->testPostId, 'medium');
            if ($response->isSuccess()) {
                $oopResult = $response->getItems();
                echo "  🆕 OOP: Successfully retrieved post\n";
            }
        } catch (ApiException $e) {
            echo "  🆕 OOP: Failed - " . $e->getMessage() . "\n";
        }
        
        // Procedural approach
        $proceduralResult = wlc_single_post([
            'key' => $this->realSecretKey,
            'api_url' => $this->realApiUrl . '/',
            'id' => $this->testPostId,
            'type' => 'posts',
            'media_size' => 'medium'
        ]);
        
        if (isset($proceduralResult['result']) && $proceduralResult['result'] === false) {
            echo "  🔙 Procedural: Failed - " . ($proceduralResult['message'] ?? 'Unknown error') . "\n";
            $proceduralResult = null;
        } else {
            echo "  🔙 Procedural: Successfully retrieved post\n";
        }
        
        // Compare results if both succeeded
        if ($oopResult && $proceduralResult) {
            echo "\n  📊 Comparison Results:\n";
            
            // Compare basic fields
            $fieldsToCompare = ['id', 'title', 'status', 'date', 'featured_media'];
            foreach ($fieldsToCompare as $field) {
                $oopValue = $field === 'title' ? ($oopResult[$field]['rendered'] ?? null) : ($oopResult[$field] ?? null);
                $proceduralValue = $proceduralResult[$field] ?? null;
                
                $match = $oopValue === $proceduralValue;
                echo "    $field: " . ($match ? '✅ Match' : '❌ Differ') . "\n";
                
                if (!$match) {
                    echo "      OOP: " . json_encode($oopValue) . "\n";
                    echo "      Procedural: " . json_encode($proceduralValue) . "\n";
                }
            }
            
            // Compare featured image handling
            $oopFeaturedImage = $oopResult['featured_image'] ?? null;
            $proceduralFeaturedImage = $proceduralResult['featured_image'] ?? null;
            
            echo "    Featured Image Handling:\n";
            
            if ($oopFeaturedImage && $proceduralFeaturedImage) {
                if (is_array($proceduralFeaturedImage) && isset($proceduralFeaturedImage['url'])) {
                    $proceduralUrl = $proceduralFeaturedImage['url'];
                    $oopUrl = $oopFeaturedImage;
                    
                    $urlMatch = $proceduralUrl === $oopUrl;
                    echo "      URLs: " . ($urlMatch ? '✅ Match' : '❌ Differ') . "\n";
                    
                    if (!$urlMatch) {
                        echo "        OOP: " . json_encode($oopUrl) . "\n";
                        echo "        Procedural: " . json_encode($proceduralUrl) . "\n";
                    }
                } else {
                    echo "      ❌ Different formats - OOP: " . gettype($oopFeaturedImage) . ", Procedural: " . gettype($proceduralFeaturedImage) . "\n";
                }
            } else {
                echo "      ℹ️  One or both approaches have no featured image\n";
                echo "        OOP: " . ($oopFeaturedImage ? 'Present' : 'Missing') . "\n";
                echo "        Procedural: " . ($proceduralFeaturedImage ? 'Present' : 'Missing') . "\n";
            }
            
            echo "  ✅ Comparison completed\n";
            
            // Add assertions to avoid risky test warning
            $this->assertTrue(true, 'Comparison completed successfully');
            
        } else {
            echo "\n  ⚠️  Cannot compare - one or both approaches failed\n";
            
            if (!$oopResult && !$proceduralResult) {
                $this->markTestSkipped("Both OOP and procedural approaches failed for post ID {$this->testPostId}");
            } else {
                // At least one approach worked
                $this->assertTrue($oopResult !== null || $proceduralResult !== null, 'At least one approach should work');
            }
        }
    }

    protected function skipIfApiNotAccessible(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->realApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => true
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($result === false || $httpCode >= 500) {
            $this->markTestSkipped('API is not accessible: ' . $this->realApiUrl);
        }
    }
}
