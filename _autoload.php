<?php
// autoloading
function kanwil_autoload($class)
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
spl_autoload_register('kanwil_autoload');