<?php
define('_AFK_NOTIFICATIONS_KEY', '__notifications');

/**
 * Records a form notification message. Note that this code requires
 * session support so that is can persist the notifications across
 * requests.
 */
function add_notification($type, $message) {
	if (!array_key_exists(_AFK_NOTIFICATIONS_KEY, $_SESSION)) {
		$_SESSION[_AFK_NOTIFICATIONS_KEY] = array();
	}
	if (!array_key_exists($type, $_SESSION[_AFK_NOTIFICATIONS_KEY])) {
		$_SESSION[_AFK_NOTIFICATIONS_KEY][$type] = array();
	}
	$_SESSION[_AFK_NOTIFICATIONS_KEY][$type][] = $message;
}

/**
 * Displays and clears any currently recorded form notifications.
 */
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

function get_field($name, $default=null) {
	return coalesce(AFK_Registry::context()->__get($name), $default);
}

function radio_field($name, array $elements, $default=null) {
	$selected = get_field($name, $default);
	if (count($elements) > 1) {
		$i = 0;
		foreach ($elements as $k => $v) {
			echo '<label><input type="radio" name="', e($name), '" ';
			if ($k == $selected) {
				echo 'checked="checked" ';
			}
			echo 'value="', e($k), '">', ee($v), '</label>';
			if (++$i < count($elements)) {
				echo "<br>\n";
			}
		}
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
 * Generates a select dropdown form element. The default selected element
 * is chosen by checking if the current context has an entry for that named
 * element, and if so using that as the default, otherwise using the
 * specified fallback default.
 */
function select_box($name, array $elements, $default=null) {
	$selected = get_field($name, $default);
	if (count($elements) > 1) {
		echo '<select name="', e($name), '" id="', e($name), '">';
		foreach ($elements as $k => $v) {
			echo '<option';
			if ($k == $selected) {
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
 * Renders the named entries in the current context as hidden form fields.
 */
function carry_hidden_values() {
	$names = func_get_args();
	hidden_fields(AFK_Registry::context()->as_array($names), '');
}

/**
 * Renders the given array as a set of hidden form fields.
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
