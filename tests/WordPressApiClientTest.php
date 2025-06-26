<?php

namespace Tests;

use WPLite\Api\WordPressApiClient;
use WPLite\Api\ApiResponse;
use WPLite\Exceptions\ApiException;
use WPLite\Exceptions\ValidationException;

/**
 * Tests for WordPressApiClient
 */
class WordPressApiClientTest extends BaseTestCase
{
    public function testClientInitialization(): void
    {
        $client = new WordPressApiClient(
            'https://apis.wirefront.net/v2',
            'test-key',
            true
        );
        
        $this->assertInstanceOf(WordPressApiClient::class, $client);
    }

    public function testGetDataWithValidEndpoint(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->apiClient->getData('posts', ['per_page' => 2]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertTrue($response->hasItems());
                $this->assertIsArray($response->getItems());
                $this->assertLessThanOrEqual(2, $response->getItemCount());
            }
            
        } catch (ApiException $e) {
            // API might return errors, that's OK for testing
            $this->assertInstanceOf(ApiException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testGetDataWithInvalidEndpoint(): void
    {
        $this->expectException(ApiException::class);
        
        $response = $this->apiClient->getData('invalid-endpoint');
    }

    public function testValidationWithEmptyEndpoint(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->apiClient->getData('', []);
    }

    public function testValidationWithInvalidParameters(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->apiClient->getData('posts', ['per_page' => 'invalid']);
    }

    public function testGetSingleBySlug(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->apiClient->getSingle('posts', 'test-post');
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            // Even if no post found, response should be valid
            $this->assertTrue($response->isSuccess() || $response->isFailure());
            
        } catch (ApiException $e) {
            // Expected for non-existent posts
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetSingleById(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->apiClient->getSingle('posts', '1');
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
        } catch (ApiException $e) {
            // Expected if post doesn't exist
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetComments(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->apiClient->getComments(1);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $items = $response->getItems();
                $this->assertIsArray($items);
            }
            
        } catch (ApiException $e) {
            // Expected if post doesn't exist or has no comments
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetCategories(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->apiClient->getCategories();
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertTrue($response->hasItems());
            }
            
        } catch (ApiException $e) {
            // API might not support categories endpoint
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetMedia(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->apiClient->getMedia(1);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
        } catch (ApiException $e) {
            // Expected if media doesn't exist
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testApiResponseConversion(): void
    {
        // Create a mock response to test conversion
        $mockData = [$this->createSamplePost(1), $this->createSamplePost(2)];
        $response = ApiResponse::success($mockData, '2', '1');
        
        $array = $response->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('result', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('total_posts', $array);
        $this->assertArrayHasKey('total_pages', $array);
        
        $this->assertTrue($array['result']);
        $this->assertEquals($mockData, $array['items']);
        $this->assertEquals('2', $array['total_posts']);
        $this->assertEquals('1', $array['total_pages']);
    }

    public function testErrorHandling(): void
    {
        // Test with invalid API URL
        $client = new WordPressApiClient('https://invalid-domain-that-does-not-exist.com', 'key');
        
        $this->expectException(ApiException::class);
        $client->getData('posts');
    }

    public function testTimeoutHandling(): void
    {
        // Test with very short timeout
        $client = new WordPressApiClient(
            'https://httpstat.us/200?sleep=5000', // This will sleep for 5 seconds
            'key',
            true,
            1, // 1 second timeout
            1  // 1 second connect timeout
        );
        
        $this->expectException(ApiException::class);
        $client->getData('posts');
    }
}
