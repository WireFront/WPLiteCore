<?php

namespace WPLite\Api;

use WPLite\Core\Validator;
use WPLite\Core\ErrorHandler;
use WPLite\Exceptions\ApiException;
use WPLite\Exceptions\ValidationException;
use WPLite\Exceptions\ConfigException;

/**
 * WordPress REST API Client
 * OOP version of wlc_get_api_data function
 */
class WordPressApiClient
{
    private string $apiUrl;
    private ?string $secretKey;
    private ErrorHandler $errorHandler;
    private int $timeout;
    private int $connectTimeout;

    public function __construct(
        string $apiUrl,
        ?string $secretKey = null,
        bool $debugMode = false,
        int $timeout = 30,
        int $connectTimeout = 10
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->secretKey = $secretKey;
        $this->errorHandler = new ErrorHandler($debugMode);
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Get data from WordPress API
     *
     * @param string $endpoint API endpoint (posts, pages, users, etc.)
     * @param array $parameters Query parameters
     * @param string|null $target Specific target (ID or slug)
     * @return ApiResponse
     * @throws ValidationException
     * @throws ApiException
     */
    public function getData(
        string $endpoint,
        array $parameters = [],
        ?string $target = null
    ): ApiResponse {
        try {
            // Validate inputs
            $this->validateInputs($endpoint, $parameters);

            // Build request URL
            $url = $this->buildUrl($endpoint, $parameters, $target);

            // Generate JWT token if secret key is provided
            $token = $this->generateToken();

            // Make API request
            $response = $this->makeRequest($url, $token);

            // Parse and return response
            return $this->parseResponse($response, $parameters);

        } catch (ValidationException | ApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ApiException(
                'Unexpected error occurred: ' . $e->getMessage(),
                0,
                500,
                [],
                ['original_error' => $e->getMessage()],
                $e
            );
        }
    }

    /**
     * Get a single post or page by slug or ID
     */
    public function getSingle(string $endpoint, string $identifier, string $mediaSize = 'medium'): ApiResponse
    {
        // Check if identifier is numeric (ID) or string (slug)
        $parameters = is_numeric($identifier) ? [] : ['slug' => $identifier];
        $target = is_numeric($identifier) ? $identifier : null;

        $response = $this->getData($endpoint, $parameters, $target);

        // If we got an array of items, get the first one
        if ($response->isSuccess() && $response->hasItems()) {
            $items = $response->getItems();
            
            // Handle both single item (object) and array of items
            if (is_array($items)) {
                // If it's an indexed array, get first item
                if (isset($items[0])) {
                    $item = $items[0];
                } else {
                    // If it's an associative array (single item), use it directly
                    $item = $items;
                }
            } else {
                // Single item object
                $item = $items;
            }
            
            // Enhance with additional data
            $enhancedItem = $this->enhanceSingleItem($item, $mediaSize);
            
            return new ApiResponse(true, $enhancedItem, $response->getTotalPosts(), $response->getTotalPages());
        }

        return $response;
    }

    /**
     * Get comments for a specific post
     */
    public function getComments(int $postId): ApiResponse
    {
        return $this->getData('comments', ['post' => $postId]);
    }

    /**
     * Get categories
     */
    public function getCategories(array $parameters = []): ApiResponse
    {
        return $this->getData('categories', $parameters);
    }

    /**
     * Get media/attachment by ID
     */
    public function getMedia(int $attachmentId): ApiResponse
    {
        return $this->getData('media', [], (string)$attachmentId);
    }

    /**
     * Validate inputs
     */
    private function validateInputs(string $endpoint, array $parameters): void
    {
        $validator = new Validator();
        
        $validator
            ->required($endpoint, 'endpoint')
            ->length($endpoint, 'endpoint', 1, 100)
            ->array($parameters, 'parameters');

        if (isset($parameters['per_page'])) {
            $validator->integer($parameters['per_page'], 'per_page');
        }
        
        if (isset($parameters['page'])) {
            $validator->integer($parameters['page'], 'page');
        }

        $validator->validate();
    }

    /**
     * Build request URL
     */
    private function buildUrl(string $endpoint, array $parameters, ?string $target): string
    {
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');
        
        if ($target !== null) {
            $url .= '/' . $target;
        }

        // Handle parameters
        if (!empty($parameters)) {
            // If slug is provided, use only slug parameter
            if (isset($parameters['slug'])) {
                $parameters = ['slug' => $parameters['slug']];
            } else {
                // Set defaults for pagination
                $parameters = array_merge(['per_page' => 10, 'page' => 1], $parameters);
            }

            $queryString = http_build_query($parameters);
            $url .= (strpos($url, '?') !== false ? '&' : '?') . $queryString;
        }

        return $url;
    }

    /**
     * Generate JWT token
     */
    private function generateToken(): ?string
    {
        if ($this->secretKey === null) {
            return null;
        }

        // Use the existing generate_jwt function
        $tokenResult = generate_jwt(['key' => $this->secretKey]);
        
        if (!$tokenResult['result']) {
            throw new ApiException('Failed to generate JWT token: ' . $tokenResult['message']);
        }

        return $tokenResult['token'];
    }

    /**
     * Make HTTP request
     */
    private function makeRequest(string $url, ?string $token): array
    {
        $ch = curl_init();
        
        $headers = ['User-Agent: WPLiteCore/1.0'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new ApiException('cURL request failed: ' . $error, 0, 500, [], ['url' => $url]);
        }

        if ($httpCode >= 400) {
            $body = substr($response, $headerSize);
            throw new ApiException(
                "API request failed with HTTP {$httpCode}",
                0,
                $httpCode,
                [],
                ['url' => $url, 'response_body' => $body]
            );
        }

        return [
            'headers' => substr($response, 0, $headerSize),
            'body' => substr($response, $headerSize),
            'http_code' => $httpCode
        ];
    }

    /**
     * Parse API response
     */
    private function parseResponse(array $response, array $parameters): ApiResponse
    {
        // Parse headers for WordPress-specific data
        $totalPosts = null;
        $totalPages = null;
        
        foreach (explode("\r\n", $response['headers']) as $header) {
            if (stripos($header, 'X-WP-Total:') === 0) {
                $totalPosts = trim(substr($header, strlen('X-WP-Total:')));
            }
            if (stripos($header, 'X-WP-TotalPages:') === 0) {
                $totalPages = trim(substr($header, strlen('X-WP-TotalPages:')));
            }
        }

        // Decode JSON response
        $items = json_decode($response['body'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
        }

        // Handle single item responses (slug or ID lookup)
        if (isset($parameters['slug']) && is_array($items) && count($items) === 1) {
            $items = $items[0];
        }

        return new ApiResponse(
            !empty($items),
            $items,
            $totalPosts,
            $totalPages
        );
    }

    /**
     * Enhance single item with additional data
     */
    private function enhanceSingleItem($item, string $mediaSize): array
    {
        // Handle non-array items
        if (!is_array($item)) {
            return [];
        }
        
        $enhancedItem = [
            'id' => $item['id'] ?? null,
            'title' => isset($item['title']['rendered']) ? $item['title']['rendered'] : ($item['title'] ?? null),
            'content' => isset($item['content']['rendered']) ? $item['content']['rendered'] : ($item['content'] ?? null),
            'excerpt' => isset($item['excerpt']['rendered']) ? $item['excerpt']['rendered'] : ($item['excerpt'] ?? null),
            'date' => $item['date'] ?? null,
            'author' => $item['author'] ?? null,
            'featured_media' => $item['featured_media'] ?? null,
            'status' => $item['status'] ?? null,
            'categories' => [],
            'tags' => $item['tags'] ?? [],
            'comments' => [],
            'featured_image' => null
        ];

        // Get categories if available
        if (isset($item['categories']) && is_array($item['categories'])) {
            foreach ($item['categories'] as $catId) {
                try {
                    $categoryResponse = $this->getData('categories', [], (string)$catId);
                    if ($categoryResponse->isSuccess()) {
                        $enhancedItem['categories'][] = $categoryResponse->getItems();
                    }
                } catch (\Exception $e) {
                    // Continue if category fetch fails
                }
            }
        }

        // Get comments
        if (isset($item['id'])) {
            try {
                $commentsResponse = $this->getComments($item['id']);
                if ($commentsResponse->isSuccess()) {
                    $enhancedItem['comments'] = $this->formatComments($commentsResponse->getItems());
                }
            } catch (\Exception $e) {
                // Continue if comments fetch fails
            }
        }

        // Get featured image
        if (!empty($item['featured_media'])) {
            try {
                $mediaResponse = $this->getMedia($item['featured_media']);
                if ($mediaResponse->isSuccess()) {
                    $mediaData = $mediaResponse->getItems();
                    $enhancedItem['featured_image'] = $this->extractImageUrl($mediaData, $mediaSize);
                }
            } catch (\Exception $e) {
                // Continue if media fetch fails
            }
        }

        return $enhancedItem;
    }

    /**
     * Format comments data
     */
    private function formatComments(array $comments): array
    {
        if (!is_array($comments)) {
            return [];
        }

        return array_map(function ($comment) {
            return [
                'id' => $comment['id'] ?? null,
                'author_name' => $comment['author_name'] ?? null,
                'author_avatar' => $comment['author_avatar_urls']['96'] ?? null,
                'content' => $comment['content']['rendered'] ?? null,
                'date' => $comment['date'] ?? null,
                'post' => $comment['post'] ?? null,
                'status' => $comment['status'] ?? null
            ];
        }, $comments);
    }

    /**
     * Extract image URL from media data
     */
    private function extractImageUrl(array $mediaData, string $size): ?string
    {
        if (empty($mediaData['media_details']['sizes'])) {
            return null;
        }

        $sizes = $mediaData['media_details']['sizes'];
        
        // Try requested size first, then fallback to medium, then full
        $fallbackSizes = [$size, 'medium', 'full'];
        
        foreach ($fallbackSizes as $fallbackSize) {
            if (isset($sizes[$fallbackSize]['source_url'])) {
                return $sizes[$fallbackSize]['source_url'];
            }
        }

        return null;
    }
}
