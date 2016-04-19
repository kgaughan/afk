<?php
if (file_exists(APP_ROOT . '/site-config.php')) {
	require(APP_ROOT . '/site-config.php');
} else {
	require(APP_ROOT . '/deployment/configurations/default.php');
}
// Pull in defaults depending on the environment.
AFK::include_if_exists('defaults/' . strtolower(STATUS) . '.php');

define('APP_VERSION', '0.0.0');
define('WITH_LOGGING', STATUS == 'LIVE' || STATUS == 'STAGING');

function routes() {
	$r = new AFK_Routes();
	$r->route('/', array('_handler' => 'Root'));
	return $r;
}

function init() {
	global $db;

	error_reporting(E_ALL);

	AFK::add_helper_path(APP_ROOT . '/lib/bk-helpers');
	AFK::load_helper('core');

	$db = new DB_MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

	// If you need sessions, you might need this:
	// new AFK_Session_DB($db, 'sessions');
	// session_start();

	// If you need output caching, you'll need this:
	// AFK::load_helper('cache');
	// cache_install(new AFK_Cache_DB($db, 'cache'));

	return array();
}
