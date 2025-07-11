# WPLiteCore

A modern PHP framework for WordPress REST API interactions with both procedural and object-oriented programming approaches.

## 📍 Quick Navigation

**👤 I want to USE WPLiteCore in my project:**
- [Installation & Basic Usage](#-quick-start-for-end-users) ← Start here
- [Complete API Reference](docs/USAGE.md) ← Full documentation
- [Migration from Old Code](docs/MIGRATION_GUIDE.md)
- [🚀 **Cache System Documentation**](docs/COMPLETE_CACHE_SYSTEM.md) ← **Performance optimization**
- [⚡ **Cache Quick Start Guide**](docs/CACHE_QUICK_START.md) ← **Get started with caching**

**👨‍💻 I want to CONTRIBUTE to WPLiteCore:**
- [Development Setup](#-for-library-developers-and-contributors) ← Start here
- [Testing Guide](docs/TESTING.md) ← Testing documentation
- [Configuration System](#configuration-system-details)
- [Running Tests](#running-tests)
- [Contributing Guidelines](#contributing)

---

## 🚀 Quick Start for End Users

If you're using WPLiteCore in your project, it's very simple:

### Installation
```bash
composer require wirefront/wplitecore
```

### Basic Usage
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
    null  // No secret key needed for public APIs
);

// Get posts (works with or without JWT)
$posts = $wpLite->posts()->getPosts(['per_page' => 5]);
if ($posts->isSuccess()) {
    foreach ($posts->getItems() as $post) {
        echo $post['title']['rendered'] . "\n";
    }
}

// Get a single post with featured image
$post = $wpLite->posts()->getPostById(32, 'medium');
if ($post->isSuccess()) {
    $postData = $post->getItems();
    echo $postData['title']['rendered'] . "\n";
    
    // Featured image is automatically retrieved
    if ($postData['featured_image']) {
        echo '<img src="' . $postData['featured_image'] . '" alt="Featured">';
    }
}
```

**🔓 JWT Authentication is Optional!**

- **Public content**: Use `null` as the secret key - no authentication needed
- **Protected content**: Provide your JWT secret key for authenticated requests  
- **Mixed usage**: You can create multiple instances with different authentication levels

WPLiteCore automatically handles authentication based on whether you provide a secret key or not.

### Procedural Functions (Backward Compatibility)
```php
// You can still use the original procedural functions
$result = wlc_single_post([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'id' => 32,
    'type' => 'posts',
    'media_size' => 'medium'
]);

if ($result && isset($result['title'])) {
    echo "Title: " . $result['title'] . "\n";
    if (isset($result['featured_image']['url'])) {
        echo "Featured Image: " . $result['featured_image']['url'] . "\n";
    }
}
```

**That's it!** No configuration files, no setup scripts needed for end users.

---

## 🚀 High-Performance Caching System

WPLiteCore includes a powerful, multi-layered caching system that can dramatically improve your application's performance:

### ⚡ Performance Benefits
- **API Responses**: 80-99% faster (from 200ms to 2-10ms)
- **Route Responses**: 95-99.9% faster (from 50ms to <1ms)  
- **Database Load**: Reduced by 70-95%
- **Server Resources**: 60-90% reduction in CPU usage

### 🔧 Cache System Components

1. **Core Cache System** - File-based caching foundation
2. **API Response Caching** - WordPress API call optimization  
3. **Route Response Caching** - Web route response optimization

### 📖 **[Complete Cache Documentation](docs/COMPLETE_CACHE_SYSTEM.md)**

**Essential Cache Topics:**
- [⚡ **Quick Start Guide**](docs/CACHE_QUICK_START.md) - **Get started in 5 minutes**
- [Getting Started with Caching](docs/COMPLETE_CACHE_SYSTEM.md#overview)
- [Basic Routing vs Cached Routing](docs/COMPLETE_CACHE_SYSTEM.md#basic-routing)
- [WordPress API Response Caching](docs/COMPLETE_CACHE_SYSTEM.md#api-response-caching)
- [Performance Optimization Guide](docs/COMPLETE_CACHE_SYSTEM.md#performance-optimization)
- [Cache Management & CLI Tools](docs/COMPLETE_CACHE_SYSTEM.md#cache-management)
- [Production Best Practices](docs/COMPLETE_CACHE_SYSTEM.md#best-practices)

### Quick Cache Example

```php
// Include cached router for high-performance routing
require_once 'cached_router.php';

// Initialize caching
init_cached_router(['enabled' => true, 'default_ttl' => 3600]);

// Standard route (no caching)
get('/api/posts', function() {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->posts()->getAll(); // ~200ms every request
});

// Cached route (massive performance improvement)
cached_get('/api/posts', function() {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->posts()->getAll(); // ~200ms first request, ~2ms subsequent requests
}, ['ttl' => 1800]); // Cache for 30 minutes

// WordPress API with automatic caching
wp_api_cached_route('/api/posts/$id', function($id) {
    $wpLite = \WPLite\WPLiteCore::getInstance();
    return $wpLite->posts()->getById($id);
}, ['ttl' => 3600, 'vary_by_params' => true]);
```

### Cache Management

```bash
# Command-line cache management
php cache_manager.php stats    # View cache statistics  
php cache_manager.php clear    # Clear all cache
php cache_manager.php cleanup  # Remove expired entries
```

**🔗 [Full Cache System Guide →](docs/COMPLETE_CACHE_SYSTEM.md)** - Complete documentation with examples, configuration, and best practices

---

## 🔧 For Library Developers and Contributors

> **⚠️ IMPORTANT:** This section is for developers working **ON** the WPLiteCore library itself (contributors, maintainers, testers). If you're just **USING** WPLiteCore in your project, you can skip this entire section.

### Development Environment Setup

If you're contributing to WPLiteCore or need to run tests:

#### 1. Clone and Install
```bash
git clone https://github.com/WireFront/WPLiteCore.git
cd WPLiteCore
composer install
```

#### 2. Development Configuration Setup
```bash
php setup.php
```

This creates a `.env` file for testing. You'll need to configure it with real API credentials for testing.

#### 3. Manual Configuration (Alternative)
```bash
cp .env.example .env
```

Edit the `.env` file:
```env
# API Configuration for Testing
WPLITE_API_URL=https://your-test-api.com/wp-json/wp/v2
WPLITE_HASH_KEY=your-test-secret-key
WPLITE_SITE_URL=https://your-test-site.com

# Test Configuration
WPLITE_TEST_POST_ID=32
WPLITE_TEST_MEDIA_ID=41
WPLITE_TEST_CATEGORY_ID=1
WPLITE_TEST_USER_ID=1
WPLITE_TEST_TAG_ID=1

# Debug Mode
WPLITE_DEBUG=true
```

### Configuration System Details

#### Config.php Class
The `Config.php` class is used internally by library developers for:
- **Testing**: Loading test API credentials securely from `.env`
- **Development**: Managing different testing environments
- **CI/CD**: Environment-based configuration in build pipelines

> **Note for End Users:** You don't need to use `Config.php` directly. Just pass your API details to `WPLiteCore::create()` as shown in the Quick Start section.

**Developer Usage:**
```php
use WPLite\Core\Config;

Config::load();                          // Load from .env file
$apiUrl = Config::getApiUrl();           // Get configured API URL
$hashKey = Config::getHashKey();         // Get configured hash key
$testPostId = Config::get('test_post_id', 32); // Get test post ID
```

**End User Usage (Simpler):**
```php
// End users just pass values directly - no Config needed
$wpLite = WPLiteCore::create('https://your-site.com/wp-json/wp/v2', 'your-key');
```

#### Environment Variables (For Library Developers Only)

> **End Users:** You don't need to worry about environment variables. Just pass your API details directly to `WPLiteCore::create()`.

**For Library Testing and Development:**

| Variable | Purpose | Required for Tests | Example |
|----------|---------|-------------------|---------|
| `WPLITE_API_URL` | Test API endpoint | ✅ Yes | `https://yoursite.com/wp-json/wp/v2` |
| `WPLITE_HASH_KEY` | Authentication key | ✅ Yes | `your-secret-api-key` |
| `WPLITE_SITE_URL` | Test site URL | ❌ Optional | `https://yoursite.com` |
| `WPLITE_TEST_POST_ID` | Post ID for testing | ❌ Optional (default: 32) | `123` |
| `WPLITE_TEST_MEDIA_ID` | Media ID for testing | ❌ Optional (default: 41) | `456` |
| `WPLITE_DEBUG` | Enable debug mode | ❌ Optional (default: false) | `true` |

**Why Environment Variables for Development?**
- Keeps real API credentials out of code
- Allows different test environments
- Secure CI/CD pipeline configuration
- Multiple developers can use different test sites

#### What Happens Without Configuration

**For End Users Using the Library:**
Nothing special - just pass your API details directly:
```php
$wpLite = WPLiteCore::create('https://your-site.com/wp-json/wp/v2', 'your-key');
// Works perfectly! No configuration files needed.
```

**For Library Developers Running Tests:**
Tests will fail or be skipped without proper `.env` configuration:
```php
// If no WPLITE_HASH_KEY is set in .env:
$this->markTestSkipped('No API key configured. Please set WPLITE_HASH_KEY environment variable.');

// If Config::getHashKey() is called without setup:
throw new RuntimeException('HASH_KEY is not configured. Please set WPLITE_HASH_KEY environment variable.');
```

**Default fallbacks in development:**
- API URL defaults to `https://api.example.com/v2` (safe placeholder)
- Test post ID defaults to `32`
- Debug mode defaults to `false`
- Tests skip if real credentials aren't available

#### Configuration Summary

| User Type | Configuration Method | Purpose | Files Needed |
|-----------|---------------------|---------|--------------|
| **End Users** | Direct parameters | Using the library | None - just use the library! |
| **Library Developers** | `.env` file | Testing & development | `.env` (from `.env.example`) |
| **CI/CD Systems** | Environment variables | Automated testing | Environment vars only |

```php
// End User (Simple):
$wpLite = WPLiteCore::create($apiUrl, $secretKey);

// Developer (With Config):
Config::load();
$wpLite = WPLiteCore::create(Config::getApiUrl(), Config::getHashKey());
```

### Running Tests

For comprehensive testing documentation, see **[📋 Testing Guide](docs/TESTING.md)**

#### Quick Test Commands
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with detailed output
vendor/bin/phpunit --testdox

# Run specific test file
vendor/bin/phpunit tests/FeaturedImageTest.php

# Run specific test method
vendor/bin/phpunit --filter "testPostWithFeaturedImageOOP"
```

#### Test Requirements
- **✅ API Credentials**: Configure `.env` with real API endpoints
- **✅ Test Data**: Ensure test post/media IDs exist in your API
- **✅ Network Access**: Tests connect to live WordPress APIs

📖 **[Complete Testing Guide →](docs/TESTING.md)** - Detailed instructions, troubleshooting, and test descriptions

### File Structure (Development)

```
WPLiteCore/
├── classes/                # Core library classes
│   ├── Api/               # API client classes
│   ├── Core/              # Config and core functionality
│   └── Exceptions/        # Custom exceptions
├── docs/                  # Documentation files
│   ├── USAGE.md          # Complete API reference 
│   ├── TESTING.md        # Testing guide
│   └── MIGRATION_GUIDE.md # Migration documentation
├── tests/                 # PHPUnit test suite
│   ├── BaseTestCase.php   # Base test class
│   ├── FeaturedImageTest.php # Featured image tests
│   └── ...               # Other test files
├── examples/              # Usage examples
│   ├── simple_usage.php   # For end users
│   └── oop_usage_examples.php # Comprehensive examples
├── .env.example          # Configuration template (for developers)
├── .env                  # Your actual config (git-ignored)
├── setup.php             # Development setup script
├── phpunit.xml           # PHPUnit configuration
├── composer.json         # Dependencies and autoload
└── functions.php         # Legacy procedural functions
```

### Security for Developers

**🔒 Critical Security Notes:**
- **Never commit `.env` files** - they contain real API credentials
- Use `.env.example` for safe templates only
- Real API URLs and keys should only exist in environment variables
- The `.gitignore` already protects `.env` files

**Testing Security:**
```bash
# Check that no real secrets are committed:
git log -p | grep -i "secret\|password\|key\|api\..*\.net"

# Verify .gitignore protection:
git status  # Should not show .env file
```

### Contributing

When contributing to WPLiteCore:

1. **Set up development environment** using `php setup.php`
2. **Configure `.env`** with your test API credentials
3. **Run tests** to ensure nothing breaks: `vendor/bin/phpunit`
4. **Write tests** for new features
5. **Update documentation** if adding new functionality
6. **Never commit** real API credentials or `.env` files

---

## 📚 API Reference

### Core Classes

#### WPLiteCore
Main entry point for the library.

```php
// Create instance
$wpLite = WPLiteCore::create($apiUrl, $secretKey, $debug = false);

// Access API clients
$posts = $wpLite->posts();     // PostsClient
$api = $wpLite->api();         // WordPressApiClient
```

#### PostsClient
Handles WordPress posts operations.

```php
$posts = $wpLite->posts();

// Get multiple posts
$response = $posts->getPosts(['per_page' => 10]);

// Get single post by ID
$response = $posts->getPostById(32, 'medium');

// Get single post by slug
$response = $posts->getPostBySlug('my-post-slug');

// Search posts
$response = $posts->searchPosts('search term');
```

#### ApiResponse
Handles API response data.

```php
if ($response->isSuccess()) {
    $items = $response->getItems();        // Get response data
    $total = $response->getTotalPosts();   // Get total count
    $pages = $response->getTotalPages();   // Get page count
}

// Convert to legacy format
$legacyArray = $response->toArray();
```

### Legacy Functions

#### wlc_single_post()
Get a single post with all related data.

```php
$result = wlc_single_post([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'id' => 32,                    // Post ID
    'type' => 'posts',             // Post type
    'media_size' => 'medium'       // Featured image size
]);
```

#### wlc_featured_image()
Get featured image for a specific attachment ID.

```php
$image = wlc_featured_image([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2/',
    'attachment_id' => 41,
    'size' => 'medium'
]);
```

#### wlc_get_api_data()
Generic API data retrieval function.

```php
$data = wlc_get_api_data([
    'key' => 'your-secret-key',
    'api_url' => 'https://your-site.com/wp-json/wp/v2',
    'endpoint' => 'posts',
    'parameters' => ['per_page' => 5]
]);
```

---

## 🔄 Migration Guide

For detailed migration from procedural to OOP approach, see **[📋 Migration Guide](docs/MIGRATION_GUIDE.md)**.

## 📖 Documentation & Examples

| Document | Description | For |
|----------|-------------|-----|
| **[docs/USAGE.md](docs/USAGE.md)** | Complete API reference with examples | 👤 End Users |
| **[docs/COMPLETE_CACHE_SYSTEM.md](docs/COMPLETE_CACHE_SYSTEM.md)** | **Complete cache system guide** | 🚀 **Performance** |
| **[docs/CACHE_QUICK_START.md](docs/CACHE_QUICK_START.md)** | **Cache quick start (5 minutes)** | ⚡ **Quick Setup** |
| **[docs/TESTING.md](docs/TESTING.md)** | Testing guide and test descriptions | 🔧 Contributors |
| **[docs/MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md)** | Upgrade from procedural to OOP | 🔄 Existing Users |
| **[examples/simple_usage.php](examples/simple_usage.php)** | Simple usage examples | 👤 End Users |
| **[examples/oop_usage_examples.php](examples/oop_usage_examples.php)** | Advanced OOP examples | 👤 End Users |

## 🤝 Contributing

See the development setup section above. All contributions welcome!

## 📄 License

MIT License - see LICENSE file for details.

