<?php
define('_AFK_NOTIFICATIONS_KEY', '__notifications');

/**
 * Records a form notification message. Note that this code requires
 * session support so that is can persist the notifications across
 * requests.
 */
function add_notification($type, $message) {
	if (isset($_SESSION)) {
		if (!array_key_exists(_AFK_NOTIFICATIONS_KEY, $_SESSION)) {
			$_SESSION[_AFK_NOTIFICATIONS_KEY] = array();
		}
		if (!array_key_exists($type, $_SESSION[_AFK_NOTIFICATIONS_KEY])) {
			$_SESSION[_AFK_NOTIFICATIONS_KEY][$type] = array();
		}
		$_SESSION[_AFK_NOTIFICATIONS_KEY][$type][] = $message;
	} else {
		$ctx = AFK_Registry::context();
		if (isset($ctx->__notifications)) {
			$notifications = $ctx->__notifications;
		} else {
			$notifications = array();
		}
		if (!array_key_exists($type, $notifications)) {
			$notifications[$type] = array();
		}
		$notifications[$type][] = $message;
		$ctx->__notifications = $notifications;
	}
}

/**
 * Displays and clears any currently recorded form notifications.
 */
function display_notifications($template='notifications') {
	if (isset($_SESSION) && array_key_exists(_AFK_NOTIFICATIONS_KEY, $_SESSION)) {
		$notifications = $_SESSION[_AFK_NOTIFICATIONS_KEY];
		unset($_SESSION[_AFK_NOTIFICATIONS_KEY]);
	} else {
		$ctx = AFK_Registry::context();
		if (isset($ctx->__notifications)) {
			$notifications = $ctx->__notifications;
		} else {
			return;
		}
	}

	ksort($notifications);
	$t = AFK_Registry::_('template_engine');
	foreach ($notifications as $type => $messages) {
		$t->render($template, compact('type', 'messages'));
	}
}

function get_field($name, $default=null) {
	return AFK::coalesce(AFK_Registry::context()->__get($name), $default);
}

function radio_field($name, array $elements, $default=null, array $field_ids=array()) {
	$selected = get_field($name, $default);
	if (count($elements) > 1) {
		$i = 0;
		foreach ($elements as $k => $v) {
			echo '<label><input type="radio" name="', e($name), '" ';
			if ($k == $selected) {
				echo 'checked="checked" ';
			}
			echo 'id="', e(isset($field_ids[$k]) ? $field_ids[$k] : "{$name}__{$k}"), '" ';
			echo 'value="', e($k), '">', e($v), '</label>';
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

function checkbox_field($name, $value, $id=null) {
	echo '<input type="checkbox" name="', e($name), '" value="', e($value), '"';
	if (!is_null($id)) {
		echo ' id="', e($id), '"';
	}
	if (get_field($name) == $value) {
		echo ' checked="checked"';
	}
	echo '>';
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
		select_box__options($elements, $selected, $default);
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

function select_box__options(array $elements, $selected, $default) {
	foreach ($elements as $k => $v) {
		if (is_array($v)) {
			echo '<optgroup label="', e($k), '">';
			select_box__options($v, $selected, $default);
			echo '</optgroup>';
		} else {
			echo '<option';
			if ($k == $selected) {
				echo ' selected="selected"';
			}
			echo ' value="', e($k), '">', e($v), '</option>';
		}
	}
}

function select_box_to_title(array $elements, $value, $default=false) {
	foreach ($elements as $k => $v) {
		if (is_array($v)) {
			return select_box_to_title($v, $value);
		}
		if ($k == $value) {
			return $v;
		}
	}
	return $default;
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
		$field = $prefix == '' ? $name : ($prefix . '[' . $name . ']');
		if (is_array($value)) {
			hidden_fields($value, $field);
		} else {
			echo '<input type="hidden" name="', e($field), '" value="', e($value), '">';
		}
	}
}
