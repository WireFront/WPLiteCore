# WPLiteCore Complete Usage Guide

> **For Developers Using WPLiteCore in Their Projects**

This guide covers everything you need to know to use WPLiteCore effectively in your WordPress projects.

## 📦 Installation

```bash
composer require wirefront/wplitecore
```

## 🚀 Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use WPLite\WPLiteCore;

// Option 1: With JWT authentication (for protected content)
$wpLite = WPLiteCore::create(
    'https://your-wordpress-site.com/wp-json/wp/v2',
    'your-secret-key'
);

// Option 2: Without JWT authentication (for public content)  
$wpLite = WPLiteCore::create(
    'https://your-wordpress-site.com/wp-json/wp/v2',
    null  // No secret key - works for public APIs
);

// Get posts (works with or without JWT)
$posts = $wpLite->posts()->getPosts(['per_page' => 5]);
if ($posts->isSuccess()) {
    foreach ($posts->getItems() as $post) {
        echo $post['title']['rendered'] . "\n";
    }
}
```

**🔓 Important**: JWT tokens are completely optional! Only use them if your WordPress API requires authentication.

---

## 🔐 Authentication Guide

### When to Use JWT Tokens

| Content Type | JWT Required? | Config Hash Key Needed? | Example |
|--------------|---------------|------------------------|---------|
| **Public Posts** | ❌ No | ❌ No | Blog posts, published pages |
| **Public Media** | ❌ No | ❌ No | Images, attachments in public posts |
| **Draft Posts** | ✅ Yes | ✅ Yes | Unpublished content |
| **Private Posts** | ✅ Yes | ✅ Yes | Password-protected content |
| **User Data** | ✅ Yes | ✅ Yes | Author profiles, user-specific data |
| **Comments** | ❌ Usually No | ❌ No | Public comments (check your API settings) |

**📝 Important Notes:**
- **End Users**: Don't need Config class or `.env` files - just pass values directly to `WPLiteCore::create()`
- **Library Developers**: Use Config class and `.env` files for testing the library itself
- **Hash Keys in Config**: Only needed when testing library functionality, not for end user applications

### Authentication Examples

```php
// For public WordPress sites (most common)
$publicApi = WPLiteCore::create('https://example.com/wp-json/wp/v2', null);

// For protected/private content
$privateApi = WPLiteCore::create('https://example.com/wp-json/wp/v2', 'your-jwt-secret');

// Mixed usage - different instances for different needs
$posts = $publicApi->posts()->getPosts();        // Public posts
$drafts = $privateApi->posts()->getPosts([        // Private drafts
    'status' => 'draft'
]);
```

### 🔧 Config Class (For Library Developers Only)

The `Config` class is used internally for testing the WPLiteCore library itself. **End users don't need it!**

```php
use WPLite\Core\Config;

// ✅ For library developers testing
Config::load();                        // Load from .env file
$apiUrl = Config::getApiUrl();         // Get test API URL
$hashKey = Config::getHashKeySafe();   // Get hash key safely (returns null if not set)

// ❌ End users don't need this - just use:
$wpLite = WPLiteCore::create('your-api-url', 'your-key-or-null');
```

**Config Methods:**
- `Config::getHashKeySafe()` - Returns hash key or null (safe)
- `Config::getHashKey()` - Returns hash key or throws exception (for testing)
- `Config::isConfigured()` - Checks if hash key is available
- `Config::getApiUrl()` - Gets API URL (with fallback to default)

---

## 🔧 Core Classes

### WPLiteCore (Main Entry Point)

The main factory class that provides access to all functionality.

#### Creating Instances

```php
use WPLite\WPLiteCore;

// Method 1: Create new instance (recommended)
$wpLite = WPLiteCore::create(
    'https://your-site.com/wp-json/wp/v2',
    'your-secret-key',  // Optional - use null for public APIs
    false  // debug mode (optional)
);

// Method 2: Without authentication (for public APIs)
$wpLite = WPLiteCore::create(
    'https://your-site.com/wp-json/wp/v2',
    null  // No authentication required
);

// Method 3: Singleton instance (for single API configuration)
$wpLite = WPLiteCore::getInstance(
    'https://your-site.com/wp-json/wp/v2',
    'your-secret-key'  // Optional
);
```

#### Available Methods

```php
// Access specialized clients
$posts = $wpLite->posts();      // PostsClient
$pages = $wpLite->pages();      // PagesClient  
$media = $wpLite->media();      // MediaClient
$api = $wpLite->api();          // WordPressApiClient

// Quick access methods
$postsResponse = $wpLite->getPosts(['per_page' => 10]);
$postResponse = $wpLite->getPost('my-post-slug');
$pageResponse = $wpLite->getPage('about-us');
$mediaResponse = $wpLite->getMedia(123);
$imageUrl = $wpLite->getFeaturedImage(123, 'medium');
```

---

## 📝 PostsClient

Handle WordPress posts operations.

### Available Methods

#### Get Multiple Posts

```php
$posts = $wpLite->posts();

// Basic usage
$response = $posts->getPosts();

// With parameters
$response = $posts->getPosts([
    'per_page' => 10,
    'page' => 1,
    'status' => 'publish',
    'orderby' => 'date',
    'order' => 'desc'
]);

if ($response->isSuccess()) {
    foreach ($response->getItems() as $post) {
        echo "Title: " . $post['title']['rendered'] . "\n";
        echo "Excerpt: " . $post['excerpt']['rendered'] . "\n";
        echo "Date: " . $post['date'] . "\n\n";
    }
    
    echo "Total Posts: " . $response->getTotalPosts() . "\n";
    echo "Total Pages: " . $response->getTotalPages() . "\n";
}
```

#### Get Single Post by ID

```php
$response = $posts->getPostById(32, 'medium');

if ($response->isSuccess()) {
    $post = $response->getItems();
    echo "Title: " . $post['title']['rendered'] . "\n";
    echo "Content: " . $post['content']['rendered'] . "\n";
    
    // Featured image is automatically included
    if (isset($post['featured_image'])) {
        echo "Featured Image: " . $post['featured_image'] . "\n";
    }
}
```

#### Get Single Post by Slug

```php
$response = $posts->getPostBySlug('my-awesome-post', 'large');

if ($response->isSuccess()) {
    $post = $response->getItems();
    echo "Post ID: " . $post['id'] . "\n";
    echo "Title: " . $post['title']['rendered'] . "\n";
}
```

#### Get Posts by Category

```php
$response = $posts->getPostsByCategory(5, [
    'per_page' => 5,
    'orderby' => 'date'
]);

if ($response->isSuccess()) {
    echo "Posts in category 5:\n";
    foreach ($response->getItems() as $post) {
        echo "- " . $post['title']['rendered'] . "\n";
    }
}
```

#### Get Posts by Tag

```php
$response = $posts->getPostsByTag(10, ['per_page' => 3]);

if ($response->isSuccess()) {
    echo "Posts with tag 10:\n";
    foreach ($response->getItems() as $post) {
        echo "- " . $post['title']['rendered'] . "\n";
    }
}
```

#### Search Posts

```php
$response = $posts->searchPosts('wordpress tutorial');

if ($response->isSuccess()) {
    echo "Search results:\n";
    foreach ($response->getItems() as $post) {
        echo "- " . $post['title']['rendered'] . "\n";
    }
}
```

---

## 📄 PagesClient

Handle WordPress pages operations.

### Available Methods

```php
$pages = $wpLite->pages();

// Get all pages
$response = $pages->getPages(['per_page' => 20]);

// Get page by ID
$response = $pages->getPageById(15, 'medium');

// Get page by slug
$response = $pages->getPageBySlug('about-us', 'large');

// Search pages
$response = $pages->searchPages('contact');

if ($response->isSuccess()) {
    $page = $response->getItems();
    echo "Page: " . $page['title']['rendered'] . "\n";
    echo "Content: " . $page['content']['rendered'] . "\n";
}
```

---

## �️ MediaClient

Handle WordPress media/attachments.

### Available Methods

```php
$media = $wpLite->media();

// Get media by ID
$response = $media->getMedia(123);

if ($response->isSuccess()) {
    $mediaItem = $response->getItems();
    echo "Title: " . $mediaItem['title']['rendered'] . "\n";
    echo "URL: " . $mediaItem['source_url'] . "\n";
    echo "Alt Text: " . $mediaItem['alt_text'] . "\n";
}

// Get featured image URL directly
$imageUrl = $media->getFeaturedImageUrl(123, 'medium');
if ($imageUrl) {
    echo '<img src="' . $imageUrl . '" alt="Featured Image">';
}

// Available image sizes: 'thumbnail', 'medium', 'large', 'full'
$thumbnailUrl = $media->getFeaturedImageUrl(123, 'thumbnail');
$largeUrl = $media->getFeaturedImageUrl(123, 'large');
```

---

## 📡 ApiResponse Class

All API methods return an `ApiResponse` object with these methods:

### Response Methods

```php
$response = $wpLite->posts()->getPosts();

// Check success/failure
if ($response->isSuccess()) {
    // Success handling
}

if ($response->isFailure()) {
    // Error handling
}

// Get response data
$items = $response->getItems();

// Get pagination info
$totalPosts = $response->getTotalPosts();
$totalPages = $response->getTotalPages();

// Get additional metadata
$meta = $response->getMeta();

// Convert to legacy array format
$legacyArray = $response->toArray();
```

### Example: Complete Response Handling

```php
$response = $wpLite->posts()->getPosts(['per_page' => 5]);

if ($response->isSuccess()) {
    $posts = $response->getItems();
    
    echo "Found " . $response->getTotalPosts() . " posts\n";
    echo "Showing page 1 of " . $response->getTotalPages() . "\n\n";
    
    foreach ($posts as $post) {
        echo "Title: " . $post['title']['rendered'] . "\n";
        echo "Date: " . date('F j, Y', strtotime($post['date'])) . "\n";
        echo "Excerpt: " . strip_tags($post['excerpt']['rendered']) . "\n";
        echo "---\n";
    }
} else {
    echo "Failed to fetch posts\n";
}
```

---

## 🔙 Legacy Procedural Functions

For backward compatibility, all original functions are still available:

### wlc_single_post()

Get a single post with all related data.

```php
// With JWT authentication
$result = wlc_single_post([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'id' => 32,                    // Post ID
    'type' => 'posts',             // Post type (posts, pages)
    'media_size' => 'medium'       // Featured image size
]);

// Without JWT authentication (for public content)
$result = wlc_single_post([
    'key' => null,                 // No authentication
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'id' => 32,
    'type' => 'posts',
    'media_size' => 'medium'
]);

if ($result && isset($result['title'])) {
    echo "Title: " . $result['title'] . "\n";
    echo "Content: " . $result['content'] . "\n";
    
    if (isset($result['featured_image']['url'])) {
        echo "Featured Image: " . $result['featured_image']['url'] . "\n";
    }
}
```

### wlc_get_api_data()

Generic API data retrieval.

```php
$data = wlc_get_api_data([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2',
    'endpoint' => 'posts',
    'parameters' => [
        'per_page' => 5,
        'status' => 'publish'
    ]
]);

if ($data && isset($data['result']) && $data['result']) {
    foreach ($data['items'] as $post) {
        echo $post['title']['rendered'] . "\n";
    }
}
```

### wlc_featured_image()

Get featured image for a specific attachment ID.

```php
$image = wlc_featured_image([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'attachment_id' => 41,
    'size' => 'medium'  // thumbnail, medium, large, full
]);

if ($image && isset($image['url'])) {
    echo '<img src="' . $image['url'] . '" alt="' . $image['alt'] . '">';
}
```

### wlc_post_comments()

Get comments for a specific post.

```php
$comments = wlc_post_comments([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'post_id' => 32,
    'per_page' => 10
]);

if ($comments && isset($comments['items'])) {
    foreach ($comments['items'] as $comment) {
        echo "Author: " . $comment['author_name'] . "\n";
        echo "Comment: " . $comment['content']['rendered'] . "\n";
        echo "---\n";
    }
}
```

### wlc_get_category()

Get category information.

```php
$category = wlc_get_category([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'category_id' => 5
]);

if ($category && isset($category['name'])) {
    echo "Category: " . $category['name'] . "\n";
    echo "Description: " . $category['description'] . "\n";
}
```

---

## ⚡ Advanced Usage Examples

### Pagination Example

```php
$currentPage = 1;
$perPage = 10;

do {
    $response = $wpLite->posts()->getPosts([
        'per_page' => $perPage,
        'page' => $currentPage
    ]);
    
    if ($response->isSuccess()) {
        $posts = $response->getItems();
        $totalPages = (int)$response->getTotalPages();
        
        echo "Page $currentPage of $totalPages:\n";
        foreach ($posts as $post) {
            echo "- " . $post['title']['rendered'] . "\n";
        }
        
        $currentPage++;
    } else {
        break;
    }
    
} while ($currentPage <= $totalPages);
```

### Error Handling Example

```php
use WPLite\Exceptions\ApiException;
use WPLite\Exceptions\ValidationException;

try {
    $response = $wpLite->posts()->getPostById(99999);
    
    if ($response->isFailure()) {
        echo "Post not found or API error occurred\n";
    }
    
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
} catch (ValidationException $e) {
    echo "Validation Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
```

### Multiple API Instances

```php
// Different WordPress sites
$site1 = WPLiteCore::create(
    'https://site1.com/wp-json/wp/v2',
    'site1-secret-key'
);

$site2 = WPLiteCore::create(
    'https://site2.com/wp-json/wp/v2',
    'site2-secret-key'
);

// Get posts from both sites
$site1Posts = $site1->posts()->getPosts(['per_page' => 5]);
$site2Posts = $site2->posts()->getPosts(['per_page' => 5]);
```

### Custom Query Parameters

```php
// Advanced post filtering
$response = $wpLite->posts()->getPosts([
    'per_page' => 20,
    'page' => 1,
    'status' => 'publish',
    'orderby' => 'date',
    'order' => 'desc',
    'categories' => [1, 5, 10],        // Multiple categories
    'tags' => [20, 25],                // Multiple tags
    'author' => 2,                     // Specific author
    'after' => '2024-01-01T00:00:00',  // Posts after date
    'before' => '2024-12-31T23:59:59', // Posts before date
    'search' => 'tutorial',            // Search term
    '_embed' => true                   // Include embedded data
]);
```

---

## 🚨 Error Handling

WPLiteCore provides several exception types:

- `ApiException` - API communication errors
- `ValidationException` - Input validation errors  
- `ConfigException` - Configuration errors
- `WPLiteException` - Base exception class

Always check `isSuccess()` on responses and wrap API calls in try-catch blocks for production code.

---

## 🎯 Migration from Legacy Code

If you're upgrading from old procedural code:

### Before (Procedural)
```php
$result = wlc_single_post([
    'key' => 'secret',
    'api_url' => 'https://api.com/wp-json/wp/v2/',
    'id' => 32,
    'type' => 'posts'
]);
```

### After (OOP)
```php
$wpLite = WPLiteCore::create('https://api.com/wp-json/wp/v2', 'secret');
$response = $wpLite->posts()->getPostById(32);
$result = $response->getItems();
```

Both approaches work and return similar data structures for easy migration!

---

## 📚 Additional Resources

- [README.md](../README.md) - Project overview and setup
- [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Detailed migration guide
- [examples/](../examples/) - More code examples
