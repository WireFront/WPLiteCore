<?php

namespace WPLite;

use WPLite\Api\WordPressApiClient;
use WPLite\Api\PostsClient;
use WPLite\Api\PagesClient;
use WPLite\Api\MediaClient;
use WPLite\Core\ErrorHandler;
use WPLite\Exceptions\ConfigException;

/**
 * Main WPLiteCore factory class
 * This class provides easy access to all OOP functionality while maintaining backward compatibility
 */
class WPLiteCore
{
    private static ?self $instance = null;
    private WordPressApiClient $apiClient;
    private PostsClient $postsClient;
    private PagesClient $pagesClient;
    private MediaClient $mediaClient;
    private ErrorHandler $errorHandler;

    private function __construct(
        string $apiUrl,
        ?string $secretKey = null,
        bool $debugMode = false
    ) {
        // Initialize error handler
        $this->errorHandler = new ErrorHandler($debugMode);
        $this->errorHandler->register();

        // Initialize API client
        $this->apiClient = new WordPressApiClient($apiUrl, $secretKey, $debugMode);

        // Initialize specialized clients
        $this->postsClient = new PostsClient($this->apiClient);
        $this->pagesClient = new PagesClient($this->apiClient);
        $this->mediaClient = new MediaClient($this->apiClient);
    }

    /**
     * Create or get singleton instance
     *
     * @param string|null $apiUrl WordPress API URL
     * @param string|null $secretKey JWT secret key
     * @param bool $debugMode Enable debug mode
     * @return self
     * @throws ConfigException
     */
    public static function getInstance(
        ?string $apiUrl = null,
        ?string $secretKey = null,
        bool $debugMode = false
    ): self {
        if (self::$instance === null) {
            // Try to get config from constants if not provided
            $apiUrl = $apiUrl ?? (defined('api_url') ? api_url : null);
            $secretKey = $secretKey ?? (defined('HASH_KEY') ? HASH_KEY : null);
            
            if (empty($apiUrl)) {
                throw new ConfigException('API URL is required. Please provide it or define api_url constant.');
            }

            self::$instance = new self($apiUrl, $secretKey, $debugMode);
        }

        return self::$instance;
    }

    /**
     * Create a new instance (for multiple API configurations)
     *
     * @param string $apiUrl WordPress API URL
     * @param string|null $secretKey JWT secret key
     * @param bool $debugMode Enable debug mode
     * @return self
     */
    public static function create(
        string $apiUrl,
        ?string $secretKey = null,
        bool $debugMode = false
    ): self {
        return new self($apiUrl, $secretKey, $debugMode);
    }

    /**
     * Get the WordPress API client
     */
    public function api(): WordPressApiClient
    {
        return $this->apiClient;
    }

    /**
     * Get the Posts client
     */
    public function posts(): PostsClient
    {
        return $this->postsClient;
    }

    /**
     * Get the Pages client
     */
    public function pages(): PagesClient
    {
        return $this->pagesClient;
    }

    /**
     * Get the Media client
     */
    public function media(): MediaClient
    {
        return $this->mediaClient;
    }

    /**
     * Get the Error handler
     */
    public function errorHandler(): ErrorHandler
    {
        return $this->errorHandler;
    }

    /**
     * Quick access method: Get posts
     */
    public function getPosts(array $parameters = [])
    {
        return $this->postsClient->getPosts($parameters);
    }

    /**
     * Quick access method: Get post by slug
     */
    public function getPost(string $slug, string $mediaSize = 'medium')
    {
        return $this->postsClient->getPostBySlug($slug, $mediaSize);
    }

    /**
     * Quick access method: Get page by slug
     */
    public function getPage(string $slug, string $mediaSize = 'medium')
    {
        return $this->pagesClient->getPageBySlug($slug, $mediaSize);
    }

    /**
     * Quick access method: Get media
     */
    public function getMedia(int $mediaId)
    {
        return $this->mediaClient->getMedia($mediaId);
    }

    /**
     * Quick access method: Get featured image URL
     */
    public function getFeaturedImage(int $mediaId, string $size = 'medium'): ?string
    {
        return $this->mediaClient->getFeaturedImageUrl($mediaId, $size);
    }

    /**
     * Reset singleton instance (useful for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
