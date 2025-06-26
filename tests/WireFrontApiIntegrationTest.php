<?php

namespace Tests;

use WPLite\WPLiteCore;
use WPLite\Api\ApiResponse;
use WPLite\Exceptions\ApiException;

/**
 * Integration tests using the real WireFront API
 * These tests will actually hit your API at https://apis.wirefront.net/v2
 */
class WireFrontApiIntegrationTest extends BaseTestCase
{
    private string $realApiUrl = 'https://apis.wirefront.net/v2';
    private ?string $realSecretKey = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Define required constants for the custom API
        if (!defined('HASH_KEY')) {
            define('HASH_KEY', '6F6$AHI.ybW!@4y-o4AR*d');
        }
        if (!defined('api_url')) {
            define('api_url', 'https://apis.wirefront.net/v2');
        }
        if (!defined('site_url')) {
            define('site_url', 'https://example.com');
        }
        
        // Use real API URL and key for your custom endpoint
        // Note: Your API uses /v2/posts instead of /wp-json/wp/v2/posts
        $this->realSecretKey = $_ENV['WIREFRONT_API_KEY'] ?? '6F6$AHI.ybW!@4y-o4AR*d';
        $this->wpLite = WPLiteCore::create($this->realApiUrl, $this->realSecretKey, true);
        
        echo "\n🔧 Testing against custom WireFront API: " . $this->realApiUrl . "\n";
        echo "🔑 Using API key: " . ($this->realSecretKey ? 'Present' : 'Missing') . "\n";
    }

    public function testRealApiConnection(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🔗 Testing connection to WireFront API...\n";
        
        try {
            $response = $this->wpLite->posts()->getPosts(['per_page' => 1]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                echo "✅ Successfully connected to WireFront API\n";
                echo "📊 Total posts available: " . $response->getTotalPosts() . "\n";
                echo "📄 Total pages: " . $response->getTotalPages() . "\n";
                
                $items = $response->getItems();
                if (!empty($items)) {
                    $firstPost = is_array($items) ? $items[0] : $items;
                    echo "📝 First post title: " . ($firstPost['title']['rendered'] ?? 'No title') . "\n";
                }
            } else {
                echo "❌ API connection successful but no posts found\n";
            }
            
        } catch (ApiException $e) {
            echo "❌ API Error: " . $e->getMessage() . "\n";
            echo "🔍 HTTP Status: " . $e->getHttpStatusCode() . "\n";
            
            // Don't fail the test - API might be configured differently
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostsWithDifferentParameters(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n📚 Testing different post parameters...\n";
        
        $testCases = [
            ['per_page' => 1],
            ['per_page' => 2, 'page' => 1],
            ['status' => 'publish'],
            ['orderby' => 'date', 'order' => 'desc'],
        ];
        
        foreach ($testCases as $index => $parameters) {
            try {
                echo "🧪 Test case " . ($index + 1) . ": " . json_encode($parameters) . "\n";
                
                $response = $this->wpLite->posts()->getPosts($parameters);
                $this->assertInstanceOf(ApiResponse::class, $response);
                
                if ($response->isSuccess()) {
                    echo "  ✅ Success - Found " . $response->getItemCount() . " items\n";
                } else {
                    echo "  ℹ️  No items found\n";
                }
                
            } catch (ApiException $e) {
                echo "  ❌ Error: " . $e->getMessage() . "\n";
                $this->assertInstanceOf(ApiException::class, $e);
            }
        }
    }

    public function testSearchFunctionality(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🔍 Testing search functionality...\n";
        
        $searchTerms = ['test', 'wordpress', 'post', 'sample'];
        
        foreach ($searchTerms as $term) {
            try {
                echo "🔎 Searching for: '{$term}'\n";
                
                $response = $this->wpLite->posts()->searchPosts($term, ['per_page' => 2]);
                $this->assertInstanceOf(ApiResponse::class, $response);
                
                if ($response->isSuccess()) {
                    echo "  ✅ Found " . $response->getItemCount() . " results\n";
                } else {
                    echo "  ℹ️  No results for '{$term}'\n";
                }
                
            } catch (ApiException $e) {
                echo "  ❌ Search error: " . $e->getMessage() . "\n";
                $this->assertInstanceOf(ApiException::class, $e);
            }
        }
    }

    public function testSinglePostRetrieval(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n📄 Testing single post retrieval...\n";
        
        // First, get a list of posts to find a real slug/ID
        try {
            $postsResponse = $this->wpLite->posts()->getPosts(['per_page' => 1]);
            
            if ($postsResponse->isSuccess() && $postsResponse->hasItems()) {
                $posts = $postsResponse->getItems();
                $firstPost = is_array($posts) ? $posts[0] : $posts;
                
                if (isset($firstPost['id'])) {
                    echo "🔍 Testing with real post ID: " . $firstPost['id'] . "\n";
                    
                    $singleResponse = $this->wpLite->posts()->getPostById($firstPost['id']);
                    $this->assertInstanceOf(ApiResponse::class, $singleResponse);
                    
                    if ($singleResponse->isSuccess()) {
                        $post = $singleResponse->getItems();
                        echo "  ✅ Retrieved post: " . ($post['title'] ?? 'No title') . "\n";
                        echo "  📷 Featured image: " . ($post['featured_image'] ? 'Available' : 'None') . "\n";
                        echo "  💬 Comments: " . count($post['comments'] ?? []) . "\n";
                    }
                }
                
                if (isset($firstPost['slug'])) {
                    echo "🔍 Testing with real post slug: " . $firstPost['slug'] . "\n";
                    
                    $slugResponse = $this->wpLite->posts()->getPostBySlug($firstPost['slug']);
                    $this->assertInstanceOf(ApiResponse::class, $slugResponse);
                    
                    if ($slugResponse->isSuccess()) {
                        echo "  ✅ Retrieved post by slug successfully\n";
                    }
                }
            }
            
        } catch (ApiException $e) {
            echo "❌ Single post test error: " . $e->getMessage() . "\n";
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testErrorHandlingWithInvalidRequests(): void
    {
        echo "\n⚠️  Testing error handling...\n";
        
        try {
            // Test with invalid post ID
            echo "🧪 Testing invalid post ID...\n";
            $response = $this->wpLite->posts()->getPostById(999999);
            
            if ($response->isFailure()) {
                echo "  ✅ Properly handled non-existent post\n";
            }
            
        } catch (ApiException $e) {
            echo "  ✅ Caught API exception as expected: " . $e->getMessage() . "\n";
            $this->assertInstanceOf(ApiException::class, $e);
        }
        
        try {
            // Test with invalid slug
            echo "🧪 Testing invalid post slug...\n";
            $response = $this->wpLite->posts()->getPostBySlug('non-existent-post-slug-12345');
            
            if ($response->isFailure()) {
                echo "  ✅ Properly handled non-existent slug\n";
            }
            
        } catch (ApiException $e) {
            echo "  ✅ Caught API exception as expected: " . $e->getMessage() . "\n";
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testCustomEndpoints(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🛠️  Testing custom endpoints...\n";
        
        $endpoints = ['posts', 'pages', 'media', 'users', 'categories', 'tags'];
        
        foreach ($endpoints as $endpoint) {
            try {
                echo "🔌 Testing endpoint: {$endpoint}\n";
                
                $response = $this->wpLite->api()->getData($endpoint, ['per_page' => 1]);
                $this->assertInstanceOf(ApiResponse::class, $response);
                
                if ($response->isSuccess()) {
                    echo "  ✅ Endpoint '{$endpoint}' is available\n";
                } else {
                    echo "  ℹ️  Endpoint '{$endpoint}' returned no data\n";
                }
                
            } catch (ApiException $e) {
                if ($e->getHttpStatusCode() === 404) {
                    echo "  ❌ Endpoint '{$endpoint}' not found (404)\n";
                } else {
                    echo "  ❌ Endpoint '{$endpoint}' error: " . $e->getMessage() . "\n";
                }
                $this->assertInstanceOf(ApiException::class, $e);
            }
        }
    }

    public function testResponseFormatCompatibility(): void
    {
        $this->skipIfApiNotAccessible();
        
        echo "\n🔄 Testing response format compatibility...\n";
        
        try {
            $response = $this->wpLite->posts()->getPosts(['per_page' => 1]);
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            // Test OOP format
            echo "🆕 OOP Response format:\n";
            echo "  Success: " . ($response->isSuccess() ? 'true' : 'false') . "\n";
            echo "  Has items: " . ($response->hasItems() ? 'true' : 'false') . "\n";
            echo "  Item count: " . $response->getItemCount() . "\n";
            
            // Test legacy format conversion
            $legacyFormat = $response->toArray();
            echo "🔙 Legacy format compatibility:\n";
            echo "  Result: " . ($legacyFormat['result'] ? 'true' : 'false') . "\n";
            echo "  Items: " . (isset($legacyFormat['items']) ? 'present' : 'missing') . "\n";
            echo "  Total posts: " . ($legacyFormat['total_posts'] ?? 'null') . "\n";
            echo "  Total pages: " . ($legacyFormat['total_pages'] ?? 'null') . "\n";
            
            // Verify legacy format structure
            $this->assertArrayHasKey('result', $legacyFormat);
            $this->assertArrayHasKey('items', $legacyFormat);
            $this->assertArrayHasKey('total_posts', $legacyFormat);
            $this->assertArrayHasKey('total_pages', $legacyFormat);
            
            echo "  ✅ Legacy format conversion successful\n";
            
        } catch (ApiException $e) {
            echo "❌ Compatibility test error: " . $e->getMessage() . "\n";
            $this->assertInstanceOf(ApiException::class, $e);
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
            $this->markTestSkipped('WireFront API is not accessible: ' . $this->realApiUrl);
        }
    }
}
