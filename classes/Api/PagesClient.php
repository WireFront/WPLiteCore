<?php

namespace WPLite\Api;

/**
 * WordPress Pages API Client
 * OOP version for pages-related functions
 */
class PagesClient
{
    private WordPressApiClient $apiClient;

    public function __construct(WordPressApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get multiple pages
     *
     * @param array $parameters Query parameters
     * @return ApiResponse
     */
    public function getPages(array $parameters = []): ApiResponse
    {
        return $this->apiClient->getData('pages', $parameters);
    }

    /**
     * Get a single page by slug
     *
     * @param string $slug Page slug
     * @param string $mediaSize Featured image size
     * @return ApiResponse
     */
    public function getPageBySlug(string $slug, string $mediaSize = 'medium'): ApiResponse
    {
        return $this->apiClient->getSingle('pages', $slug, $mediaSize);
    }

    /**
     * Get a single page by ID
     *
     * @param int $id Page ID
     * @param string $mediaSize Featured image size
     * @return ApiResponse
     */
    public function getPageById(int $id, string $mediaSize = 'medium'): ApiResponse
    {
        return $this->apiClient->getSingle('pages', (string)$id, $mediaSize);
    }

    /**
     * Get child pages of a parent page
     *
     * @param int $parentId Parent page ID
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getChildPages(int $parentId, array $parameters = []): ApiResponse
    {
        $parameters['parent'] = $parentId;
        return $this->getPages($parameters);
    }

    /**
     * Get top-level pages (no parent)
     *
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getTopLevelPages(array $parameters = []): ApiResponse
    {
        $parameters['parent'] = 0;
        return $this->getPages($parameters);
    }

    /**
     * Search pages
     *
     * @param string $searchTerm Search term
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function searchPages(string $searchTerm, array $parameters = []): ApiResponse
    {
        $parameters['search'] = $searchTerm;
        return $this->getPages($parameters);
    }

    /**
     * Get pages by status
     *
     * @param string $status Page status
     * @param array $parameters Additional parameters
     * @return ApiResponse
     */
    public function getPagesByStatus(string $status, array $parameters = []): ApiResponse
    {
        $parameters['status'] = $status;
        return $this->getPages($parameters);
    }
}
