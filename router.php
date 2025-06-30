<?php

/* ---------------------------------------------------- */
// CREDIT to author from:
// https://phprouter.com
/* ---------------------------------------------------- */

function get($route, $path_to_include)
{
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		route($route, $path_to_include);
	}

}
function post($route, $path_to_include)
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		route($route, $path_to_include);
	}
}
function put($route, $path_to_include)
{
	if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
		route($route, $path_to_include);
	}
}
function patch($route, $path_to_include)
{
	if ($_SERVER['REQUEST_METHOD'] == 'PATCH') {
		route($route, $path_to_include);
	}
}
function delete($route, $path_to_include)
{
	if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
		route($route, $path_to_include);
	}
}
function any($route, $path_to_include)
{
	route($route, $path_to_include);
}
function route($route, $path_to_include){

	$callback = $path_to_include;
	if (!is_callable($callback)) {
		if (!strpos($path_to_include, '.php')) {
			$path_to_include .= '.php';
		}
	}
	
	// Security: Sanitize path to prevent path traversal attacks
	if (!is_callable($callback)) {
		// Use Validator class if available for path sanitization  
		if (class_exists('WPLite\Core\Validator')) {
			$path_to_include = \WPLite\Core\Validator::sanitizePath($path_to_include);
		} else {
			// Fallback sanitization
			$path_to_include = sanitize_file_path($path_to_include);
		}
		
		// Ensure the file exists and is within allowed directory
		$fullPath = getcwd() . "/" . $path_to_include;
		$realPath = realpath($fullPath);
		$basePath = realpath(getcwd());
		
		// Prevent path traversal - ensure file is within base directory
		if (!$realPath || strpos($realPath, $basePath) !== 0) {
			// Security: Log the attempt
			error_log("WPLiteCore Security: Path traversal attempt blocked: " . $path_to_include);
			
			// Show 404 instead of revealing path information
			http_response_code(404);
			if (file_exists(getcwd() . "/404.php")) {
				include_once getcwd() . "/404.php";
			} else {
				echo '404 - Page Not Found';
			}
			exit();
		}
	}
	
	if ($route == "/404") {
		include_once getcwd() . "/$path_to_include";
		exit();
	}

	// Security: Enhanced URL sanitization  
	$request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
	$request_url = rtrim($request_url, '/');
	$request_url = strtok($request_url, '?');
	
	// Additional security: Remove potentially dangerous characters
	$request_url = preg_replace('/[^a-zA-Z0-9\/_\-.]/', '', $request_url);
	
	$route_parts = explode('/', $route);
	$request_url_parts = explode('/', $request_url);
	array_shift($route_parts);
	array_shift($request_url_parts);
	
	if ($route_parts[0] == '' && count($request_url_parts) == 0) {
		// Callback function
		if (is_callable($callback)) {
			call_user_func_array($callback, []);
			exit();
		}
		include_once getcwd() . "/$path_to_include";
		exit();
	}
	if (count($route_parts) != count($request_url_parts)) {
		return;
	}
	$parameters = [];
	for ($__i__ = 0; $__i__ < count($route_parts); $__i__++) {
		$route_part = $route_parts[$__i__];
		if (preg_match("/^[$]/", $route_part)) {
			$route_part = ltrim($route_part, '$');
			
			// Security: Sanitize route parameters
			$sanitized_param = sanitize_route_parameter($request_url_parts[$__i__]);
			array_push($parameters, $sanitized_param);
			$$route_part = $sanitized_param;
		} else if ($route_parts[$__i__] != $request_url_parts[$__i__]) {
			return;
		}
	}
	// Callback function
	if (is_callable($callback)) {
		call_user_func_array($callback, $parameters);
		exit();
	}
	include_once getcwd() . "/$path_to_include";
	exit();
}

/**
 * Sanitize file path to prevent directory traversal attacks
 */
function sanitize_file_path($path) {
	// Remove any attempt at directory traversal
	$path = str_replace(['../', '..\\', '..', './', '.\\'], '', $path);
	
	// Remove null bytes
	$path = str_replace("\0", '', $path);
	
	// Remove dangerous characters
	$path = preg_replace('/[^a-zA-Z0-9\/_\-.]/', '', $path);
	
	// Remove leading slashes and dots
	$path = ltrim($path, './\\');
	
	return $path;
}

/**
 * Sanitize route parameters
 */
function sanitize_route_parameter($param) {
	// Remove null bytes and dangerous characters
	$param = str_replace("\0", '', $param);
	
	// Basic sanitization - allow alphanumeric, hyphens, underscores
	$param = preg_replace('/[^a-zA-Z0-9\-_]/', '', $param);
	
	// Limit length
	$param = substr($param, 0, 255);
	
	return $param;
}

function out($text)
{
	echo htmlspecialchars($text);
}

function set_csrf()
{
	session_start();
	if (!isset($_SESSION["csrf"])) {
		$_SESSION["csrf"] = bin2hex(random_bytes(50));
	}
	echo '<input type="hidden" name="csrf" value="' . $_SESSION["csrf"] . '">';
}

function is_csrf_valid()
{
	session_start();
	if (!isset($_SESSION['csrf']) || !isset($_POST['csrf'])) {
		return false;
	}
	if ($_SESSION['csrf'] != $_POST['csrf']) {
		return false;
	}
	return true;
}