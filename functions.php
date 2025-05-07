<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/* -------------------------------------------------------------------------- */
/** JWT GENERATOR
 * This function generates a JWT token
 */
/* -------------------------------------------------------------------------- */
function generate_jwt($options = []) {

    $options = array_merge([
        'key' => HASH_KEY,
        'url' => 'https://api.wirefront.net',
        'expire_date' => 60, // Default to 1 hour (3600 seconds)
        'hash' => 'HS512'
    ], $options);

    // Automatically calculate the expiration timestamp
    $options['expire_date'] = time() + $options['expire_date'];

    try {

        if (!is_array($options)) {
            return [
                'result' => false,
                'message' => 'Payload must be an array'
            ];
        }

        $jwt = JWT::encode(array_diff_key($options, ['key' => '', 'hash' => '']), $options['key'], $options['hash']);
        $token = json_decode(json_encode($jwt), true);

        return [
            'result' => true,
            'token' => $token
        ];

    } catch (Exception $e) {

        return [
            'result' => false,
            'message' => $e->getMessage()
        ];

    }

}


/* -------------------------------------------------------------------------- */
/** CALL HEADER FUNCTION
 * This function includes the header.php file from the project root directory
 * and can be used to include the header in any PHP file within the project.
 */
/* -------------------------------------------------------------------------- */
if (!function_exists('wlc_header')) {
    function wlc_header( $customHeader = null ) {
        // Get the current working directory (project root)
        $projectRoot = getcwd();
        
        // Path to the header.php file in the project root
        if ($customHeader) {
            $headerFile = $projectRoot . '/' . $customHeader;
        } else {
            $headerFile = $projectRoot . '/header.php';
        }
        
        // Check if the file exists and include it if it does
        if (file_exists($headerFile)) {
            include $headerFile;
        }
    }
}


/* -------------------------------------------------------------------------- */
/** CALL FOOTER FUNCTION
 * This function includes the footer.php file from the project root directory
 * and can be used to include the footer in any PHP file within the project.
 */
/* -------------------------------------------------------------------------- */
if (!function_exists('wlc_footer')) {
    function wlc_footer( $customFooter = null ) {
        // Get the current working directory (project root)
        $projectRoot = getcwd();
        
        // Path to the footer.php file in the project root
        if ($customFooter) {
            $footerFile = $projectRoot . '/' . $customFooter;
        } else {
            $footerFile = $projectRoot . '/footer.php';
        }
        
        // Check if the file exists and include it if it does
        if (file_exists($footerFile)) {
            include $footerFile;
        }
    }
}


/* -------------------------------------------------------------------------- */
/** FUNCTION TO GET THE CURRENT URL
 * This function returns the current URL of the page being accessed.
**/
/* -------------------------------------------------------------------------- */
if (!function_exists('wlc_get_url')) {
    function wlc_get_url() {
        // Get the current URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['REQUEST_URI']);

        // Ensure the path ends with a slash
        $path = rtrim($path, '/');

        return $protocol . $host . $path;
    }
}


/* -------------------------------------------------------------------------- */
/** GET GENERAL API DATA
 * 
 * This function retrieves data from a specified URL and decodes it into an array.
 * It can be used to fetch data from APIs or other sources.
 * Parameter Reference: https://developer.wordpress.org/rest-api/reference/posts/#arguments
**/
/* -------------------------------------------------------------------------- */
if (!function_exists('wlc_get_api_data')) {
    function wlc_get_api_data($options = []) {
        $options = array_merge([
            'key' => null,
            'api_url' => '', // Valid URL is required
            'endpoint' => null, // Possible values: posts | pages | users | comments
            'status' => 'publish', // (Optional) Possible values: publish | future | draft | pending | private
            'parameters' => ['per_page' => 10, 'page' => 1], // (Optional array), see WordPress API for available parameters
        ], $options);

        // Extract the URL and token from the options
        $url = $options['api_url'];
        $endpoint = $options['endpoint'];

        // Generate the JWT token
        $token = generate_jwt(['key' => $options['key']]);
        $token = $token['token'];
        
        // Check if key is provided
        if (empty($options['key'])) {
            return [
                'result' => false,
                'message' => 'Key is required'
            ];
        }

        // Check if URL and endpoint are provided
        if (empty($url) && empty($endpoint)) {
            return [
                'result' => false,
                'message' => 'URL or endpoint is required'
            ];
        }

        // Check if the URL is provided
        if (empty($url)) {
            return [
                'result' => false,
                'message' => 'URL is required'
            ];
        }
        // Check if the URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'result' => false,
                'message' => 'Invalid URL'
            ];
        }

        // Combine URL with endpoint if provided
        if (!empty($endpoint)) {
            $url = rtrim($url, '/') . '/' . ltrim($endpoint, '/');
        }

        if (!empty($options['target'])) {
            $url = rtrim($url, '/') . '/' . ltrim($options['target'], '/');
        }

        // Append the ID to the URL if provided
        if (!empty($options['id'])) {
            $url = rtrim($url, '/') . '/' . $options['id'];
        }

        // Append query parameters to the URL if provided
        if (!empty($options['parameters']) && is_array($options['parameters'])) {
            // Check if 'slug' is present in the parameters
            if (isset($options['parameters']['slug'])) {
                // If 'slug' is present, ignore other parameters
                $options['parameters'] = ['slug' => $options['parameters']['slug']];
            } else {
                // Merge default parameters with provided ones
                $options['parameters'] = array_merge(['per_page' => 10, 'page' => 1], $options['parameters']);
            }

            $queryString = http_build_query($options['parameters']);
            if (strpos($url, '?') !== false) {
                $url .= '&' . $queryString;
            } else {
                $url .= '?' . $queryString;
            }
        }

        // Initialize a cURL session
        $ch = curl_init();

        // Set the URL to fetch
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set options to return the response as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Include headers in the output
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Add Authorization Bearer Token if provided
        if ($token) {
            $headers = [
                'Authorization: Bearer ' . $token
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Execute the cURL request and store the response
        $response = curl_exec($ch);

        // Separate the headers and the body
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        // Extracts the headers from the HTTP response using the header size.
        $headers = substr($response, 0, $header_size);
        
        // Extracts the body from the HTTP response using the header size.
        $body = substr($response, $header_size);

        // Parse the headers to extract X-WP-Total and X-WP-TotalPages
        $x_wp_total = null;
        $x_wp_total_pages = null;
        foreach (explode("\r\n", $headers) as $header) {
            if (stripos($header, 'X-WP-Total:') === 0) {
                $x_wp_total = trim(substr($header, strlen('X-WP-Total:')));
            }
            if (stripos($header, 'X-WP-TotalPages:') === 0) {
                $x_wp_total_pages = trim(substr($header, strlen('X-WP-TotalPages:')));
            }
        }

        // Close the cURL session
        curl_close($ch);

        // Decode the JSON response into an associative array
        $items = json_decode($body, true);

        // Store the extracted header values in variables
        $total_posts = $x_wp_total;
        $total_pages = $x_wp_total_pages;

        // If 'slug' is provided, return the first record as a single object
        if ((isset($options['parameters']['slug']) || isset($options['id'])) && is_array($items) && count($items) === 1) {
            $items = $items[0];
        }

        // Add the total posts and pages to the response
        $data['total_posts'] = $total_posts;
        $data['total_pages'] = $total_pages;

        $output = [
            'result' => !empty($items),
            'items' => $items,
            'total_posts' => $total_posts,
            'total_pages' => $total_pages
        ];

        return $output;

    }
}


/* -------------------------------------------------------------------------- */
/** GET COMMENTS FOR A POST
 * This function retrieves comments for a specific post from a specified URL and decodes it into an array.
 **/
/* -------------------------------------------------------------------------- */
function wlc_post_comments($public_options = []) {
    // Ensure $public_options is always an array
    if (!is_array($public_options)) {
        $public_options = [];
    }

    // Declare variables
    $private_options = [];
    $options = [];

    $public_options = array_merge([
        'key' => null,
        'api_url' => null, // Valid URL is required
        'post_id' => null, // ID of the post to fetch comments for
    ], $public_options);

    // Add extra options hidden in the main options
    $private_options = [
        'endpoint' => 'comments'
    ];

    $options = array_merge($public_options, $private_options);

    // Check if key is provided
    if (empty($options['key'])) {
        return [
            'result' => false,
            'message' => 'Key is required'
        ];
    }

    // Check if URL and endpoint are provided
    if (empty($options['api_url']) && empty($options['endpoint'])) {
        return [
            'result' => false,
            'message' => 'URL or endpoint is required'
        ];
    }

    // Check if post_id is provided
    if (empty($options['post_id'])) {
        return [
            'result' => false,
            'message' => 'Post ID is required'
        ];
    }

    // Call function to get the data from the API
    $comments = wlc_get_api_data([
        'key' => $options['key'],
        'api_url' => $options['api_url'],
        'endpoint' => $options['endpoint'],
        'parameters' => ['post' => $options['post_id']],
    ]);

    // Validate the response
    if (empty($comments) || !is_array($comments) || empty($comments['items'])) {
        return [
            'result' => false,
            'message' => 'No comments found or an error occurred'
        ];
    }

    // Format the comments data
    $comments_data = array_map(function ($comment) {
        return [
            'id' => $comment['id'] ?? null,
            'author_name' => $comment['author_name'] ?? null,
            'author_avatar' => $comment['author_avatar_urls']['96'] ?? null,
            'content' => $comment['content']['rendered'] ?? null,
            'date' => $comment['date'] ?? null,
            'post' => $comment['post'] ?? null,
            'status' => $comment['status'] ?? null
        ];
    }, $comments['items']);

    return [
        'result' => true,
        'items' => $comments_data
    ];
}


/* -------------------------------------------------------------------------- */
/** GET FEATURED IMAGE FOR A POST
 * This function retrieves the featured image for a specific post from a specified URL.
 * It includes an optional parameter to specify the image size (default: medium).
 **/
if (!function_exists('wlc_featured_image')) {
    function wlc_featured_image($public_options = []) {

        // Ensure $public_options is always an array
        if (!is_array($public_options)) {
            $public_options = [];
        }

        // Declare variables
        $private_options = [];
        $options = [];

        $public_options = array_merge([
            'key' => null,
            'api_url' => null, // Valid URL is required
            'attachment_id' => 0, // ID of the post to fetch the featured image for
            'size' => 'medium', // Default image size
            'type' => 'posts', // Possible values: posts | pages | other custom post types
        ], $public_options);

        // Add extra options hidden in the main options
        $private_options = [
            'endpoint' => 'media'
        ];

        // Merge public and private options
        $options = array_merge($public_options, $private_options);

        // Generate the JWT token
        $token = generate_jwt(['key' => $options['key']]);
        $token = $token['token'];

        // Check if key is provided
        if (empty($options['key'])) {
            return [
                'result' => false,
                'message' => 'Key is required'
            ];
        }

        // Check if URL and endpoint are provided
        if (empty($options['api_url']) && empty($options['endpoint'])) {
            return [
                'result' => false,
                'message' => 'API URL or endpoint is required'
            ];
        }

        // Check if attachment_id is provided
        if (empty($options['attachment_id'])) {
            return [
                'result' => false,
                'message' => 'Attachment ID is required'
            ];
        }

        // Set URL and endpoint
        $url = $options['api_url'] . 'media/' . $options['attachment_id'];

        // Initialize a cURL session
        $ch = curl_init();

        // Set the URL to fetch
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set options to return the response as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Add Authorization Bearer Token if provided
        if ($token) {
            $headers = [
                'Authorization: Bearer ' . $token
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Execute the cURL request and store the response
        $response = curl_exec($ch);

        // Close the cURL session
        curl_close($ch);

        // Decode the JSON response into an associative array
        $data = json_decode($response, true);


        if (empty($data['media_details'])) {
            return [
            'result' => false,
            'message' => 'Media with ID ' . $options['attachment_id'] . ' not found'
            ];
        }

        $output = [
            'result' => true,
            'url' => $data['media_details']['sizes'][$options['size']]['source_url'] ?? $data['media_details']['sizes']['medium']['source_url'],
        ];

        return $output;
        
    }
}


/* -------------------------------------------------------------------------- */
/** GET CATEGORIES
 * This function retrieves categories from a specified URL and decodes it into an array.
 **/
if( !function_exists('wlc_get_categories') ) {
    
    function wlc_get_category($options = []) {
        $options = array_merge([
            'key' => null,
            'api_url' => null, // Valid URL is required
            'cat_id' => null, // ID of the category to fetch
        ], $options);

        // Check if key is provided
        if (empty($options['key'])) {
            return [
                'result' => false,
                'message' => 'Key is required'
            ];
        }

        // Check if URL and endpoint are provided
        if (empty($options['api_url']) && empty($options['endpoint'])) {
            return [
                'result' => false,
                'message' => 'API URL or endpoint is required'
            ];
        }

        // Call function to get the data from the API
        $categories = wlc_get_api_data([
            'key' => $options['key'],
            'api_url' => $options['api_url'],
            'endpoint' => 'categories',
            'parameters' => ['id' => $options['cat_id']],
        ]);

        // Validate the response
        if (empty($categories) || !is_array($categories) || empty($categories['items'])) {
            return [
                'result' => false,
                'message' => 'No categories found or an error occurred'
            ];
        }

        // Loop through the categories dynamically
        $items = [];
        if (!empty($categories['items']) && is_array($categories['items'])) {
            foreach ($categories['items'] as $category) {
                $items[] = [
                    'id' => $category['id'] ?? null,
                    'name' => $category['name'] ?? null,
                    'slug' => $category['slug'] ?? null,
                    'description' => $category['description'] ?? null,
                    'count' => $category['count'] ?? null,
                    'parent' => $category['parent'] ?? null,
                    'link' => $category['link'] ?? null,
                    'taxonomy' => $category['taxonomy'] ?? null
                ];
            }
        }

        return [
            'result' => true,
            'items' => $items
        ];
    }

}


/* -------------------------------------------------------------------------- */
/** GET SINGLE POST/PAGE API DATA
 * This function retrieves data from a specified URL and decodes it into an array.
 * It can be used to fetch data from APIs or other sources.
 **/
/* -------------------------------------------------------------------------- */
if (!function_exists('wlc_single_post')) {
    function wlc_single_post($options = []) {

        $options = array_merge([
            'key' => null,
            'api_url' => null, // Valid URL is required
            'type' => 'posts', // Possible values: posts | pages
            'slug' => null, // Possible values: post slug
            'media_size' => 'medium', // (Optional) Default image size
        ], $options);

        // Check if key is provided
        if (empty($options['key'])) {
            return [
                'result' => false,
                'message' => 'Key is required'
            ];
        }

        // Check if URL and endpoint are provided
        if (empty($options['api_url']) && empty($options['endpoint'])) {
            return [
                'result' => false,
                'message' => 'API URL or endpoint is required'
            ];
        }

        // Call function to get the data from the API
        $posts = wlc_get_api_data([
            'key' => $options['key'],
            'api_url' => $options['api_url'],
            'endpoint' => $options['type'],
            'id' => isset($options['id']) && !is_null($options['id']) ? $options['id'] : null,
            'parameters' => ['slug' => $options['slug']],
        ]);

        // Check if the posts data is retrieved successfully
        if (empty($posts) || !is_array($posts) || (isset($posts['result']) && $posts['result'] == false) || (isset($posts['total_posts']) && $posts['total_posts'] === null)) {
            if ($options['type'] === 'pages') {
                return [
                    'result' => false,
                    'message' => 'Page not found for the given ID or slug',
                    'data' => $posts
                ];
            } else {
                return [
                    'result' => false,
                    'message' => ucfirst($options['type']) . ' not found for the given ID or slug',
                    'data' => $posts
                ];
            }
        }

        // Validate if 'items' exists and is an array
        if (!isset($posts['items']) || !is_array($posts['items'])) {
            if ($options['type'] === 'pages') {
                return [
                    'result' => false,
                    'message' => 'Page not found for the given ID or slug',
                    'data' => $posts
                ];
            } else {
                return [
                    'result' => false,
                    'message' => ucfirst($options['type']) . ' not found for the given ID or slug',
                    'data' => $posts
                ];
            }
        }

        // Post category using wlc_get_category()
        if (isset($posts['items']['categories']) && is_array($posts['items']['categories'])) {
            foreach ($posts['items']['categories'] as $cat_id) {
                $category = wlc_get_category([
                    'key' => $options['key'],
                    'api_url' => $options['api_url'],
                    'cat_id' => $cat_id,
                ]);
                if ($category['result'] == true) {
                    $posts['items']['categories'][$cat_id] = $category['items'];
                }
            }
        }

        // Safely access keys from $posts['items']
        $post_data = [
            'title' => $posts['items']['title']['rendered'] ?? null,
            'content' => $posts['items']['content']['rendered'] ?? null,
            'excerpt' => $posts['items']['excerpt']['rendered'] ?? null,
            'date' => $posts['items']['date'] ?? null,
            'author' => $posts['items']['author'] ?? null,
            'featured_media' => $posts['items']['featured_media'] ?? null,
            'status' => $posts['items']['status'] ?? null,
            'id' => $posts['items']['id'] ?? null,
            'categories' => $category['items'] ?? [],
            'tags' => $posts['items']['tags'] ?? [],
            'comments' => wlc_post_comments([
                'key' => $options['key'],
                'api_url' => $options['api_url'],
                'post_id' => $posts['items']['id'] ?? null,
            ]),
            'featured_image' => wlc_featured_image([
                'key' => $options['key'],
                'api_url' => $options['api_url'],
                'attachment_id' => $posts['items']['featured_media'] ?? null,
                'size' => $options['media_size']
            ])
        ];

        return $post_data;

    }
}



/* -------------------------------------------------------------------------- */
/** CREATE REQUIREMENTS
 * This function creates requirements for the project, including:
 * - .htaccess file
 * - routes.php file
 **/
/* -------------------------------------------------------------------------- */
function create_requirements() {
    // Define the file paths for the .htaccess, routes.php, and 404.php files in the current working directory
    $htaccessPath = getcwd() . '/.htaccess';
    $routesPath = getcwd() . '/routes.php';
    $notFoundPath = getcwd() . '/404.php';

    // Define the contents of the .htaccess file
    $htaccessContent = <<<HTACCESS
RewriteEngine On
RewriteCond %{REQUEST_URI}  !(\.png|\.jpg|\.webp|\.gif|\.jpeg|\.zip|\.css|\.svg|\.js|\.pdf)$
RewriteRule (.*) routes.php [QSA,L]
HTACCESS;

    // Define the contents of the routes.php file
    $routesContent = <<<PHP
<?php

// Require the autoload file for the project
require_once __DIR__ . '/vendor/autoload.php';

// Static GET
get('/', 'views/index.php');

// Dynamic GET. Example with 1 variable
// The \$id will be available in user.php
get('/user/\$id', 'views/user');

// Dynamic GET. Example with 2 variables
// The \$name will be available in full_name.php
// The \$last_name will be available in full_name.php
// In the browser point to: localhost/user/X/Y
get('/user/\$name/\$last_name', 'views/full_name.php');

// Dynamic GET. Example with 2 variables with static
// In the URL -> http://localhost/product/shoes/color/blue
// The \$type will be available in product.php
// The \$color will be available in product.php
get('/product/\$type/color/\$color', 'product.php');

// A route with a callback
get('/callback', function(){
  echo 'Callback executed';
});

// A route with a callback passing a variable
// To run this route, in the browser type:
// http://localhost/user/A
get('/callback/\$name', function(\$name){
  echo "Callback executed. The name is \$name";
});

// Route where the query string happens right after a forward slash
get('/product', '');

// A route with a callback passing 2 variables
// To run this route, in the browser type:
// http://localhost/callback/A/B
get('/callback/\$name/\$last_name', function(\$name, \$last_name){
  echo "Callback executed. The full name is \$name \$last_name";
});

// Route that will use POST data
post('/user', '/api/save_user');

// For GET or POST
// The 404.php which is inside the views folder will be called
// The 404.php has access to \$_GET and \$_POST
any('/404','views/404.php');
PHP;

    // Define the contents of the 404.php file
    $notFoundContent = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>
</body>
</html>
HTML;

    // Attempt to create or overwrite the files
    try {
        if (file_put_contents($htaccessPath, $htaccessContent) === false) {
            throw new Exception('Failed to create .htaccess file at ' . $htaccessPath);
        }

        if (file_put_contents($routesPath, $routesContent) === false) {
            throw new Exception('Failed to create routes.php file at ' . $routesPath);
        }

        if (file_put_contents($notFoundPath, $notFoundContent) === false) {
            throw new Exception('Failed to create 404.php file at ' . $notFoundPath);
        }

        return [
            'result' => true,
            'message' => '.htaccess, routes.php, and 404.php files created successfully at ' . getcwd()
        ];
    } catch (Exception $e) {
        return [
            'result' => false,
            'message' => $e->getMessage()
        ];
    }
}


function test(){

    echo "Test function called!";

}