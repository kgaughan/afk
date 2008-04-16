<?php
define('_AFK_NOTIFICATIONS_KEY', '__notifications');

function add_notification($type, $message) {
	if (!array_key_exists(_AFK_NOTIFICATIONS_KEY, $_SESSION)) {
		$_SESSION[_AFK_NOTIFICATIONS_KEY] = array();
	}
	if (!array_key_exists($type, $_SESSION[_AFK_NOTIFICATIONS_KEY])) {
		$_SESSION[_AFK_NOTIFICATIONS_KEY][$type] = array();
	}
	$_SESSION[_AFK_NOTIFICATIONS_KEY][$type][] = $message;
}

function display_notifications() {
	if (array_key_exists(_AFK_NOTIFICATIONS_KEY, $_SESSION)) {
		ksort($_SESSION[_AFK_NOTIFICATIONS_KEY]);
		foreach ($_SESSION[_AFK_NOTIFICATIONS_KEY] as $type => $messages) {
			echo "<div class=\"notification $type\"><ul>";
			foreach ($messages as $message) {
				echo "<li>", e($message), "</li>";
			}
			echo "</ul></div>";
		}
		unset($_SESSION[_AFK_NOTIFICATIONS_KEY]);
	}
}

function select_box($name, array $elements, $default=null) {
	$default = coalesce(AFK_Registry::context()->__get($name), $default);
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
		foreach ($elements as $k => $v) {
			echo '<input type="hidden" name="', e($name), '" value="', e($k), '">', e($v);
		}
	} else {
		echo '&mdash;';
	}
}

/**
 *
 */
function carry_hidden_values() {
	$names = func_get_args();
	hidden_fields(AFK_Registry::context()->as_array($names), '');
}

/**
 *
 */
function hidden_fields(array $data, $prefix='') {
	foreach ($data as $name => $value) {
		$field = $prefix == '' ? $name : $prefix . '[' . $name . ']';
		if (is_array($value)) {
			hidden_fields($value, $field);
		} else {
			echo '<input type="hidden" name="', e($field), '" value="', e($value), '">';
		}
	}
}
