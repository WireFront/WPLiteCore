<?php

define('api_url', ''); // Required
define('HASH_KEY', ''); // Optional
define('site_url', 'https://example.com'); // Required

// ==========================================
// CACHING CONFIGURATION
// ==========================================

// Enable/disable API response caching
define('WLC_CACHE_ENABLED', true);

// Cache TTL (Time To Live) in seconds
// Default: 3600 (1 hour)
define('WLC_CACHE_TTL', 3600);

// Cache directory path (optional)
// If not set, will use default: [WPLiteCore]/cache
// define('WLC_CACHE_DIR', '/path/to/your/cache/directory');

// Specific TTL for different endpoint types (in seconds)
define('WLC_CACHE_TTL_POSTS', 1800);        // 30 minutes for posts
define('WLC_CACHE_TTL_PAGES', 7200);        // 2 hours for pages  
define('WLC_CACHE_TTL_MEDIA', 86400);       // 24 hours for media
define('WLC_CACHE_TTL_CATEGORIES', 3600);   // 1 hour for categories
define('WLC_CACHE_TTL_TAGS', 3600);         // 1 hour for tags
define('WLC_CACHE_TTL_USERS', 7200);        // 2 hours for users
define('WLC_CACHE_TTL_COMMENTS', 900);      // 15 minutes for comments

// Cache cleanup settings
define('WLC_CACHE_AUTO_CLEANUP', true);     // Auto cleanup expired files
define('WLC_CACHE_CLEANUP_PROBABILITY', 10); // 10% chance of cleanup on each request