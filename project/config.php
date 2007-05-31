<?php
function routes() {
	$r = new AFK_Routes();
	$r->route('/', 'Root');
	$r->fallback('Default');
	return $r;
}

function init() {
	date_default_timezone_set('UTC');
	return array();
}
?>
