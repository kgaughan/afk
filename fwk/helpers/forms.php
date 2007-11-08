<?php
function select_box($name, array $elements, $default=null) {
	$default = coalesce($default, AFK_Registry::context()->__get($name), null);
	if (count($elements) > 1) {
		echo '<select name="', e($name), '" id="', e($name), '">';
		foreach ($elements as $k => $v) {
			echo '<option';
			if ($k == $default) {
				echo ' selected="selected"';
			}
			echo ' value="', e($k), '">', e($v), '</option>';
		}
		echo '</select>';
	} elseif (count($elements) == 1) {
		// This is in a loop so we can be sure what the array key is.
		// If there's only one, it's selected by default.
		foreach ($element as $k => $v) {
			echo '<input type="hidden" name="', e($name), '" value="', e($k), '">', e($v);
		}
	} else {
		echo '&mdash;';
	}
}
