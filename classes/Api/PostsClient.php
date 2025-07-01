<?php

namespace WPLite\Api;

use WPLite\Exceptions\ApiException;
use WPLite\Exceptions\ValidationException;

/**
 * WordPress Posts API Client
 * OOP version of wlc_single_post and related functions
 */
class PostsClient
{
    private WordPressApiClient $apiClient;

    public function __construct(WordPressApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get multiple posts
     *
     * @param array $parameters Query parameters (per_page, page, status, etc.)
     * @return ApiResponse
     */
    public function getPosts(array $parameters = []): ApiResponse
    {
        return $this->apiClient->getData('posts', $parameters);
    }

    /**
     * Get a single post by slug
     *
     * @param string $slug Post slug
     * @param string $mediaSize Featured image size
     * @return ApiResponse
     */
    public function getPostBySlug(string $slug, string $mediaSize = 'medium'): ApiResponse
    {
        return $this->apiClient->getSingle('posts', $slug, $mediaSize);
    }

    /**
     * Get a single post by ID
     *
     * @param int $id Post ID
     * @param string $mediaSize Featured image size
     * @return ApiResponse
     */
    public function getPostById(int $id, string $mediaSize = 'medium'): ApiResponse
    {
        return $this->apiClient->getSingle('posts', (string)$id, $mediaSize);
    }

    /**
     * Get posts by category
     *
     * @param int $categoryId Category ID
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getPostsByCategory(int $categoryId, array $parameters = []): ApiResponse
    {
        $parameters['categories'] = $categoryId;
        return $this->getPosts($parameters);
    }

    /**
     * Get posts by tag
     *
     * @param int $tagId Tag ID
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getPostsByTag(int $tagId, array $parameters = []): ApiResponse
    {
        $parameters['tags'] = $tagId;
        return $this->getPosts($parameters);
    }

    /**
     * Get posts by author
     *
     * @param int $authorId Author ID
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getPostsByAuthor(int $authorId, array $parameters = []): ApiResponse
    {
        $parameters['author'] = $authorId;
        return $this->getPosts($parameters);
    }

    /**
     * Search posts
     *
     * @param string $searchTerm Search term
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function searchPosts(string $searchTerm, array $parameters = []): ApiResponse
    {
        $parameters['search'] = $searchTerm;
        return $this->getPosts($parameters);
    }

    /**
     * Get recent posts
     *
     * @param int $limit Number of posts to retrieve
     * @return ApiResponse
     */
    public function getRecentPosts(int $limit = 5): ApiResponse
    {
        return $this->getPosts([
            'per_page' => $limit,
            'orderby' => 'date',
            'order' => 'desc'
        ]);
    }

    /**
     * Get posts with specific status
     *
     * @param string $status Post status (publish, draft, private, etc.)
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getPostsByStatus(string $status, array $parameters = []): ApiResponse
    {
        $parameters['status'] = $status;
        return $this->getPosts($parameters);
    }
}
