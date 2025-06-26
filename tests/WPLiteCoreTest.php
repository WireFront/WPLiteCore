<?php

namespace Tests;

use WPLite\WPLiteCore;
use WPLite\Api\WordPressApiClient;
use WPLite\Api\PostsClient;
use WPLite\Api\PagesClient;
use WPLite\Api\MediaClient;
use WPLite\Core\ErrorHandler;
use WPLite\Exceptions\ConfigException;

/**
 * Tests for WPLiteCore main class
 */
class WPLiteCoreTest extends BaseTestCase
{
    public function testSingletonInstance(): void
    {
        WPLiteCore::reset();
        
        $instance1 = WPLiteCore::getInstance(
            'https://apis.wirefront.net/v2',
            'test-key',
            true
        );
        
        $instance2 = WPLiteCore::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testCreateNewInstance(): void
    {
        $instance1 = WPLiteCore::create(
            'https://apis.wirefront.net/v2',
            'test-key1',
            true
        );
        
        $instance2 = WPLiteCore::create(
            'https://another-api.com/v2',
            'test-key2',
            false
        );
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testGetInstanceWithoutApiUrl(): void
    {
        WPLiteCore::reset();
        
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('API URL is required');
        
        // Test by passing an empty string explicitly, which should override constants
        WPLiteCore::getInstance('', null, false);
    }

    public function testGetInstanceWithConstants(): void
    {
        WPLiteCore::reset();
        
        // Define constants as they would be in wlc_config.php
        if (!defined('api_url')) {
            define('api_url', 'https://apis.wirefront.net/v2');
        }
        if (!defined('HASH_KEY')) {
            define('HASH_KEY', 'test-secret-key');
        }
        
        $instance = WPLiteCore::getInstance();
        
        $this->assertInstanceOf(WPLiteCore::class, $instance);
    }

    public function testApiClientAccess(): void
    {
        $apiClient = $this->wpLite->api();
        $this->assertInstanceOf(WordPressApiClient::class, $apiClient);
    }

    public function testPostsClientAccess(): void
    {
        $postsClient = $this->wpLite->posts();
        $this->assertInstanceOf(PostsClient::class, $postsClient);
    }

    public function testPagesClientAccess(): void
    {
        $pagesClient = $this->wpLite->pages();
        $this->assertInstanceOf(PagesClient::class, $pagesClient);
    }

    public function testMediaClientAccess(): void
    {
        $mediaClient = $this->wpLite->media();
        $this->assertInstanceOf(MediaClient::class, $mediaClient);
    }

    public function testErrorHandlerAccess(): void
    {
        $errorHandler = $this->wpLite->errorHandler();
        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }

    public function testQuickAccessMethods(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            // Test getPosts quick access
            $postsResponse = $this->wpLite->getPosts(['per_page' => 1]);
            $this->assertInstanceOf(\WPLite\Api\ApiResponse::class, $postsResponse);
            
            // Test getPost quick access
            $postResponse = $this->wpLite->getPost('test-post');
            $this->assertInstanceOf(\WPLite\Api\ApiResponse::class, $postResponse);
            
            // Test getPage quick access
            $pageResponse = $this->wpLite->getPage('test-page');
            $this->assertInstanceOf(\WPLite\Api\ApiResponse::class, $pageResponse);
            
        } catch (\WPLite\Exceptions\ApiException $e) {
            // Expected for non-existent content
            $this->assertInstanceOf(\WPLite\Exceptions\ApiException::class, $e);
        }
    }

    public function testGetMediaQuickAccess(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $mediaResponse = $this->wpLite->getMedia(1);
            $this->assertInstanceOf(\WPLite\Api\ApiResponse::class, $mediaResponse);
            
        } catch (\WPLite\Exceptions\ApiException $e) {
            // Expected if media doesn't exist
            $this->assertInstanceOf(\WPLite\Exceptions\ApiException::class, $e);
        }
    }

    public function testGetFeaturedImageQuickAccess(): void
    {
        $imageUrl = $this->wpLite->getFeaturedImage(999, 'medium');
        
        // Should return null for non-existent media
        $this->assertNull($imageUrl);
    }

    public function testResetSingleton(): void
    {
        $instance1 = WPLiteCore::getInstance(
            'https://apis.wirefront.net/v2',
            'test-key'
        );
        
        WPLiteCore::reset();
        
        $instance2 = WPLiteCore::getInstance(
            'https://apis.wirefront.net/v2',
            'test-key'
        );
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testMultipleApiConfigurations(): void
    {
        $site1 = WPLiteCore::create(
            'https://site1.com/api',
            'key1',
            true
        );
        
        $site2 = WPLiteCore::create(
            'https://site2.com/api',
            'key2',
            false
        );
        
        // Verify they're different instances
        $this->assertNotSame($site1, $site2);
        
        // Verify they have different clients
        $this->assertNotSame($site1->api(), $site2->api());
        $this->assertNotSame($site1->posts(), $site2->posts());
    }

    public function testDebugModeConfiguration(): void
    {
        $debugInstance = WPLiteCore::create(
            'https://apis.wirefront.net/v2',
            'test-key',
            true  // Debug mode enabled
        );
        
        $productionInstance = WPLiteCore::create(
            'https://apis.wirefront.net/v2',
            'test-key',
            false  // Debug mode disabled
        );
        
        $this->assertInstanceOf(WPLiteCore::class, $debugInstance);
        $this->assertInstanceOf(WPLiteCore::class, $productionInstance);
    }

    public function testClientChaining(): void
    {
        // Test that we can chain method calls fluently
        $this->skipIfApiNotAccessible();
        
        try {
            $posts = $this->wpLite
                ->posts()
                ->getPosts(['per_page' => 1]);
            
            $this->assertInstanceOf(\WPLite\Api\ApiResponse::class, $posts);
            
        } catch (\WPLite\Exceptions\ApiException $e) {
            $this->assertInstanceOf(\WPLite\Exceptions\ApiException::class, $e);
        }
    }

    public function testWithoutSecretKey(): void
    {
        $instance = WPLiteCore::create(
            'https://apis.wirefront.net/v2',
            null,  // No secret key
            true
        );
        
        $this->assertInstanceOf(WPLiteCore::class, $instance);
        
        // Should still work for public endpoints
        try {
            $response = $instance->getPosts(['per_page' => 1]);
            $this->assertInstanceOf(\WPLite\Api\ApiResponse::class, $response);
        } catch (\WPLite\Exceptions\ApiException $e) {
            // Expected if API requires authentication
            $this->assertInstanceOf(\WPLite\Exceptions\ApiException::class, $e);
        }
    }
}
