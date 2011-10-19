<?php
/** 
 * Simple gateway
 */

// autoloading
function __autoload($class)
{
	$tries = array(
		'./class/' . strtolower($class) . '.php',
		"./class/{$class}.php",
		'./class/' . str_replace('_', '/', strtolower($class)) . '.php',
		'./class/' . str_replace('_', '/', $class) . '.php',
	);
	foreach ($tries as $try) {
		if (is_file($try)) {
			include_once($try);
			return;
		}
	}
	// Class not found
	throw new Exception("Class $class not found");
}
 
// Get base URL
$base_url = str_replace(basename(__FILE__), '', $_SERVER['SCRIPT_NAME']);
// Get request part
$request = str_replace($base_url, '', $_SERVER['REQUEST_URI']);
if (strpos($request, '?')) $request = substr($request, 0, strpos($request, '?'));
// Include the requested file
if (is_file($request . '.php')) {
	include $request.'.php';
}