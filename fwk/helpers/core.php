<?php
function load_helper($name) {
	AFK::load_helper($name);
}

/**
 * Fetches an element from an array, returning a default value if the value
 * isn't there.
 */
function g($ary, $i, $default='') {
	if (isset($ary[$i])) {
		return $ary[$i];
	}
	return $default;
}

/**
 * Checks if the current ETag for a resource matches one of those sent
 * back by the client.
 */
function check_etag($current_etag) {
	// A bit simplistic for now, but should work ok.
	return is_numeric(array_search("\"$current_etag\"",
		explode(', ', g($_SERVER, 'HTTP_IF_NONE_MATCH', ''))));
}
?>
