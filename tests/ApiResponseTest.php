<?php

namespace Tests;

use WPLite\Api\ApiResponse;

/**
 * Tests for ApiResponse
 */
class ApiResponseTest extends BaseTestCase
{
    public function testSuccessfulResponse(): void
    {
        $items = [$this->createSamplePost(1), $this->createSamplePost(2)];
        $response = ApiResponse::success($items, '10', '3');
        
        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isFailure());
        $this->assertTrue($response->hasItems());
        $this->assertEquals(2, $response->getItemCount());
        $this->assertEquals($items, $response->getItems());
        $this->assertEquals('10', $response->getTotalPosts());
        $this->assertEquals('3', $response->getTotalPages());
        $this->assertTrue($response->isPaginated());
    }

    public function testFailedResponse(): void
    {
        $response = ApiResponse::failure('Test error message');
        
        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isFailure());
        $this->assertFalse($response->hasItems());
        $this->assertEquals(0, $response->getItemCount());
        $this->assertNull($response->getItems());
        $this->assertFalse($response->isPaginated());
        
        $meta = $response->getMeta();
        $this->assertArrayHasKey('error_message', $meta);
        $this->assertEquals('Test error message', $meta['error_message']);
    }

    public function testEmptyResponse(): void
    {
        $response = new ApiResponse(true, []);
        
        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->hasItems());
        $this->assertEquals(0, $response->getItemCount());
        $this->assertEquals([], $response->getItems());
    }

    public function testSingleItemResponse(): void
    {
        $item = $this->createSamplePost(1);
        $response = new ApiResponse(true, $item);
        
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->hasItems());
        $this->assertEquals(1, $response->getItemCount());
        $this->assertEquals($item, $response->getItems());
        $this->assertEquals($item, $response->getFirstItem());
    }

    public function testArrayItemResponse(): void
    {
        $items = [$this->createSamplePost(1), $this->createSamplePost(2)];
        $response = new ApiResponse(true, $items);
        
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->hasItems());
        $this->assertEquals(2, $response->getItemCount());
        $this->assertEquals($items, $response->getItems());
        $this->assertEquals($items[0], $response->getFirstItem());
    }

    public function testMetaData(): void
    {
        $response = new ApiResponse(true, [], null, null, ['custom' => 'value']);
        
        $meta = $response->getMeta();
        $this->assertArrayHasKey('custom', $meta);
        $this->assertEquals('value', $meta['custom']);
        
        $response->addMeta('another', 'test');
        $meta = $response->getMeta();
        $this->assertArrayHasKey('another', $meta);
        $this->assertEquals('test', $meta['another']);
    }

    public function testToArray(): void
    {
        $items = [$this->createSamplePost(1)];
        $meta = ['test' => 'value'];
        $response = new ApiResponse(true, $items, '5', '2', $meta);
        
        $array = $response->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('result', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('total_posts', $array);
        $this->assertArrayHasKey('total_pages', $array);
        $this->assertArrayHasKey('meta', $array);
        
        $this->assertTrue($array['result']);
        $this->assertEquals($items, $array['items']);
        $this->assertEquals('5', $array['total_posts']);
        $this->assertEquals('2', $array['total_pages']);
        $this->assertEquals($meta, $array['meta']);
    }

    public function testToJson(): void
    {
        $items = [$this->createSamplePost(1)];
        $response = new ApiResponse(true, $items, '1', '1');
        
        $json = $response->toJson();
        $this->assertIsString($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('result', $decoded);
        $this->assertTrue($decoded['result']);
    }

    public function testPaginationDetection(): void
    {
        // With pagination info
        $paginatedResponse = new ApiResponse(true, [], '10', '3');
        $this->assertTrue($paginatedResponse->isPaginated());
        
        // Without pagination info
        $nonPaginatedResponse = new ApiResponse(true, []);
        $this->assertFalse($nonPaginatedResponse->isPaginated());
        
        // Partial pagination info
        $partialResponse = new ApiResponse(true, [], '10', null);
        $this->assertFalse($partialResponse->isPaginated());
    }

    public function testItemCountWithDifferentTypes(): void
    {
        // Array items
        $arrayResponse = new ApiResponse(true, [1, 2, 3]);
        $this->assertEquals(3, $arrayResponse->getItemCount());
        
        // Single item
        $singleResponse = new ApiResponse(true, 'single item');
        $this->assertEquals(1, $singleResponse->getItemCount());
        
        // Empty items
        $emptyResponse = new ApiResponse(true, []);
        $this->assertEquals(0, $emptyResponse->getItemCount());
        
        // Null items
        $nullResponse = new ApiResponse(true, null);
        $this->assertEquals(0, $nullResponse->getItemCount());
    }

    public function testFirstItemWithDifferentTypes(): void
    {
        // Array items
        $items = ['first', 'second', 'third'];
        $arrayResponse = new ApiResponse(true, $items);
        $this->assertEquals('first', $arrayResponse->getFirstItem());
        
        // Single item
        $singleResponse = new ApiResponse(true, 'single');
        $this->assertEquals('single', $singleResponse->getFirstItem());
        
        // Empty array
        $emptyResponse = new ApiResponse(true, []);
        $this->assertEquals([], $emptyResponse->getFirstItem());
        
        // Null items
        $nullResponse = new ApiResponse(true, null);
        $this->assertNull($nullResponse->getFirstItem());
    }
}
