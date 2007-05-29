<?php
define('DB_NAME', '');
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');

/**
 * 
 */
function routes() {
	$r = new AFK_Routes();
	$r->route('/',         'Root');
	return $r;
}

function init() {
	date_default_timezone_set('UTC');
	return array();
}
?>
