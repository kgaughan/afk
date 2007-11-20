<?php
/*
 * This library is solely for use within afk itself and not outside.
 * It's mainly code required to have test code work well without driving
 * me insane by having to explicitly include classes under test everywhere.
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/fwk/afk.php';

function routes() {
	return new AFK_Routes();
}

function init() {
	return array();
}
