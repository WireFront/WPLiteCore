<?php

namespace WPLite\Api;

/**
 * WordPress Media API Client
 * OOP version of wlc_featured_image and media-related functions
 */
class MediaClient
{
    private WordPressApiClient $apiClient;

    public function __construct(WordPressApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get media/attachment by ID
     *
     * @param int $mediaId Media ID
     * @return ApiResponse
     */
    public function getMedia(int $mediaId): ApiResponse
    {
        return $this->apiClient->getMedia($mediaId);
    }

    /**
     * Get featured image URL for a specific size
     *
     * @param int $mediaId Media ID
     * @param string $size Image size (thumbnail, medium, large, full)
     * @return string|null
     */
    public function getFeaturedImageUrl(int $mediaId, string $size = 'medium'): ?string
    {
        try {
            $response = $this->getMedia($mediaId);
            
            if ($response->isFailure()) {
                return null;
            }
            
            $mediaData = $response->getItems();
            
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
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all available sizes for a media item
     *
     * @param int $mediaId Media ID
     * @return array
     */
    public function getAvailableSizes(int $mediaId): array
    {
        try {
            $response = $this->getMedia($mediaId);
            
            if ($response->isFailure()) {
                return [];
            }
            
            $mediaData = $response->getItems();
            
            if (empty($mediaData['media_details']['sizes'])) {
                return [];
            }
            
            return array_keys($mediaData['media_details']['sizes']);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get media metadata
     *
     * @param int $mediaId Media ID
     * @return array
     */
    public function getMediaMetadata(int $mediaId): array
    {
        try {
            $response = $this->getMedia($mediaId);
            
            if ($response->isFailure()) {
                return [];
            }
            
            $mediaData = $response->getItems();
            
            return [
                'id' => $mediaData['id'] ?? null,
                'title' => $mediaData['title']['rendered'] ?? null,
                'alt_text' => $mediaData['alt_text'] ?? null,
                'caption' => $mediaData['caption']['rendered'] ?? null,
                'description' => $mediaData['description']['rendered'] ?? null,
                'mime_type' => $mediaData['mime_type'] ?? null,
                'file_size' => $mediaData['media_details']['filesize'] ?? null,
                'width' => $mediaData['media_details']['width'] ?? null,
                'height' => $mediaData['media_details']['height'] ?? null,
                'source_url' => $mediaData['source_url'] ?? null,
                'date' => $mediaData['date'] ?? null,
                'author' => $mediaData['author'] ?? null
            ];
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get multiple media items
     *
     * @param array $parameters Query parameters
     * @return ApiResponse
     */
    public function getMediaLibrary(array $parameters = []): ApiResponse
    {
        return $this->apiClient->getData('media', $parameters);
    }

    /**
     * Get media by MIME type
     *
     * @param string $mimeType MIME type (image/jpeg, image/png, etc.)
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getMediaByType(string $mimeType, array $parameters = []): ApiResponse
    {
        $parameters['media_type'] = $mimeType;
        return $this->getMediaLibrary($parameters);
    }

    /**
     * Get images only
     *
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getImages(array $parameters = []): ApiResponse
    {
        $parameters['media_type'] = 'image';
        return $this->getMediaLibrary($parameters);
    }
}
