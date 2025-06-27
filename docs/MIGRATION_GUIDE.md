# WPLiteCore OOP Migration Guide

This guide shows you how to gradually migrate from procedural functions to the new OOP classes.

## 🚀 Quick Start

```php
// Include autoloader
require_once 'vendor/autoload.php';

use WPLite\WPLiteCore;

// Initialize (uses your existing config constants)
$wpLite = WPLiteCore::getInstance();

// Start using OOP methods alongside your existing code
$posts = $wpLite->posts()->getPosts();
```

## 📚 Function Migration Reference

### Getting API Data

**OLD (Procedural):**
```php
$data = wlc_get_api_data([
    'key' => 'your-key',
    'api_url' => 'https://site.com/wp-json/wp/v2',
    'endpoint' => 'posts',
    'parameters' => ['per_page' => 10]
]);
```

**NEW (OOP):**
```php
$wpLite = WPLiteCore::getInstance();
$response = $wpLite->posts()->getPosts(['per_page' => 10]);
$data = $response->toArray(); // Convert to legacy format if needed
```

### Getting Single Posts

**OLD (Procedural):**
```php
$post = wlc_single_post([
    'key' => 'your-key',
    'api_url' => 'https://site.com/wp-json/wp/v2',
    'type' => 'posts',
    'slug' => 'my-post'
]);
```

**NEW (OOP):**
```php
$response = $wpLite->posts()->getPostBySlug('my-post');
$post = $response->getItems();
```

### Getting Featured Images

**OLD (Procedural):**
```php
$image = wlc_featured_image([
    'key' => 'your-key',
    'api_url' => 'https://site.com/wp-json/wp/v2',
    'attachment_id' => 123,
    'size' => 'medium'
]);
$imageUrl = $image['url'];
```

**NEW (OOP):**
```php
$imageUrl = $wpLite->getFeaturedImage(123, 'medium');
```

### Getting Comments

**OLD (Procedural):**
```php
$comments = wlc_post_comments([
    'key' => 'your-key',
    'api_url' => 'https://site.com/wp-json/wp/v2',
    'post_id' => 123
]);
```

**NEW (OOP):**
```php
$response = $wpLite->api()->getComments(123);
$comments = $response->getItems();
```

## 🔄 Migration Strategy

### Phase 1: Parallel Usage
- Keep all existing procedural code
- Start using OOP for new features
- Both systems work together

### Phase 2: Gradual Replacement
- Replace procedural calls during refactoring
- Use `->toArray()` method for template compatibility
- Test thoroughly

### Phase 3: Full Migration
- Remove procedural function calls
- Use native OOP responses
- Clean up legacy code

## ✅ Benefits of OOP Version

1. **Better Error Handling**: Proper exceptions instead of array returns
2. **Type Safety**: Better IDE support and debugging
3. **Method Chaining**: More intuitive API usage
4. **Separation of Concerns**: Specialized clients for different content types
5. **Extensibility**: Easy to add new features
6. **Testing**: Much easier to unit test

## 🛠️ Backward Compatibility

The OOP system maintains full backward compatibility:

```php
// OOP response can be converted to legacy format
$oopResponse = $wpLite->posts()->getPosts();
$legacyFormat = $oopResponse->toArray();

// $legacyFormat now has the same structure as procedural functions:
// ['result' => true, 'items' => [...], 'total_posts' => '10', 'total_pages' => '2']
```

## 📋 Common Patterns

### Error Handling
```php
try {
    $response = $wpLite->posts()->getPostBySlug('my-post');
    
    if ($response->isSuccess()) {
        $post = $response->getItems();
        // Use post data
    } else {
        // Handle no results
    }
    
} catch (ApiException $e) {
    // Handle API errors
} catch (ValidationException $e) {
    // Handle validation errors
}
```

### Checking Results
```php
$response = $wpLite->posts()->getPosts();

// Check if successful
if ($response->isSuccess()) {
    // Process items
}

// Check if has items
if ($response->hasItems()) {
    // Process items
}

// Get item count
$count = $response->getItemCount();

// Check if paginated
if ($response->isPaginated()) {
    $totalPages = $response->getTotalPages();
}
```

### Multiple API Configurations
```php
// Different WordPress sites
$site1 = WPLiteCore::create('https://site1.com/wp-json/wp/v2', 'key1');
$site2 = WPLiteCore::create('https://site2.com/wp-json/wp/v2', 'key2');

$posts1 = $site1->posts()->getPosts();
$posts2 = $site2->posts()->getPosts();
```

## 🎯 Next Steps

1. **Try the examples**: Run `php examples/oop_usage_examples.php`
2. **Start small**: Use OOP for one new feature
3. **Convert gradually**: Replace functions during maintenance
4. **Leverage benefits**: Use better error handling and type safety
5. **Provide feedback**: Help improve the OOP system

The beauty of this approach is that you can migrate at your own pace while maintaining a fully functional system!
