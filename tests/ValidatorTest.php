<?php

namespace Tests;

use WPLite\Core\Validator;
use WPLite\Exceptions\ValidationException;

/**
 * Tests for Validator
 */
class ValidatorTest extends BaseTestCase
{
    public function testRequiredValidation(): void
    {
        $validator = new Validator();
        
        // Valid required field
        $validator->required('value', 'test_field');
        $this->assertTrue($validator->passes());
        
        // Invalid required field - empty string
        $validator = new Validator();
        $validator->required('', 'test_field');
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('test_field', $errors);
        $this->assertContains('The test_field field is required', $errors['test_field']);
    }

    public function testRequiredValidationWithZero(): void
    {
        $validator = new Validator();
        
        // Zero should be considered valid
        $validator->required(0, 'test_field');
        $this->assertTrue($validator->passes());
        
        // String zero should be considered valid
        $validator = new Validator();
        $validator->required('0', 'test_field');
        $this->assertTrue($validator->passes());
    }

    public function testUrlValidation(): void
    {
        $validator = new Validator();
        
        // Valid URL
        $validator->url('https://example.com', 'url_field');
        $this->assertTrue($validator->passes());
        
        // Invalid URL
        $validator = new Validator();
        $validator->url('not-a-url', 'url_field');
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('url_field', $errors);
        $this->assertContains('The url_field must be a valid URL', $errors['url_field']);
        
        // Empty URL should pass (not required)
        $validator = new Validator();
        $validator->url('', 'url_field');
        $this->assertTrue($validator->passes());
    }

    public function testLengthValidation(): void
    {
        $validator = new Validator();
        
        // Valid length
        $validator->length('hello', 'text_field', 3, 10);
        $this->assertTrue($validator->passes());
        
        // Too short
        $validator = new Validator();
        $validator->length('hi', 'text_field', 3, 10);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('text_field', $errors);
        $this->assertContains('The text_field must be at least 3 characters', $errors['text_field']);
        
        // Too long
        $validator = new Validator();
        $validator->length('this is a very long string', 'text_field', 3, 10);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('text_field', $errors);
        $this->assertContains('The text_field must not exceed 10 characters', $errors['text_field']);
    }

    public function testIntegerValidation(): void
    {
        $validator = new Validator();
        
        // Valid integer
        $validator->integer(123, 'number_field');
        $this->assertTrue($validator->passes());
        
        // Valid integer string
        $validator = new Validator();
        $validator->integer('456', 'number_field');
        $this->assertTrue($validator->passes());
        
        // Invalid integer
        $validator = new Validator();
        $validator->integer('not-a-number', 'number_field');
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('number_field', $errors);
        $this->assertContains('The number_field must be an integer', $errors['number_field']);
    }

    public function testArrayValidation(): void
    {
        $validator = new Validator();
        
        // Valid array
        $validator->array([1, 2, 3], 'array_field');
        $this->assertTrue($validator->passes());
        
        // Invalid array
        $validator = new Validator();
        $validator->array('not-an-array', 'array_field');
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('array_field', $errors);
        $this->assertContains('The array_field must be an array', $errors['array_field']);
    }

    public function testMultipleValidationRules(): void
    {
        $validator = new Validator();
        
        $validator
            ->required('test', 'field1')
            ->url('https://example.com', 'field2')
            ->length('hello', 'field3', 3, 10)
            ->integer(123, 'field4')
            ->array([1, 2], 'field5');
        
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors());
    }

    public function testMultipleValidationErrors(): void
    {
        $validator = new Validator();
        
        $validator
            ->required('', 'field1')  // Should fail
            ->url('invalid-url', 'field2')  // Should fail
            ->length('hi', 'field3', 5, 10)  // Should fail (too short)
            ->integer('not-number', 'field4')  // Should fail
            ->array('not-array', 'field5');  // Should fail
        
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertCount(5, $errors);
        $this->assertArrayHasKey('field1', $errors);
        $this->assertArrayHasKey('field2', $errors);
        $this->assertArrayHasKey('field3', $errors);
        $this->assertArrayHasKey('field4', $errors);
        $this->assertArrayHasKey('field5', $errors);
    }

    public function testValidateMethod(): void
    {
        $validator = new Validator();
        
        // Should not throw exception when validation passes
        $validator->required('value', 'field1');
        $validator->validate(); // Should not throw
        
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function testValidateMethodThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        
        $validator = new Validator();
        $validator->required('', 'field1'); // Will fail
        $validator->validate(); // Should throw ValidationException
    }

    public function testSanitizeString(): void
    {
        $input = '<script>alert("xss")</script>Hello World!  ';
        $sanitized = Validator::sanitizeString($input);
        
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello World!', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
    }

    public function testSanitizeUrl(): void
    {
        $input = 'https://example.com/path?param=value&other=<script>';
        $sanitized = Validator::sanitizeUrl($input);
        
        $this->assertStringContainsString('https://example.com', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
    }

    public function testSanitizeInt(): void
    {
        $this->assertEquals(123, Validator::sanitizeInt('123'));
        $this->assertEquals(123, Validator::sanitizeInt('123abc'));
        $this->assertEquals(0, Validator::sanitizeInt('abc'));
        $this->assertEquals(-123, Validator::sanitizeInt('-123'));
    }

    public function testSanitizeArray(): void
    {
        $input = [
            'string' => '<script>alert("xss")</script>',
            'number' => 123,
            'nested' => [
                'inner' => '<img src=x onerror=alert(1)>'
            ]
        ];
        
        $sanitized = Validator::sanitizeArray($input);
        
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $sanitized['string']);
        $this->assertEquals(123, $sanitized['number']);
        $this->assertEquals('&lt;img src=x onerror=alert(1)&gt;', $sanitized['nested']['inner']);
    }

    public function testFluentInterface(): void
    {
        $validator = new Validator();
        
        $result = $validator
            ->required('value', 'field1')
            ->url('https://example.com', 'field2')
            ->length('test', 'field3', 1, 10);
        
        $this->assertInstanceOf(Validator::class, $result);
        $this->assertTrue($validator->passes());
    }
}
