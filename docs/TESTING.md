# WPLiteCore Testing Guide

This guide covers testing for **library developers and contributors**. If you're just using WPLiteCore in your project, you don't need to run these tests.

## 🎯 Who This Guide Is For

| User Type | Purpose | Need Testing? |
|-----------|---------|---------------|
| **End Users** | Using WPLiteCore in projects | ❌ No - just use the library |
| **Library Developers** | Contributing to WPLiteCore | ✅ Yes - run full test suite |
| **Contributors** | Bug fixes, new features | ✅ Yes - ensure changes work |

---

## 🚀 Quick Testing Setup

### 1. **Environment Setup**
```bash
# Clone the repository
git clone https://github.com/your-org/WPLiteCore.git
cd WPLiteCore

# Install dependencies
composer install

# Set up testing environment
php setup.php
```

### 2. **Configure Test Environment**
Edit the generated `.env` file with your test API credentials:
```bash
# .env file
WPLITE_API_URL=https://your-test-api.com/wp-json/wp/v2
WPLITE_HASH_KEY=your-test-api-key
WPLITE_SITE_URL=https://your-test-site.com

# Test Data IDs (configure based on your test API)
WPLITE_TEST_POST_ID=32
WPLITE_TEST_MEDIA_ID=41
WPLITE_TEST_CATEGORY_ID=1
WPLITE_TEST_USER_ID=1
WPLITE_TEST_TAG_ID=1
```

### 3. **Run Tests**
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with detailed output
vendor/bin/phpunit --testdox
```

---

## 📋 Test Suite Overview

The WPLiteCore test suite includes **7 test classes** with **70+ comprehensive tests**:

### **Core Tests**
| Test File | Purpose | Test Count | Description |
|-----------|---------|------------|-------------|
| `ApiResponseTest.php` | API response handling | 11 tests | Tests response wrapper, pagination, error handling |
| `PostsClientTest.php` | Posts operations | 10 tests | Tests post retrieval, filtering, search functionality |
| `WordPressApiClientTest.php` | Core API client | 13 tests | Tests HTTP requests, authentication, error handling |
| `ValidatorTest.php` | Input validation | 15 tests | Tests parameter validation and sanitization |
| `WPLiteCoreTest.php` | Main framework class | 16 tests | Tests factory methods, configuration, client access |

### **Integration Tests**
| Test File | Purpose | Test Count | Description |
|-----------|---------|------------|-------------|
| `WireFrontApiIntegrationTest.php` | Live API testing | 7 tests | Tests against real WordPress API endpoints |
| `FeaturedImageTest.php` | Featured image handling | 4 tests | Tests image retrieval in OOP vs procedural approaches |

---

## 🧪 Running Specific Tests

### **Run Single Test File**
```bash
# Test API responses
vendor/bin/phpunit tests/ApiResponseTest.php

# Test posts functionality
vendor/bin/phpunit tests/PostsClientTest.php

# Test featured images
vendor/bin/phpunit tests/FeaturedImageTest.php

# Test live API integration
vendor/bin/phpunit tests/WireFrontApiIntegrationTest.php
```

### **Run Specific Test Method**
```bash
# Test specific functionality
vendor/bin/phpunit --filter testGetPosts
vendor/bin/phpunit --filter testFeaturedImage
vendor/bin/phpunit --filter testPostWithFeaturedImageOOP

# Test with class context
vendor/bin/phpunit --filter "PostsClientTest::testGetPosts"
vendor/bin/phpunit --filter "FeaturedImageTest::testPostWithFeaturedImageOOP"
```

### **Run Tests by Category**
```bash
# Run only unit tests (no API calls)
vendor/bin/phpunit --exclude-group integration

# Run only integration tests
vendor/bin/phpunit --group integration

# Run tests with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage/
```

---

## 📊 Understanding Test Output

### **Successful Test Run**
```bash
PHPUnit 10.5.47 by Sebastian Bergmann and contributors.

🔧 Testing against custom WireFront API: https://apis.wirefront.net/v2
🔑 Using API key: Present

✅ Successfully connected to WireFront API
📊 Total posts available: 16
📄 Total pages: 16

.................                                    70 / 70 (100%)

Time: 00:24.550, Memory: 10.00 MB

OK (70 tests, 425 assertions)
```

### **Test Failure Example**
```bash
FAILURES!
Tests: 70, Assertions: 425, Failures: 1.

There was 1 failure:

1) Tests\PostsClientTest::testGetPosts
Failed asserting that null is not null.

/path/to/WPLiteCore/tests/PostsClientTest.php:45
```

### **Skipped Tests**
Tests are automatically skipped when:
- No API key is configured
- API endpoint is not accessible
- Required test data doesn't exist

---

## 🔧 Test Configuration

### **Environment Variables**
Configure these in your `.env` file:

```bash
# Required for API tests
WPLITE_API_URL=https://your-api.com/wp-json/wp/v2  # Your WordPress API endpoint
WPLITE_HASH_KEY=your-secret-key                   # API authentication key
WPLITE_SITE_URL=https://your-site.com             # Your site URL

# Optional API keys
WIREFRONT_API_KEY=alternative-key                 # Alternative API key format

# Test Data Configuration
WPLITE_TEST_POST_ID=32                            # ID of post that exists in your API
WPLITE_TEST_MEDIA_ID=41                           # ID of media/image in your API
WPLITE_TEST_CATEGORY_ID=1                         # Category ID for filtering tests
WPLITE_TEST_USER_ID=1                             # User ID for author tests
WPLITE_TEST_TAG_ID=1                              # Tag ID for filtering tests

# Debug and Performance
WPLITE_DEBUG=true                                 # Enable debug output
WPLITE_TIMEOUT=30                                 # API request timeout (seconds)
```

### **PHPUnit Configuration**
The `phpunit.xml` file configures:
- Test directory locations
- Bootstrap files
- Environment variables
- Output formatting

---

## 📝 Individual Test Descriptions

### **1. ApiResponseTest.php**
Tests the `ApiResponse` wrapper class that standardizes API responses:

```bash
vendor/bin/phpunit tests/ApiResponseTest.php
```

**What it tests:**
- ✅ Successful response handling
- ✅ Error response handling  
- ✅ Empty response handling
- ✅ Pagination detection
- ✅ Data conversion (`toArray()`, `toJson()`)
- ✅ Item counting and retrieval
- ✅ Metadata handling

**Key test methods:**
- `testSuccessfulResponse()` - Valid API responses
- `testFailedResponse()` - Error handling
- `testPaginationDetection()` - Pagination logic
- `testToArray()` - Legacy format conversion

### **2. PostsClientTest.php**
Tests the `PostsClient` class for WordPress posts operations:

```bash
vendor/bin/phpunit tests/PostsClientTest.php
```

**What it tests:**
- ✅ Get posts with parameters
- ✅ Get single post by ID
- ✅ Get single post by slug
- ✅ Filter posts by category, tag, author
- ✅ Search posts functionality
- ✅ Get recent posts
- ✅ Filter by post status

**Key test methods:**
- `testGetPosts()` - Basic post retrieval
- `testGetPostById()` - Single post access
- `testSearchPosts()` - Search functionality
- `testGetPostsByCategory()` - Category filtering

### **3. WordPressApiClientTest.php**
Tests the core `WordPressApiClient` that handles HTTP requests:

```bash
vendor/bin/phpunit tests/WordPressApiClientTest.php
```

**What it tests:**
- ✅ HTTP client initialization
- ✅ API authentication
- ✅ Request/response handling
- ✅ Error handling and exceptions
- ✅ Timeout handling
- ✅ Different endpoint access
- ✅ Parameter validation

**Key test methods:**
- `testClientInitialization()` - Client setup
- `testGetDataWithValidEndpoint()` - Valid requests
- `testErrorHandling()` - Exception handling
- `testTimeoutHandling()` - Network timeouts

### **4. ValidatorTest.php**
Tests the `Validator` class for input validation and sanitization:

```bash
vendor/bin/phpunit tests/ValidatorTest.php
```

**What it tests:**
- ✅ Required field validation
- ✅ URL validation
- ✅ String length validation
- ✅ Integer validation
- ✅ Array validation
- ✅ Multiple validation rules
- ✅ Data sanitization
- ✅ Fluent interface

**Key test methods:**
- `testRequiredValidation()` - Required fields
- `testUrlValidation()` - URL format checking
- `testSanitizeString()` - String cleaning
- `testMultipleValidationRules()` - Complex validation

### **5. WPLiteCoreTest.php**
Tests the main `WPLiteCore` factory class:

```bash
vendor/bin/phpunit tests/WPLiteCoreTest.php
```

**What it tests:**
- ✅ Singleton pattern implementation
- ✅ Factory method creation
- ✅ Configuration handling
- ✅ Client access methods
- ✅ Quick access methods
- ✅ Multiple API configurations
- ✅ Debug mode configuration

**Key test methods:**
- `testSingletonInstance()` - Singleton behavior
- `testCreateNewInstance()` - Factory pattern
- `testQuickAccessMethods()` - Convenience methods
- `testMultipleApiConfigurations()` - Multi-site support

### **6. WireFrontApiIntegrationTest.php**
Tests integration with live WordPress API endpoints:

```bash
vendor/bin/phpunit tests/WireFrontApiIntegrationTest.php
```

**What it tests:**
- ✅ Real API connection
- ✅ Authentication with live API
- ✅ Posts retrieval with various parameters
- ✅ Search functionality
- ✅ Single post retrieval
- ✅ Error handling with invalid requests
- ✅ Multiple endpoint availability
- ✅ Response format compatibility

**Key test methods:**
- `testRealApiConnection()` - Live API connectivity
- `testGetPostsWithDifferentParameters()` - Parameter handling
- `testSearchFunctionality()` - Search features
- `testErrorHandlingWithInvalidRequests()` - Error scenarios

### **7. FeaturedImageTest.php**
Tests featured image handling in both OOP and procedural approaches:

```bash
vendor/bin/phpunit tests/FeaturedImageTest.php
```

**What it tests:**
- ✅ OOP featured image retrieval
- ✅ Procedural featured image retrieval  
- ✅ Direct featured image function
- ✅ OOP vs Procedural comparison
- ✅ Multiple image sizes
- ✅ Error handling for missing images

**Key test methods:**
- `testPostWithFeaturedImageOOP()` - OOP approach
- `testPostWithFeaturedImageProcedural()` - Procedural approach
- `testDirectFeaturedImageFunction()` - Direct function testing
- `testOOPvsProcedural()` - Approach comparison

---

## 🐛 Troubleshooting Tests

### **Common Issues**

#### **1. "No API key configured" Error**
```bash
# Problem
Tests are skipped with message: "No API key configured"

# Solution
Edit your .env file:
WPLITE_HASH_KEY=your-actual-api-key-here
```

#### **2. "API is not accessible" Error**
```bash
# Problem  
Tests fail with connectivity errors

# Solutions
1. Check your API URL is correct
2. Verify the API is online
3. Check network connectivity
4. Verify SSL certificates if using HTTPS
```

#### **3. "Post ID does not exist" Error**
```bash
# Problem
Tests fail because test post ID doesn't exist in your API

# Solution
Update your .env with valid post IDs:
WPLITE_TEST_POST_ID=123  # Use an actual post ID from your API
WPLITE_TEST_MEDIA_ID=456 # Use an actual media ID from your API
```

#### **4. PHPUnit Command Not Found**
```bash
# Problem
bash: vendor/bin/phpunit: No such file or directory

# Solution
composer install  # Install dependencies first
```

#### **5. Memory or Timeout Issues**
```bash
# Problem
Tests timeout or run out of memory

# Solutions
# Increase memory limit
php -d memory_limit=512M vendor/bin/phpunit

# Increase timeout
WPLITE_TIMEOUT=60 vendor/bin/phpunit
```

### **Debug Mode**
Enable debug output to see detailed test information:

```bash
# Set debug mode in .env
WPLITE_DEBUG=true

# Run tests with verbose output
vendor/bin/phpunit --testdox
```

### **Test Isolation**
If tests interfere with each other:

```bash
# Run tests in separate processes
vendor/bin/phpunit --process-isolation

# Run specific problematic test alone
vendor/bin/phpunit --filter testSpecificProblem
```

---

## 📈 Test Coverage

### **Generate Coverage Report**
```bash
# Requires Xdebug extension
vendor/bin/phpunit --coverage-html coverage/

# Open coverage report
open coverage/index.html
```

### **Coverage Goals**
- **Unit Tests**: 90%+ code coverage
- **Integration Tests**: All major API endpoints
- **Error Scenarios**: All exception paths tested
- **Backward Compatibility**: All legacy functions covered

---

## 🚀 Adding New Tests

### **Creating a New Test Class**
```php
<?php

namespace Tests;

class YourNewTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Your setup code
    }
    
    public function testYourFeature(): void
    {
        // Arrange
        $expected = 'expected result';
        
        // Act
        $actual = $this->wpLite->yourMethod();
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
}
```

### **Test Naming Convention**
- Test files: `YourFeatureTest.php`
- Test methods: `testSpecificBehavior()`
- Use descriptive names that explain what's being tested

### **Best Practices**
1. **Arrange, Act, Assert** pattern
2. **Test one thing** per test method
3. **Use descriptive assertions** with custom messages
4. **Clean up** after tests if needed
5. **Skip tests** when prerequisites aren't met
6. **Mock external dependencies** when appropriate

---

## 📚 Additional Resources

### **PHPUnit Documentation**
- [PHPUnit Manual](https://phpunit.de/documentation.html)
- [Assertions Reference](https://phpunit.de/manual/current/en/appendixes.assertions.html)
- [Test Doubles](https://phpunit.de/manual/current/en/test-doubles.html)

### **WPLiteCore Specific**
- [Main README](../README.md) - Library usage
- [Usage Guide](USAGE.md) - API documentation  
- [Migration Guide](MIGRATION_GUIDE.md) - Upgrade instructions
- [Examples](../examples/) - Code examples

### **WordPress API Reference**
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [Posts Endpoint](https://developer.wordpress.org/rest-api/reference/posts/)
- [Media Endpoint](https://developer.wordpress.org/rest-api/reference/media/)

---

## ✅ Testing Checklist

Before submitting contributions, ensure:

- [ ] All tests pass (`vendor/bin/phpunit`)
- [ ] New features have corresponding tests
- [ ] Tests are properly documented
- [ ] Integration tests work with real API
- [ ] Backward compatibility is maintained
- [ ] Error scenarios are covered
- [ ] Performance is acceptable
- [ ] Code follows project standards

---

**Remember**: Testing is for library developers and contributors. End users can simply use WPLiteCore without running these tests! 🚀
