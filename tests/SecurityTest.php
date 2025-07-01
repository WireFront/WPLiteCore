<?php

namespace Tests;

use WPLite\Core\Validator;
use WPLite\Core\Security;
use WPLite\Exceptions\ValidationException;

/**
 * Security and Input Sanitization Tests
 */
class SecurityTest extends BaseTestCase
{
    public function testPathTraversalPrevention(): void
    {
        // Test path traversal attempts
        $dangerousPaths = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc//passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            'file.php/../../../etc/passwd',
            'file.php\..\..\..\..\etc\passwd'
        ];
        
        foreach ($dangerousPaths as $path) {
            $sanitized = Validator::sanitizePath($path);
            $this->assertStringNotContainsString('..', $sanitized);
            // The sanitized path should not contain directory traversal patterns
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
            // The final result should be just a filename (basename)
            $this->assertNotEmpty($sanitized, "Path should not be empty after sanitization");
        }
    }
    
    public function testUrlValidation(): void
    {
        // Set environment to allow localhost testing
        putenv('WPLITE_DEBUG=true');
        
        // Valid URLs
        $validUrls = [
            'https://example.com/api/v2',
            'http://localhost:8080/wp-json/wp/v2',
            'https://subdomain.example.com/path'
        ];
        
        foreach ($validUrls as $url) {
            $this->assertTrue(Validator::validateSecureUrl($url), "URL should be valid: {$url}");
        }
        
        // Invalid URLs
        $invalidUrls = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'ftp://example.com/file.txt',
            'file:///etc/passwd',
            'invalid-url',
            ''
        ];
        
        foreach ($invalidUrls as $url) {
            $this->assertFalse(Validator::validateSecureUrl($url), "URL should be invalid: {$url}");
        }
        
        // Clean up
        putenv('WPLITE_DEBUG');
    }
    
    public function testEndpointSanitization(): void
    {
        // Valid endpoints
        $validEndpoints = [
            'posts',
            'pages',
            'media',
            'users/1',
            'posts/categories'
        ];
        
        foreach ($validEndpoints as $endpoint) {
            $sanitized = Validator::sanitizeEndpoint($endpoint);
            $this->assertNotEmpty($sanitized);
        }
        
        // Invalid endpoints should throw exception
        $invalidEndpoints = [
            'malicious',
            'admin',
            'wp-admin',
            'plugins',
            '../posts'
        ];
        
        foreach ($invalidEndpoints as $endpoint) {
            $this->expectException(ValidationException::class);
            Validator::sanitizeEndpoint($endpoint);
        }
    }
    
    public function testParameterValidation(): void
    {
        $validParams = [
            'per_page' => 10,
            'page' => 1,
            'search' => 'test query',
            'categories' => [1, 2, 3],
            'order' => 'desc',
            'orderby' => 'date'
        ];
        
        $sanitized = Validator::validateApiParameters($validParams);
        
        $this->assertEquals(10, $sanitized['per_page']);
        $this->assertEquals(1, $sanitized['page']);
        $this->assertEquals('test query', $sanitized['search']);
        $this->assertEquals([1, 2, 3], $sanitized['categories']);
        $this->assertEquals('desc', $sanitized['order']);
        $this->assertEquals('date', $sanitized['orderby']);
    }
    
    public function testParameterSanitization(): void
    {
        $dirtyParams = [
            'per_page' => '10<script>alert(1)</script>', // Should extract 10
            'search' => '<script>alert("xss")</script>',
            'order' => 'invalid_order',
            'categories' => '1,2,3,invalid',
            'malicious_param' => 'should_be_removed'
        ];
        
        $sanitized = Validator::validateApiParameters($dirtyParams);
        
        // per_page should be 10 (extracted from the string and limited)
        $this->assertEquals(10, $sanitized['per_page']);
        $this->assertStringNotContainsString('<script>', $sanitized['search']);
        $this->assertEquals('desc', $sanitized['order']); // Should default to 'desc'
        $this->assertEquals([1, 2, 3, 0], $sanitized['categories']); // Invalid becomes 0
        $this->assertArrayNotHasKey('malicious_param', $sanitized);
        
        // Test extreme per_page values
        $extremeParams = ['per_page' => '999'];
        $sanitizedExtreme = Validator::validateApiParameters($extremeParams);
        $this->assertEquals(100, $sanitizedExtreme['per_page']); // Should be capped at 100
    }
    
    public function testRateLimiting(): void
    {
        Security::clearRateLimitData();
        
        $identifier = 'test_user';
        $maxRequests = 5;
        $timeWindow = 60;
        
        // Should allow first 5 requests
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue(Security::checkRateLimit($identifier, $maxRequests, $timeWindow));
        }
        
        // Should deny 6th request
        $this->assertFalse(Security::checkRateLimit($identifier, $maxRequests, $timeWindow));
        
        // Different identifier should be allowed
        $this->assertTrue(Security::checkRateLimit('different_user', $maxRequests, $timeWindow));
    }
    
    public function testSlidingWindowRateLimit(): void
    {
        Security::clearRateLimitData();
        
        $identifier = 'sliding_test';
        $maxRequests = 3;
        $timeWindow = 10;
        
        // Test sliding window behavior
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue(Security::checkRateLimit($identifier, $maxRequests, $timeWindow, 'sliding_window'));
        }
        
        // Should be denied
        $this->assertFalse(Security::checkRateLimit($identifier, $maxRequests, $timeWindow, 'sliding_window'));
    }
    
    public function testInputSanitization(): void
    {
        // Test string sanitization
        $dirtyString = '<script>alert("xss")</script>test';
        $cleaned = Validator::sanitizeInput($dirtyString, 'string');
        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertStringContainsString('test', $cleaned);
        
        // Test URL sanitization
        $url = 'https://example.com/api/v2';
        $cleanUrl = Validator::sanitizeInput($url, 'url');
        $this->assertEquals($url, $cleanUrl);
        
        // Test integer sanitization
        $number = '123abc';
        $cleanNumber = Validator::sanitizeInput($number, 'int');
        $this->assertEquals(123, $cleanNumber);
        
        // Test array sanitization
        $dirtyArray = [
            'clean' => 'value',
            'dirty' => '<script>alert(1)</script>',
            'nested' => ['clean' => 'value', 'dirty' => '<img onerror="alert(1)">']
        ];
        $cleanArray = Validator::sanitizeInput($dirtyArray, 'array');
        $this->assertStringNotContainsString('<script>', $cleanArray['dirty']);
        $this->assertStringNotContainsString('<img', $cleanArray['nested']['dirty']);
    }
    
    public function testSecureTokenGeneration(): void
    {
        $token1 = Security::generateSecureToken(32);
        $token2 = Security::generateSecureToken(32);
        
        $this->assertEquals(32, strlen($token1));
        $this->assertEquals(32, strlen($token2));
        $this->assertNotEquals($token1, $token2);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token1);
    }
    
    public function testSecureComparison(): void
    {
        $string1 = 'secret_value';
        $string2 = 'secret_value';
        $string3 = 'different_value';
        
        $this->assertTrue(Security::secureCompare($string1, $string2));
        $this->assertFalse(Security::secureCompare($string1, $string3));
    }
    
    public function testHeaderSanitization(): void
    {
        $headers = [
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json',
            'X-Malicious-Header' => '<script>alert(1)</script>',
            'User-Agent' => 'WPLiteCore/1.0',
            'Dangerous-Header' => 'should_be_removed'
        ];
        
        $sanitized = Security::sanitizeHeaders($headers);
        
        $this->assertArrayHasKey('authorization', $sanitized);
        $this->assertArrayHasKey('content-type', $sanitized);
        $this->assertArrayHasKey('user-agent', $sanitized);
        $this->assertArrayNotHasKey('x-malicious-header', $sanitized);
        $this->assertArrayNotHasKey('dangerous-header', $sanitized);
    }
    
    public function testCurlSecurityOptions(): void
    {
        $options = Security::getSecureCurlOptions();
        
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertEquals(CURL_SSLVERSION_TLSv1_2, $options[CURLOPT_SSLVERSION]);
        $this->assertEquals(CURLPROTO_HTTP | CURLPROTO_HTTPS, $options[CURLOPT_PROTOCOLS]);
        $this->assertFalse($options[CURLOPT_DNS_USE_GLOBAL_CACHE]);
        $this->assertTrue($options[CURLOPT_FORBID_REUSE]);
    }
}
