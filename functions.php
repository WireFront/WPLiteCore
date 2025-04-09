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
            'url' => '', // Valid URL is required
            'endpoint' => null, // Posible values: posts | pages | users | comments
            'id' => null, // (Optional)Posible values: ID
            'status' => 'publish', // (Optional)Posible values: publish | future | draft | pending | private
            'parameters' => ['per_page' => 10, 'page' => 1], // (Optional array), see WordPress API for available parameters
        ], $options);

        // Extract the URL and token from the options
        $url = $options['url'];
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
                true => false,
                'message' => 'Invalid URL'
            ];
        }

        // Combined URL with endpoint if provided
        if (!empty($endpoint)) {
            $url = rtrim($url, '/') . '/' . ltrim($endpoint, '/');
        }

        if (!empty($options['target'])) {
            $url = rtrim($url, '/') . '/' . ltrim($options['target'], '/');
        }

        // Append query parameters to the URL if provided
        if (!empty($options['parameters']) && is_array($options['parameters'])) {
            // Merge default parameters with provided ones
            $options['parameters'] = array_merge(['per_page' => 10, 'page' => 1], $options['parameters']);
            
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
        return json_decode($response, true);

    }
}


/* -------------------------------------------------------------------------- */
/** GET COMMENTS FOR A POST
 * This function retrieves comments for a specific post from a specified URL and decodes it into an array.
 **/
/* -------------------------------------------------------------------------- */
if (!function_exists('wlc_post_comments')) {
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
            'url' => null, // Valid URL is required
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
        if (empty($options['url']) && empty($options['endpoint'])) {
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
            'url' => $options['url'],
            'endpoint' => $options['endpoint'],
            'parameters' => ['post' => $options['post_id']],
        ]);

        // Check if comments are retrieved successfully
        if (empty($comments) || !is_array($comments)) {
            return [
                'result' => false,
                'message' => 'No comments found or an error occurred'
            ];
        }

        // Format the comments data
        $comments_data = array_map(function ($comment) {
            return [
                'id' => $comment['id'],
                'author_name' => $comment['author_name'],
                'author_avatar' => $comment['author_avatar_urls']['96'],
                'content' => $comment['content']['rendered'],
                'date' => $comment['date'],
                'post' => $comment['post'],
                'status' => $comment['status']
            ];
        }, $comments);

        return [
            'result' => true,
            'items' => $comments_data
        ];
    }
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
            'url' => null, // Valid URL is required
            'post_id' => null, // ID of the post to fetch the featured image for
            'size' => 'medium', // Default image size
        ], $public_options);

        // Add extra options hidden in the main options
        $private_options = [
            'endpoint' => 'media'
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
        if (empty($options['url']) && empty($options['endpoint'])) {
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

        // Call function to get the post data from the API
        $post_data = wlc_get_api_data([
            'key' => $options['key'],
            'url' => $options['url'],
            'endpoint' => 'posts',
            'parameters' => ['include' => $options['post_id']],
        ]);

        // Check if the post data is retrieved successfully
        if (empty($post_data) || !is_array($post_data)) {
            return [
                'result' => false,
                'message' => 'Post not found or an error occurred'
            ];
        }

        // Get the featured media ID from the post data
        $featured_media_id = $post_data[0]['featured_media'];

        // Call function to get the featured image data from the API
        $media_data = wlc_get_api_data([
            'key' => $options['key'],
            'url' => $options['url'],
            'endpoint' => $options['endpoint'],
            'parameters' => ['include' => $featured_media_id],
        ]);

        // Check if the media data is retrieved successfully
        if (empty($media_data) || !is_array($media_data)) {
            return [
                'result' => false,
                'message' => 'Featured image not found or an error occurred'
            ];
        }

        // Get the URL of the specified image size
        $image_url = $media_data[0]['media_details']['sizes'][$options['size']]['source_url'] ?? null;

        // Check if the image URL is available
        if (empty($image_url)) {
            return [
                'result' => false,
                'message' => 'Image size not found'
            ];
        }

        return [
            'result' => true,
            'image_url' => $image_url
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
    function wlc_single_post($public_options = []) {

        // Ensure $public_options is always an array
        if (!is_array($public_options)) {
            $public_options = [];
        }

        // Declare variables
        $private_options = [];
        $options = [];

        $public_options = array_merge([
            'key' => null,
            'url' => null, // Valid URL is required
            'slug' => null, // Possible values: post slug
            'id' => null, // (Optional) Possible values: ID
            'media_size' => 'medium', // (Optional) Default image size
        ], $public_options);
        
        // Add extra options hidden in the main options
        $private_options = [
            'endpoint' => 'posts'
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
        if (empty($options['url']) && empty($options['endpoint'])) {
            return [
                'result' => false,
                'message' => 'URL or endpoint is required'
            ];
        }

        // Call function to get the data from the API
        $posts = wlc_get_api_data([
            'key' => $options['key'],
            'url' => $options['url'],
            'endpoint' => $options['endpoint'],
            'parameters' => ['slug' => $options['slug']],
        ]);

        $comments = wlc_post_comments([
            'key' => $options['key'],
            'url' => $options['url'],
            'post_id' => $posts[0]['id'],
        ]);

        $featured_image = wlc_featured_image([
            'key' => $options['key'],
            'url' => $options['url'],
            'post_id' => $posts[0]['id'],
            'size' => $options['media_size'],
        ]);

        $post_data = [
            'title' => $posts[0]['title']['rendered'],
            'content' => $posts[0]['content']['rendered'],
            'excerpt' => $posts[0]['excerpt']['rendered'],
            'date' => $posts[0]['date'],
            'author' => $posts[0]['author'],
            'featured_media' => $posts[0]['featured_media'],
            'status' => $posts[0]['status'],
            'id' => $posts[0]['id'],
            'categories' => $posts[0]['categories'],
            'tags' => $posts[0]['tags'],
            'comments' => $comments,
            'featured_image' => $featured_image
        ];

        return $post_data;

    }
}




