<?php

namespace WPLite\Core;

use WPLite\Exceptions\ConfigException;

/**
 * Simple file-based caching system for API responses
 * Supports TTL (Time To Live) and cache invalidation
 */
class Cache
{
    private string $cacheDir;
    private int $defaultTtl;
    private bool $enabled;
    private string $hashAlgo;

    public function __construct(
        string $cacheDir = null,
        int $defaultTtl = 3600,
        bool $enabled = true,
        string $hashAlgo = 'sha256'
    ) {
        $this->cacheDir = $cacheDir ?? $this->getDefaultCacheDir();
        $this->defaultTtl = $defaultTtl;
        $this->enabled = $enabled;
        $this->hashAlgo = $hashAlgo;

        $this->ensureCacheDirectoryExists();
    }

    /**
     * Get cached data if it exists and is still valid
     *
     * @param string $key Cache key
     * @return mixed|null Returns cached data or null if not found/expired
     */
    public function get(string $key)
    {
        if (!$this->enabled) {
            return null;
        }

        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }

        $data = $this->readCacheFile($filename);
        
        if ($data === null) {
            return null;
        }

        // Check if cache has expired
        if ($data['expires_at'] <= time()) {
            $this->delete($key);
            return null;
        }

        return $data['content'];
    }

    /**
     * Store data in cache with TTL
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $ttl Time to live in seconds (null uses default)
     * @return bool Success status
     */
    public function set(string $key, $data, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $filename = $this->getCacheFilename($key);
        
        $cacheData = [
            'key' => $key,
            'content' => $data,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'ttl' => $ttl
        ];

        return $this->writeCacheFile($filename, $cacheData);
    }

    /**
     * Check if cache key exists and is valid
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    /**
     * Clear all cached data
     *
     * @return bool Success status
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $files = glob($this->cacheDir . '/*.cache');
        $success = true;

        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Clean up expired cache files
     *
     * @return int Number of files cleaned up
     */
    public function cleanup(): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;
        $currentTime = time();

        foreach ($files as $file) {
            $data = $this->readCacheFile($file);
            
            if ($data !== null && $data['expires_at'] <= $currentTime) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        if (!$this->enabled) {
            return [
                'enabled' => false,
                'total_files' => 0,
                'valid_files' => 0,
                'expired_files' => 0,
                'total_size' => 0
            ];
        }

        $files = glob($this->cacheDir . '/*.cache');
        $totalFiles = count($files);
        $validFiles = 0;
        $expiredFiles = 0;
        $totalSize = 0;
        $currentTime = time();

        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = $this->readCacheFile($file);
            
            if ($data !== null) {
                if ($data['expires_at'] > $currentTime) {
                    $validFiles++;
                } else {
                    $expiredFiles++;
                }
            }
        }

        return [
            'enabled' => $this->enabled,
            'cache_dir' => $this->cacheDir,
            'default_ttl' => $this->defaultTtl,
            'total_files' => $totalFiles,
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize)
        ];
    }

    /**
     * Generate cache key for API request
     *
     * @param string $endpoint API endpoint
     * @param array $parameters Request parameters
     * @param string|null $target Target identifier
     * @return string Cache key
     */
    public static function generateApiCacheKey(string $endpoint, array $parameters = [], ?string $target = null): string
    {
        // Sort parameters for consistent key generation
        ksort($parameters);
        
        $keyParts = [
            'endpoint' => $endpoint,
            'target' => $target,
            'params' => $parameters
        ];

        return 'api_' . hash('sha256', serialize($keyParts));
    }

    /**
     * Get default cache directory
     *
     * @return string
     */
    private function getDefaultCacheDir(): string
    {
        $baseDir = dirname(__DIR__, 2); // Go up to WPLiteCore root
        return $baseDir . '/cache';
    }

    /**
     * Ensure cache directory exists and is writable
     *
     * @throws ConfigException
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new ConfigException("Failed to create cache directory: {$this->cacheDir}");
            }
        }

        if (!is_writable($this->cacheDir)) {
            throw new ConfigException("Cache directory is not writable: {$this->cacheDir}");
        }

        // Create .htaccess file to prevent direct access
        $htaccessFile = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\n");
        }

        // Create index.php to prevent directory listing
        $indexFile = $this->cacheDir . '/index.php';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, "<?php\n// Silence is golden.\n");
        }
    }

    /**
     * Get cache filename for a key
     *
     * @param string $key Cache key
     * @return string Filename
     */
    private function getCacheFilename(string $key): string
    {
        $hashedKey = hash($this->hashAlgo, $key);
        return $this->cacheDir . '/' . $hashedKey . '.cache';
    }

    /**
     * Read and unserialize cache file
     *
     * @param string $filename Cache filename
     * @return array|null Cache data or null on failure
     */
    private function readCacheFile(string $filename): ?array
    {
        try {
            $content = file_get_contents($filename);
            if ($content === false) {
                return null;
            }

            $data = unserialize($content);
            if ($data === false || !is_array($data)) {
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            // If file is corrupted, try to delete it
            if (file_exists($filename)) {
                @unlink($filename);
            }
            return null;
        }
    }

    /**
     * Serialize and write cache file
     *
     * @param string $filename Cache filename
     * @param array $data Cache data
     * @return bool Success status
     */
    private function writeCacheFile(string $filename, array $data): bool
    {
        try {
            $serialized = serialize($data);
            return file_put_contents($filename, $serialized, LOCK_EX) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Enable or disable caching
     *
     * @param bool $enabled Enable status
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if caching is enabled
     *
     * @return bool Enabled status
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set default TTL
     *
     * @param int $ttl TTL in seconds
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = max(0, $ttl);
    }

    /**
     * Get default TTL
     *
     * @return int TTL in seconds
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}
