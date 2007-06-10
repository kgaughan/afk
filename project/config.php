<?php
function routes() {
	$r = new AFK_Routes();
	$r->route('/', array('_handler' => 'Root'));
	$r->fallback(array('_handler' => 'AFK_Default'));
	return $r;
}

function init() {
	error_reporting(E_ALL);
	date_default_timezone_set('UTC');
	return array();
}
?>
