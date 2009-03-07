<?php
function load_helper() {
	$args = func_get_args();
	call_user_func_array(array('AFK', 'load_helper'), $args);
}

function le($path) {
	$root = AFK_Registry::context()->application_root();
	ee($root, $path);
}

/**
 * Fetches an element from an array, returning a default value if the value
 * isn't there.
 */
function g(array $ary, $i, $default='') {
	if (array_key_exists($i, $ary)) {
		return $ary[$i];
	}
	return $default;
}

/**
 * Given a result set, gets all the unique values in the named column.
 */
function collect_column(array $rs, $name) {
	$values = array();
	foreach ($rs as $r) {
		if ($r[$name] != '') {
			$values[] = $r[$name];
		}
	}
	return array_unique($values);
}
