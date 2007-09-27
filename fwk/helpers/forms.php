<?php
function select_box($name, $elements, $default=null) {
	$default = coalesce($default, AFK_Registry::context()->__get($name), null);
	echo '<select name="', e($name), '" id="', e($name), '">';
	foreach ($elements as $k=>$v) {
		echo '<option';
		if ($k == $default) {
			echo ' selected="selected"';
		}
		echo ' value="', e($k), '">', e($v), '</option>';
	}
	echo '</select>';
}
