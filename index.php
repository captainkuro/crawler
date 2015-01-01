<?php
/** 
 * Simple gateway
 */

require '_autoload.php';
 
// Get base URL
$base_url = str_replace(basename(__FILE__), '', $_SERVER['SCRIPT_NAME']);
// Get request part
$request = str_replace($base_url, '', $_SERVER['REQUEST_URI']);
if (strpos($request, '?')) $request = substr($request, 0, strpos($request, '?'));
// Include the requested file
if (is_file($request . '.php')) {
	include $request.'.php';
}

define('KANWIL_AUTOLOADED', true);