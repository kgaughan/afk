<?php
/*
 * afk.php
 * by Keith Gaughan
 *
 * Copyright (c) Keith Gaughan, 2007.
 * All Rights Reserved.
 *
 * Permission is given to use, modify and distribute modified and unmodified
 * versions of this software on condition that all copyright notices are
 * retained and a record of changes made to this is software is kept and
 * distributed with any modified version. No warranty, implied or otherwise,
 * is given on this software as to its fitness for any purpose. The author is
 * not liable for any damage, loss of data, or other misfortune caused as a
 * result of the use/misuse of this software.
 */

define('CRLF', "\r\n");

define('AFK_ROOT', dirname(__FILE__));
define('AFK_VERSION', '1.2.2');

require(AFK_ROOT . '/classes/AFK.php');

AFK::register_autoloader();
AFK::add_class_path(AFK_ROOT . '/classes');
AFK::add_helper_path(AFK_ROOT . '/helpers');
AFK_TemplateEngine::add_paths(AFK_ROOT . '/templates');

// ---------------------------------------------------- Utility Functions --

/** Returns the first non-empty argument in the arguments passed in. */
function coalesce() {
	$args = func_get_args();
	$default = $args[0];
	foreach ($args as $arg) {
		if (!empty($arg)) {
			return $arg;
		}
	}
	return $default;
}

/** Entity encodes the given string. */
function e($s) {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Entity-encoded echo: encodes all the special characters in it's arguments
 * and echos them.
 */
function ee() {
	$args = func_get_args();
	echo htmlspecialchars(implode('', $args), ENT_QUOTES, 'UTF-8');
}
?>
