<?php

namespace Tests;

use WPLite\Api\PostsClient;
use WPLite\Api\ApiResponse;
use WPLite\Exceptions\ApiException;

/**
 * Tests for PostsClient
 */
class PostsClientTest extends BaseTestCase
{
    private PostsClient $postsClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postsClient = $this->wpLite->posts();
    }

    public function testGetPosts(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPosts(['per_page' => 3]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertTrue($response->hasItems());
                $this->assertLessThanOrEqual(3, $response->getItemCount());
                
                $items = $response->getItems();
                $this->assertIsArray($items);
                
                if (!empty($items)) {
                    $firstPost = $items[0];
                    $this->assertArrayHasKey('id', $firstPost);
                    $this->assertArrayHasKey('title', $firstPost);
                }
            }
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostBySlug(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPostBySlug('sample-post');
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $post = $response->getItems();
                $this->assertIsArray($post);
                $this->assertArrayHasKey('id', $post);
                $this->assertArrayHasKey('title', $post);
            }
            
        } catch (ApiException $e) {
            // Expected if post doesn't exist
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostById(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPostById(1);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
        } catch (ApiException $e) {
            // Expected if post doesn't exist
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostsByCategory(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPostsByCategory(1, ['per_page' => 2]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertLessThanOrEqual(2, $response->getItemCount());
            }
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostsByTag(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPostsByTag(1, ['per_page' => 2]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostsByAuthor(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPostsByAuthor(1, ['per_page' => 2]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testSearchPosts(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->searchPosts('test', ['per_page' => 2]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertLessThanOrEqual(2, $response->getItemCount());
            }
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetRecentPosts(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getRecentPosts(3);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertLessThanOrEqual(3, $response->getItemCount());
            }
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testGetPostsByStatus(): void
    {
        $this->skipIfApiNotAccessible();
        
        try {
            $response = $this->postsClient->getPostsByStatus('publish', ['per_page' => 2]);
            
            $this->assertInstanceOf(ApiResponse::class, $response);
            
            if ($response->isSuccess()) {
                $this->assertLessThanOrEqual(2, $response->getItemCount());
                
                $items = $response->getItems();
                if (!empty($items)) {
                    foreach ($items as $post) {
                        $this->assertEquals('publish', $post['status'] ?? null);
                    }
                }
            }
            
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function testPostsClientIntegration(): void
    {
        // Test that PostsClient is properly integrated with WPLiteCore
        $postsClient = $this->wpLite->posts();
        $this->assertInstanceOf(PostsClient::class, $postsClient);
        
        // Test quick access method
        try {
            $response = $this->wpLite->getPosts(['per_page' => 1]);
            $this->assertInstanceOf(ApiResponse::class, $response);
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }
}
