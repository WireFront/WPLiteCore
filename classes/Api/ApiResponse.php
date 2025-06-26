<?php

namespace WPLite\Api;

/**
 * API Response wrapper class
 * Provides consistent response format for all API operations
 */
class ApiResponse
{
    private bool $success;
    private mixed $items;
    private ?string $totalPosts;
    private ?string $totalPages;
    private array $meta;

    public function __construct(
        bool $success,
        mixed $items = null,
        ?string $totalPosts = null,
        ?string $totalPages = null,
        array $meta = []
    ) {
        $this->success = $success;
        $this->items = $items;
        $this->totalPosts = $totalPosts;
        $this->totalPages = $totalPages;
        $this->meta = $meta;
    }

    /**
     * Check if the API call was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the API call failed
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the response items/data
     */
    public function getItems(): mixed
    {
        return $this->items;
    }

    /**
     * Get total number of posts (from X-WP-Total header)
     */
    public function getTotalPosts(): ?string
    {
        return $this->totalPosts;
    }

    /**
     * Get total number of pages (from X-WP-TotalPages header)
     */
    public function getTotalPages(): ?string
    {
        return $this->totalPages;
    }

    /**
     * Get additional metadata
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Add metadata
     */
    public function addMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Get response as array (compatible with legacy format)
     */
    public function toArray(): array
    {
        return [
            'result' => $this->success,
            'items' => $this->items,
            'total_posts' => $this->totalPosts,
            'total_pages' => $this->totalPages,
            'meta' => $this->meta
        ];
    }

    /**
     * Get response as JSON
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * Check if response has items
     */
    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    /**
     * Get number of items (if items is an array)
     */
    public function getItemCount(): int
    {
        if (is_array($this->items)) {
            // Check if this is a list of items (numeric indexed) vs a single item (associative)
            // A single WordPress post/page will have keys like 'id', 'title', etc.
            // An array of posts will be numerically indexed [0, 1, 2, ...]
            if (array_is_list($this->items)) {
                return count($this->items);
            } else {
                // Single associative array item (e.g., a single post)
                return empty($this->items) ? 0 : 1;
            }
        }
        return $this->hasItems() ? 1 : 0;
    }

    /**
     * Get first item (if items is an array)
     */
    public function getFirstItem(): mixed
    {
        if (is_array($this->items) && !empty($this->items)) {
            // If it's a list of items, return the first one
            if (array_is_list($this->items)) {
                return $this->items[0];
            } else {
                // If it's a single associative array, return it as is
                return $this->items;
            }
        }
        return $this->items;
    }

    /**
     * Check if this is a paginated response
     */
    public function isPaginated(): bool
    {
        return $this->totalPages !== null && $this->totalPosts !== null;
    }

    /**
     * Create a successful response
     */
    public static function success(
        mixed $items,
        ?string $totalPosts = null,
        ?string $totalPages = null,
        array $meta = []
    ): self {
        return new self(true, $items, $totalPosts, $totalPages, $meta);
    }

    /**
     * Create a failed response
     */
    public static function failure(string $message = 'Request failed', array $meta = []): self
    {
        return new self(false, null, null, null, array_merge($meta, ['error_message' => $message]));
    }
}
