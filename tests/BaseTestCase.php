<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use WPLite\WPLiteCore;
use WPLite\Api\WordPressApiClient;
use WPLite\Core\ErrorHandler;

/**
 * Base test class with common functionality
 */
abstract class BaseTestCase extends TestCase
{
    protected string $apiUrl;
    protected string $secretKey;
    protected WPLiteCore $wpLite;
    protected WordPressApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset singleton before each test
        WPLiteCore::reset();
        
        // Get test configuration
        $this->apiUrl = $_ENV['TEST_API_URL'] ?? 'https://apis.wirefront.net/v2';
        $this->secretKey = $_ENV['TEST_SECRET_KEY'] ?? 'test-secret-key';
        
        // Initialize WPLiteCore for testing
        $this->wpLite = WPLiteCore::create($this->apiUrl, $this->secretKey, true);
        $this->apiClient = $this->wpLite->api();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        WPLiteCore::reset();
        parent::tearDown();
    }

    /**
     * Helper method to create mock API response
     */
    protected function createMockApiResponse(array $data, int $httpCode = 200): array
    {
        $headers = "HTTP/1.1 {$httpCode} OK\r\n";
        $headers .= "Content-Type: application/json\r\n";
        $headers .= "X-WP-Total: " . (is_array($data) ? count($data) : 1) . "\r\n";
        $headers .= "X-WP-TotalPages: 1\r\n";
        $headers .= "\r\n";

        return [
            'headers' => $headers,
            'body' => json_encode($data),
            'http_code' => $httpCode
        ];
    }

    /**
     * Helper method to create sample post data
     */
    protected function createSamplePost(int $id = 1): array
    {
        return [
            'id' => $id,
            'title' => ['rendered' => "Sample Post {$id}"],
            'content' => ['rendered' => "<p>This is sample content for post {$id}</p>"],
            'excerpt' => ['rendered' => "<p>Sample excerpt for post {$id}</p>"],
            'date' => '2025-06-26T10:00:00',
            'author' => 1,
            'featured_media' => 123,
            'status' => 'publish',
            'categories' => [1, 2],
            'tags' => [1, 3],
            'slug' => "sample-post-{$id}"
        ];
    }

    /**
     * Helper method to create sample page data
     */
    protected function createSamplePage(int $id = 1): array
    {
        return [
            'id' => $id,
            'title' => ['rendered' => "Sample Page {$id}"],
            'content' => ['rendered' => "<p>This is sample content for page {$id}</p>"],
            'excerpt' => ['rendered' => "<p>Sample excerpt for page {$id}</p>"],
            'date' => '2025-06-26T10:00:00',
            'author' => 1,
            'featured_media' => 123,
            'status' => 'publish',
            'parent' => 0,
            'slug' => "sample-page-{$id}"
        ];
    }

    /**
     * Helper method to create sample media data
     */
    protected function createSampleMedia(int $id = 123): array
    {
        return [
            'id' => $id,
            'title' => ['rendered' => "Sample Image {$id}"],
            'alt_text' => "Alt text for image {$id}",
            'caption' => ['rendered' => "Caption for image {$id}"],
            'description' => ['rendered' => "Description for image {$id}"],
            'mime_type' => 'image/jpeg',
            'source_url' => "https://example.com/image-{$id}.jpg",
            'media_details' => [
                'width' => 1920,
                'height' => 1080,
                'filesize' => 256000,
                'sizes' => [
                    'thumbnail' => [
                        'source_url' => "https://example.com/image-{$id}-150x150.jpg",
                        'width' => 150,
                        'height' => 150
                    ],
                    'medium' => [
                        'source_url' => "https://example.com/image-{$id}-300x169.jpg",
                        'width' => 300,
                        'height' => 169
                    ],
                    'large' => [
                        'source_url' => "https://example.com/image-{$id}-1024x576.jpg",
                        'width' => 1024,
                        'height' => 576
                    ],
                    'full' => [
                        'source_url' => "https://example.com/image-{$id}.jpg",
                        'width' => 1920,
                        'height' => 1080
                    ]
                ]
            ]
        ];
    }

    /**
     * Skip test if API is not accessible
     */
    protected function skipIfApiNotAccessible(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_NOBODY => true
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($result === false || $httpCode >= 500) {
            $this->markTestSkipped('API is not accessible: ' . $this->apiUrl);
        }
    }
}
