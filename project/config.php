<?php
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

define('APP_VERSION', '0.0.0');

function routes() {
	$r = new AFK_Routes();
	$r->route('/', array('_handler' => 'Root'));
	$r->fallback(array('_handler' => 'AFK_Default'));
	return $r;
}

function init() {
	global $db, $db_logger;

	error_reporting(E_ALL);
	date_default_timezone_set('UTC');
	AFK::load_helper('core');

	if (defined('DB_NAME') && DB_NAME != '') {
		$db = new DB_MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$db->set_logger($db_logger = new DB_BasicLogger());
		new AFK_Session_DB($db, 'sessions');
		AFK_Cache::set_backend(new Cache_DB($db, 'cache'));
	}

	session_start();

	return array();
}
?>
