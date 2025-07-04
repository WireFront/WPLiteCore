<?php

// Check if wlc_config.php exists
if (!file_exists('wlc_config.php')) {
    die('Please run create_requirements() function to create the necessary files and folders to effectively use this library.');
}else{
    // Include wlc_config.php
    include_once 'wlc_config.php';
}

// Only include autoloader if WPLite classes are not already available
if (!class_exists('WPLite\WPLiteCore')) {
    // Try to find the appropriate autoloader
    $possibleAutoloaders = [
        __DIR__ . '/vendor/autoload.php',                    // Package standalone
        __DIR__ . '/../../../../autoload.php',               // Package installed via Composer
        __DIR__ . '/../../../../../autoload.php',            // Different nesting level
        __DIR__ . '/../vendor/autoload.php',                 // One level up
        getcwd() . '/vendor/autoload.php',                   // Main project autoloader
    ];
    
    $autoloaderFound = false;
    foreach ($possibleAutoloaders as $autoloader) {
        if (file_exists($autoloader)) {
            require_once $autoloader;
            $autoloaderFound = true;
            break;
        }
    }
    
    // If no autoloader found and classes still not available, show helpful error
    if (!$autoloaderFound || !class_exists('WPLite\WPLiteCore')) {
        throw new Exception(
            'WPLiteCore autoloader not found. Please ensure Composer autoload is properly configured. ' .
            'Expected WPLite\WPLiteCore class to be available.'
        );
    }
}

// Set the subfolder based on the current request URI
// This will help to determine the base URL for the application
$uri = rtrim($_SERVER['REQUEST_URI'], '/');
$subFolder = preg_replace('/\/[^\/]+\.[a-zA-Z0-9]+$/', '', $uri);
$subFolder = $subFolder === '' ? '/' : $subFolder;

// Static GET
get($subFolder.'', 'pages/index.php');

// For 404 error
// This will be used when the user tries to access a page that does not exist
any($subFolder.'/404','404.php');

// // Dynamic GET. Example with 1 variable
// // The $id will be available in user.php
// get('/user/$id', 'views/user');

// // Dynamic GET. Example with 2 variables
// // The $name will be available in full_name.php
// // The $last_name will be available in full_name.php
// // In the browser point to: localhost/user/X/Y
// get('/user/$name/$last_name', 'views/full_name.php');

// // Dynamic GET. Example with 2 variables with static
// // In the URL -> http://localhost/product/shoes/color/blue
// // The $type will be available in product.php
// // The $color will be available in product.php
// get('/product/$type/color/$color', 'product.php');

// // A route with a callback
// get('/callback', function(){
//   echo 'Callback executed';
// });

// // A route with a callback passing a variable
// // To run this route, in the browser type:
// // http://localhost/user/A
// get('/callback/$name', function($name){
//   echo "Callback executed. The name is $name";
// });

// // Route where the query string happens right after a forward slash
// get('/product', '');

// // A route with a callback passing 2 variables
// // To run this route, in the browser type:
// // http://localhost/callback/A/B
// get('/callback/$name/$last_name', function($name, $last_name){
//   echo "Callback executed. The full name is $name $last_name";
// });

// // Route that will use POST data
// post('/user', '/api/save_user');