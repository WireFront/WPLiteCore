# WPLiteCore

## Function: `wlc_get_api_data`

This function retrieves data from a specified API URL and decodes it into an associative array. It is useful for fetching data from APIs or other external sources.

### Options

- **`key`** (string, optional):  
  The secret key used to generate the JWT token for authentication. If not provided, the function will attempt to proceed without a token.

- **`url`** (string, required):  
  The base URL of the API to fetch data from. This must be a valid URL.

- **`endpoint`** (string, optional):  
  The specific API endpoint to append to the base URL.  
  Example: `'posts'`, `'pages'`, `'users'`, etc.

- **`target`** (string, optional):  
  A specific identifier to append to the endpoint, such as an ID or slug.  
  Example: `'123'`, `'my-post-slug'`, etc.

### Returns

- An **associative array** containing the API response data if the request is successful.
- An **error message** if the request fails or if required parameters are missing.

### Example Usage

```php
$data = wlc_get_api_data([
    'key' => 'your-secret-key',
    'url' => 'https://example.com/api',
    'endpoint' => 'posts',
    'target' => '123'
]);

if ($data['result']) {
    // Process the data
    print_r($data);
} else {
    // Handle the error
    echo $data['message'];
}
```

