<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

// Essential Constants {{{

define('CRLF', "\r\n");

define('AFK_ROOT', dirname(__FILE__));
define('AFK_VERSION', '1.2.4');

// }}}

// Utility Functions {{{

/**
 * @return The first non-empty argument in the arguments passed in.
 */
function coalesce()
{
	$args = func_get_args();
	return call_user_func_array(array('AFK', 'coalesce'), $args);
}

/**
 * Entity encodes the given string.
*/
function e($s)
{
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Entity-encoded echo: encodes all the special characters in it's arguments
 * and echos them.
 */
function ee()
{
	$args = func_get_args();
	echo htmlspecialchars(implode('', $args), ENT_QUOTES, 'UTF-8');
}

// }}}

// The order and position of these lines is important.
require AFK_ROOT . '/classes/AFK.php';
require AFK_ROOT . '/classes/AFK/PathList.php';
require AFK_ROOT . '/classes/AFK/Loader.php';
require AFK_ROOT . '/classes/AFK/Exception.php';
