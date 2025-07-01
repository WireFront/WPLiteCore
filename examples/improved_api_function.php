<?php

// Example: Refactored wlc_get_api_data function with proper error handling

use WPLite\Core\Validator;
use WPLite\Core\ErrorHandler;
use WPLite\Exceptions\ApiException;
use WPLite\Exceptions\ValidationException;
use WPLite\Exceptions\ConfigException;

/**
 * IMPROVED VERSION: Get API data with proper error handling
 * 
 * @param array $options Configuration options
 * @return array API response data
 * @throws ValidationException When input validation fails
 * @throws ConfigException When configuration is invalid
 * @throws ApiException When API request fails
 */
function wlc_get_api_data_improved(array $options = []): array
{
    // Initialize error handler
    $errorHandler = new ErrorHandler(WLC_DEBUG_MODE ?? false);
    
    try {
        // Set defaults
        $options = array_merge([
            'key' => null,
            'api_url' => '',
            'endpoint' => null,
            'status' => 'publish',
            'parameters' => ['per_page' => 10, 'page' => 1],
        ], $options);

        // Validate input
        $validator = new Validator();
        $validator
            ->required($options['key'], 'key')
            ->required($options['api_url'], 'api_url')
            ->url($options['api_url'], 'api_url')
            ->array($options['parameters'], 'parameters');

        // Check validation
        $validator->validate();

        // Sanitize inputs
        $options['api_url'] = Validator::sanitizeUrl($options['api_url']);
        $options['parameters'] = Validator::sanitizeArray($options['parameters']);

        // Generate JWT token
        $tokenResult = generate_jwt(['key' => $options['key']]);
        if (!$tokenResult['result']) {
            throw new ApiException('Failed to generate JWT token: ' . $tokenResult['message']);
        }

        // Build URL
        $url = buildApiUrl($options);

        // Make API request with error handling
        $response = makeSecureApiRequest($url, $tokenResult['token']);

        return formatApiResponse($response);

    } catch (ValidationException $e) {
        // Re-throw validation exceptions
        throw $e;
    } catch (ApiException $e) {
        // Re-throw API exceptions
        throw $e;
    } catch (\Throwable $e) {
        // Convert any other exception to ApiException
        throw new ApiException(
            'Unexpected error occurred: ' . $e->getMessage(),
            500,
            500,
            [],
            ['original_error' => $e->getMessage()],
            $e
        );
    }
}

/**
 * Build API URL with proper validation
 */
function buildApiUrl(array $options): string
{
    $url = rtrim($options['api_url'], '/');
    
    if (!empty($options['endpoint'])) {
        $url .= '/' . ltrim($options['endpoint'], '/');
    }
    
    if (!empty($options['target'])) {
        $url .= '/' . ltrim($options['target'], '/');
    }
    
    if (!empty($options['id'])) {
        $url .= '/' . $options['id'];
    }
    
    // Add query parameters
    if (!empty($options['parameters']) && is_array($options['parameters'])) {
        if (isset($options['parameters']['slug'])) {
            $options['parameters'] = ['slug' => $options['parameters']['slug']];
        } else {
            $options['parameters'] = array_merge(['per_page' => 10, 'page' => 1], $options['parameters']);
        }
        
        $queryString = http_build_query($options['parameters']);
        if (strpos($url, '?') !== false) {
            $url .= '&' . $queryString;
        } else {
            $url .= '?' . $queryString;
        }
    }
    
    return $url;
}

/**
 * Make secure API request with proper error handling
 */
function makeSecureApiRequest(string $url, string $token): array
{
    $ch = curl_init();
    
    // Set cURL options with security in mind
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'User-Agent: WPLiteCore/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors
    if ($response === false || !empty($error)) {
        throw new ApiException('cURL request failed: ' . $error, 0, 500, [], ['url' => $url]);
    }
    
    // Check HTTP status code
    if ($httpCode >= 400) {
        $body = substr($response, $headerSize);
        throw new ApiException(
            "API request failed with HTTP {$httpCode}",
            0,
            $httpCode,
            [],
            ['url' => $url, 'response_body' => $body]
        );
    }
    
    return [
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize),
        'http_code' => $httpCode
    ];
}

/**
 * Format API response with proper error handling
 */
function formatApiResponse(array $response): array
{
    // Parse headers for WordPress-specific data
    $totalPosts = null;
    $totalPages = null;
    
    foreach (explode("\r\n", $response['headers']) as $header) {
        if (stripos($header, 'X-WP-Total:') === 0) {
            $totalPosts = trim(substr($header, strlen('X-WP-Total:')));
        }
        if (stripos($header, 'X-WP-TotalPages:') === 0) {
            $totalPages = trim(substr($header, strlen('X-WP-TotalPages:')));
        }
    }
    
    // Decode JSON response
    $items = json_decode($response['body'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
    }
    
    // Handle single item responses (slug or ID lookup)
    if (is_array($items) && count($items) === 1) {
        $items = $items[0];
    }
    
    return [
        'result' => !empty($items),
        'items' => $items,
        'total_posts' => $totalPosts,
        'total_pages' => $totalPages
    ];
}

// Example usage with error handling:
/*
try {
    $data = wlc_get_api_data_improved([
        'key' => 'your-secret-key',
        'api_url' => 'https://example.com/wp-json/wp/v2/',
        'endpoint' => 'posts'
    ]);
    
    // Handle successful response
    echo json_encode($data);
    
} catch (ValidationException $e) {
    // Handle validation errors
    http_response_code(422);
    echo json_encode($e->toArray());
    
} catch (ApiException $e) {
    // Handle API errors
    http_response_code($e->getHttpStatusCode());
    echo json_encode($e->toArray());
    
} catch (WPLiteException $e) {
    // Handle other framework errors
    http_response_code(500);
    echo json_encode($e->toArray());
}
*/
