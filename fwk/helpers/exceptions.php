<?php
/** Converts an exception trace frame to a function/method name. */
function frame_to_name($frame) {
	$name = '';
	if (isset($frame['class'])) {
		$name .= $frame['class'] . $frame['type'];
	}
	$name .= $frame['function'];
	return $name;
}

function get_context_lines($file, $line_no, $amount=3) {
	$context = array();
	$lines = file($file);
	for ($i = $line_no - $amount; $i <= $line_no + $amount; $i++) {
		if ($i >= 0 && isset($lines[$i - 1])) {
			$context[$i] = $lines[$i - 1];
		}
	}
	return array(max(1, $line_no - $amount), $context);
}

/* Truncates an application/library filename. */
function truncate_filename($filename) {
	if (substr($filename, 0, strlen(AFK_ROOT)) == AFK_ROOT) {
		return 'AFK:' . substr($filename, strlen(AFK_ROOT) + 1);
	}
	if (defined('APP_ROOT') && substr($filename, 0, strlen(APP_ROOT)) == APP_ROOT) {
		return substr($filename, strlen(APP_ROOT) + 1);
	}
	return $filename;
}
?>
