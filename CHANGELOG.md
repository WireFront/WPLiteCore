# Changelog

All notable changes to WPLiteCore will be documented in this file.

## [2.0.1] - 2025-07-05

### 🐛 Bug Fixes
- **Autoloader Fatal Error Fix**: Resolved critical fatal error when including WPLiteCore files in Composer-managed projects
- **Smart Autoloader Detection**: Replaced hardcoded `vendor/autoload.php` paths with intelligent autoloader detection
- **Composer Package Compatibility**: Fixed compatibility issues when WPLiteCore is installed as a Composer dependency

### 🔧 Technical Improvements
- **Conditional Class Loading**: Added class existence checks before attempting to load autoloaders
- **Multiple Autoloader Paths**: Support for various installation scenarios (standalone, Composer package, different nesting levels)
- **Graceful Error Handling**: Improved error messages when autoloaders cannot be found
- **Cross-Environment Support**: Enhanced compatibility between standalone and Composer-managed environments

### 📁 Files Modified
- `cached_router.php` - Smart autoloader detection for cached routing functionality
- `cache_manager.php` - Conditional autoloader for cache management script
- `bin/cache_manager.php` - CLI cache manager with proper autoloader detection
- `setup-files/routes.php` - Setup routes file with autoloader fix
- `setup-files/routes_with_cache.php` - Cached routes setup with autoloader fix

### 🚨 Critical Fix
This patch resolves the fatal error:
```
Fatal error: Failed opening required '/path/to/project/vendor/wirefront/wplitecore/vendor/autoload.php'
```

This error occurred when WPLiteCore was installed via Composer in existing projects that already had their own autoloader configured.

### 🧪 Testing
- Comprehensive test coverage for autoloader scenarios
- Validated in both standalone and Composer-managed environments
- Confirmed backward compatibility with existing installations

### 💡 Impact
- **Before**: Fatal errors when including WPLiteCore files in Composer projects
- **After**: Seamless integration in any PHP project environment
- **Compatibility**: Works in standalone installations and as Composer dependencies

---

## [2.0.0] - 2025-07-02

### 🚀 Major Changes
- **Complete OOP Architecture**: Migrated from procedural to object-oriented programming approach
- **Namespace Implementation**: Added proper PSR-4 namespace structure (`WPLiteCore\`)
- **Composer Support**: Added full Composer autoloading and dependency management
- **PHPUnit Integration**: Implemented comprehensive unit testing framework

### ✨ New Features
- **Security Layer**: New `Security` class with JWT token validation and request sanitization
- **Error Handling**: Centralized error handling with custom exception classes
- **Data Validation**: Enhanced input validation and sanitization
- **Caching System**: Improved caching mechanism with better performance
- **API Response Handling**: Structured API response classes for better data management
- **Configuration Management**: Centralized configuration handling

### 🏗️ Architecture Changes
- **New Class Structure**:
  - `WPLiteCore\Core\Config` - Configuration management
  - `WPLiteCore\Core\Cache` - Caching functionality
  - `WPLiteCore\Core\Security` - Security and authentication
  - `WPLiteCore\Core\ErrorHandler` - Error handling
  - `WPLiteCore\Core\Validator` - Data validation
  - `WPLiteCore\Api\WordPressApiClient` - Main API client
  - `WPLiteCore\Api\PostsClient` - Posts API handling
  - `WPLiteCore\Api\PagesClient` - Pages API handling
  - `WPLiteCore\Api\MediaClient` - Media API handling
  - `WPLiteCore\Api\ApiResponse` - API response handling

### 🔧 Technical Improvements
- **PSR-4 Autoloading**: Proper namespace-based autoloading
- **Type Declarations**: Added strict typing throughout the codebase
- **Error Handling**: Custom exception classes for better error management
- **JWT Authentication**: Optional JWT token support for enhanced security
- **Performance Optimization**: Improved caching and API response handling
- **Code Quality**: Better separation of concerns and single responsibility principle

### 📝 Documentation
- **README.md**: Updated with new OOP usage examples
- **USAGE.md**: Comprehensive usage documentation
- **MIGRATION_GUIDE.md**: Guide for migrating from v1.x to v2.0.0
- **TESTING.md**: Testing documentation and guidelines

### 🧪 Testing
- **Unit Tests**: Complete test coverage for all major components
- **Integration Tests**: API integration testing
- **Performance Tests**: Caching performance validation
- **Error Handling Tests**: Comprehensive error scenario testing

### 📦 Dependencies
- **firebase/php-jwt**: JWT token handling (^6.0)
- **phpunit/phpunit**: Unit testing framework (^9.0)

### 🔄 Migration Notes
- **Breaking Changes**: This is a major version with breaking changes
- **Backward Compatibility**: Limited backward compatibility maintained through procedural wrapper functions
- **Migration Required**: Existing implementations will need updates to use new OOP structure

### 🐛 Bug Fixes
- Fixed caching issues with large datasets
- Improved error handling for API failures
- Better validation of configuration parameters
- Enhanced security for API requests

### 🚨 Security
- Added JWT token validation
- Improved request sanitization
- Enhanced input validation
- Better error message handling to prevent information disclosure

### 📊 Performance
- Optimized caching mechanism
- Reduced memory usage
- Improved API response times
- Better resource management

### 🔧 Development
- **Composer**: Full composer.json with proper dependencies
- **Autoloading**: PSR-4 compliant autoloading
- **Testing**: PHPUnit configuration and comprehensive test suite
- **Logging**: Improved logging system for debugging

---

## How to Upgrade

### From v1.x to v2.0.0

1. **Install via Composer** (recommended):
   ```bash
   composer require wplitecore/wplitecore
   ```

2. **Update your code** to use the new OOP structure:
   ```php
   // Old way (v1.x)
   $posts = wlc_get_posts();
   
   // New way (v2.0.0)
   use WPLiteCore\WPLiteCore;
   
   $wlc = new WPLiteCore();
   $posts = $wlc->posts()->getPosts();
   ```

3. **Update configuration** to use the new Config class
4. **Review security settings** and implement JWT if needed
5. **Update error handling** to use new exception classes

For detailed migration instructions, see [MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md).

---

## Support

- **Documentation**: See `/docs` folder for comprehensive guides
- **Examples**: Check `/examples` folder for usage examples
- **Issues**: Report bugs and feature requests on the project repository

---

*This changelog follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format.*
