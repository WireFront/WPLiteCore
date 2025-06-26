# WPLiteCore Usage Guide

## 🚀 For End Users (Using WPLiteCore in Your Projects)

If you're building a website or application and want to use WPLiteCore, it's very simple:

### Installation
```bash
composer require your-org/wplitecore
```

### Basic Usage
```php
<?php
require_once 'vendor/autoload.php';

use WPLite\WPLiteCore;

// Create instance with your API details
$wpLite = WPLiteCore::create(
    'https://your-wordpress-site.com/wp-json/wp/v2',
    'your-secret-key'
);

// Get posts
$posts = $wpLite->posts()->getPosts(['per_page' => 5]);
if ($posts->isSuccess()) {
    foreach ($posts->getItems() as $post) {
        echo $post['title']['rendered'] . "\n";
    }
}
```

**That's it!** No `.env` files, no setup scripts, no configuration needed.

---

## 🔧 For Library Developers (Working ON WPLiteCore)

If you're contributing to or modifying the WPLiteCore library itself:

### Development Setup
```bash
git clone this-repo
cd WPLiteCore
composer install
php setup.php          # Creates .env for testing
vendor/bin/phpunit      # Run tests
```

### Configuration for Testing
The `.env` file and `Config.php` are used for:
- Running unit tests against real APIs
- Testing different configurations
- Library development and debugging

---

## 📁 File Structure Explanation

```
WPLiteCore/
├── src/                    # Library source code (what end users get)
├── tests/                  # Tests (for library development)
├── examples/               # Usage examples
├── .env.example           # For library developers only
├── setup.php              # For library developers only
├── Config.php             # Used internally for tests
└── README.md              # This file
```

**For End Users:** You only need the `src/` directory (installed via Composer)
**For Library Developers:** You need everything for testing and development

---

## 🎯 Quick Decision Guide

**Are you building a website using WPLiteCore?**
👉 Use: `WPLiteCore::create('your-api', 'your-key')`

**Are you contributing to the WPLiteCore library?**
👉 Use: `php setup.php` and configure `.env` for testing
