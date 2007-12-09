<?php
function add_notification($type, $message) {
	if (!isset($_SESSION['__notifications'])) {
		$_SESSION['__notifications'] = array();
	}
	if (!isset($_SESSION['__notifications'][$type])) {
		$_SESSION['__notifications'][$type] = array();
	}
	$_SESSION['__notifications'][$type][] = $message;
}

function display_notifications() {
	if (isset($_SESSION['__notifications'])) {
		ksort($_SESSION['__notifications']);
		foreach ($_SESSION['__notifications'] as $type => $messages) {
			echo "<div class=\"notification $type\"><ul>";
			foreach ($messages as $message) {
				echo "<li>", e($message), "</li>";
			}
			echo "</ul></div>";
		}
		unset($_SESSION['__notifications']);
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
