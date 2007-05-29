<?php
function scrub_cc_number($cc) {
	$result = '';
	for ($i = 0; $i < strlen($cc); $i++) {
		$ch = substr($cc, $i, 1);
		if (is_numeric($ch)) {
			$result .= $ch;
		}
	}
	return $result;
}

function luhn_check($cc_number) {
	$ttl = 0;
	$len = strlen($cc_number);
	$alt = false;
	for ($i = $len - 1; $i >= 0; $i--) {
		$digit = (int) substr($cc_number, $i, 1);
		if ($alt) {
			$digit *= 2;
			if ($digit > 9) {
				$digit -= 9;
			}
		}
		$ttl += $digit;
		$alt = !$alt;
	}
	return $ttl % 10 == 0;
}

/*
 * Antifwk
 * by Keith Gaughan
 *
 * Form helpers.
 */

function clear_request_fields() {
	$args = func_get_args();
	foreach ($args as $arg) {
		unset($_REQUEST[$arg]);
	}
}

/**
 *
 */
function has_int_fields($ary, $fields) {
	foreach ($fields as $field_name) {
		if (!array_key_exists($field_name, $ary) || !ctype_digit($ary[$field_name])) {
			return false;
		}
	}
	return true;
}

/**
 * Checks if a given checkbox/radiobutton in a scope is active or not.
 *
 * @param  $scope  Scope (i.e. $_REQUEST/$_GET/$_POST) to check.
 * @param  $name   Name of the checkbox/radiobutton group to check.
 * @param  $value  Value of the checkbox if active.
 *
 * @return TRUE if active, else FALSE.
 */
function is_checked($scope, $name, $value) {
	return isset($scope[$name]) && array_search($value, $scope[$name]) !== false;
}

/**
 * Saves the named POSTed parameters to cookies.
 */
function save_params() {
	$names = func_get_args();
	$time  = time() + make_timespan(0, 0, 0, 365);
	$path  = dirname($_SERVER['PHP_SELF']);
	foreach ($names as $name) {
		if (isset($_POST[$name])) {
			$value = $_POST[$name];
			if (is_array($value)) {
				$value = join(',', $value);
			}
			setcookie('c_' . $name, $value, $time, $path);
		}
	}
}

/**
 * Sets the value of a parameter in the $_REQUEST scope if unset, checking
 * if the value's previously been saved to a cookie first (with save_params()),
 * and then using a default value if there was no appropriate cookie.
 *
 * @param  $name   Parameter name.
 * @param  $value  Default value if unset and there's no appropriate cookie.
 */
function set_default($name, $value='') {
	if (!isset($_REQUEST[$name])) {
		if (isset($_COOKIE['c_' . $name])) {
			if (is_array($value)) {
				$_REQUEST[$name] = explode(',', $_COOKIE['c_' . $name]);
			} else {
				$_REQUEST[$name] = $_COOKIE['c_' . $name];
			}
		} else {
			$_REQUEST[$name] = $value;
		}
	}
}

/**
 * Fetches a parameter from $_REQUEST.
 */
function get_param($name, $default='') {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

/**
 * Fetches and echos a parameter from $_REQUEST.
 */
function put_param($name, $default='') {
	echo htmlentities(get_param($name, $default));
}

/**
 * Form helper generating a checkbox.
 *
 * @param  $name     Name of checkbox group.
 * @param  $value    Value corresponding to this checkbox.
 * @param  $label    Label text to use (automatically escaped).
 * @param  $checked  Optional parameter specifying whether it should be
 *                   checked; by default, it checks $_REQUEST and uses that.
 */
function checkbox($name, $value, $label, $checked=null) {
	if (is_null($checked)) {
		$checked = is_checked($_REQUEST, $name, $value);
	}
	$enc_value = htmlentities($value);
	echo '<input type="checkbox" name="', $name, '[]" id="';
	echo $name, '_', $enc_value, '" ';
	if ($checked) {
		echo 'checked="checked" ';
	}
	echo 'value="', $enc_value, '"/>&nbsp;';
	echo '<label for="', $name, '_', $enc_value, '">';
	echo htmlentities($label), '</label>';
}

/**
 * Form helper generating a start form tag.
 *
 * @param  $method  HTTP method to use for form; defaults to 'post'.
 * @param  $hidden  Array giving fields to include as hidden fields; if a
 *                  name is given by itself, the value is taken from $_REQUEST,
 *                  but if a name=>value mapping is given, that's used.
 */
function form($method='post', $hidden=array()) {
	echo '<form method="', strtolower($method), '" action="';
	echo htmlentities($_SERVER['PHP_SELF']), '">';
	foreach ($hidden as $name=>$value) {
		if (is_int($name)) {
			# Grab the value from $_REQUEST and write it out.
			$name  = $value;
			$value = $_REQUEST[$name];
		}
		hidden_field($name, $value);
	}
}

/**
 * Form helper to write out a named hidden field.
 *
 * @param  $name   Name of hidden field.
 * @param  $value  Value for field; defaults to an empty string; can be an
 *                 array.
 */
function hidden_field($name, $value='') {
	if (is_array($value)) {
		foreach ($value as $v) {
			hidden_field($name . '[]', $v);
		}
	} else {
		echo '<input type="hidden" name="', htmlentities($name);
		echo '" value="', htmlentities($value), '"/>';
	}
}

function text_field($name, $default='', $args=array()) {
	echo '<input type="text" name="', htmlentities($name), '" id="';
	echo htmlentities($name), '"';
	emit_tag_attributes($args);
	echo ' value="', htmlentities(get_param($name, $default)), '"/>';
}

function password_field($name, $default='', $args=array()) {
	echo '<input type="password" name="', htmlentities($name), '" id="';
	echo htmlentities($name), '"';
	emit_tag_attributes($args);
	echo ' value="', htmlentities(get_param($name, $default)), '"/>';
}

function number_dropdown($name, $start_from=0, $count=1) {
	echo '<select name="', htmlentities($name), '" id="', htmlentities($name), '">';
	for ($i = $start_from; $i < $start_from + $count; $i++) {
		echo '<option value="', $i, '"';
		if (isset($_REQUEST[$name]) && $_REQUEST[$name] == $i) {
			echo ' selected="selected"';
		}
		echo '>', $i, '</option>';
	}
	echo '</select>';
}

function textual_dropdown($name, $options) {
	echo '<select name="', htmlentities($name), '" id="', htmlentities($name), '">';
	emit_options($options, get_param($name)); 
	echo '</select>';
}

/**
 * Converts an array into a list of options for a select list.
 *
 * @param  $options   The options to convert.
 * @param  $selected  The value of the option that should be selected.
 */
function emit_options($options, $selected=null) {
	foreach ($options as $value=>$title) {
		echo '<option ';
		if ($value == $selected) {
			echo 'selected="selected" ';
		}
		echo 'value="', htmlentities($value), '">';
		echo htmlentities($title), "</option>";
	}
}

/**
 * Validates a form given a set of constraints.
 *
 * @param  $src  Array containing form variables to validate.
 * @param  $constraints  Constraints the form must satisfy.
 *
 * @return An array of errors; empty if the constraints were satisfied.
 *
 * @details
 *
 * Each constraint consists of a three-element array. The first element is
 * the name of the field to validate. The second is the constraints string.
 * The third is the error message to use if the constraint cannot b
 * satisfied.
 *
 * A constraints string is a comma-separated list of functions that must be
 * satisfied for the field to be valid. It looks like this:
 *
 *     'constraint1,!constraint2,constraint3'
 *
 * If a function name is prefixed with '!', a logical not is done on the
 * function's result.
 *
 * Each constraint is a function whose name is validate_<constraint_name>.
 * Built-in functions can't be used because of limitations in PHP itself.
 */
function validate_params($src, $constraints) {
	$errors = array();
	foreach ($constraints as $constraint) {
		list($fields, $conds, $msg) = $constraint;
		$validates = true;
		$conds = explode(',', $conds);
		# Check each to see if they conform to the field constraints.
		foreach ($conds as $cond) {
			$negate = substr($cond, 0, 1) == '!';
			if ($negate) {
				$cond = substr($cond, 1);
			}

			# Construct and call the constraint function.
			$args = array();
			foreach (explode(',', $fields) as $field) {
				$args[] = $src[$field];
			}
			$validates = call_user_func_array("validate_$cond", $args);

			if ($negate) {
				$validates = !$validates;
			}

			if (!$validates) {
				break;
			}
		}
		if (!$validates) {
			$errors[] = $msg;
		}
	}
	return $errors;
}

/**
 *
 */
function generate_javascript_validation($form_id, $constraints, $include_tag=false) {
	# I'm going to generate the js code verbatim for now.
	if ($include_tag) {
		echo '<script type="text/javascript"><![CDATA[';
	}

	# Wrap the code up in its own scope.
	echo '(function() {';

	echo 'var frm = $("', js_escape($form_id), '");';
	echo 'frm.onsubmit = function() {';
	echo 'var errors = validate(frm, [';

	$is_first = true;
	foreach ($constraints as $constraint) {
		if (!$is_first) {
			echo ', ';
		} else {
			$is_first = false;
		}
		list($field, $conds, $msg) = $constraint;
		$field = js_escape($field);
		$conds = js_escape($conds);
		$msg   = js_escape($msg);
		echo "['$field', '$conds', '$msg']";
	}

	echo ']);';
	echo 'return reportErrors(errors);';
	echo '};';

	# Close the scope and evaluate its contents.
	echo '})()';

	if ($include_tag) {
		echo '}]]></script>';
	}
}

############################################# Common Validation Functions ##

/**
 * Wrapper around empty() to allow validate() to apply it.
 */
function validate_is_empty($v) {
	return empty($v);
}

/**
 * Quick check to see is the email looks good.
 */
function validate_is_email($s) {
	return preg_match('/^[-a-z0-9_.]+@([-a-z0-9_]+\.)+[a-z]+$/i', $s);
}

/**
 * Wrapper around is_numeric() to get around PHP's inability to apply
 * built-ins by name.
 */
function validate_is_integer($v) {
	return is_numeric($v);
}

function emit_tag_attributes($attrs) {
	foreach ($attrs as $k=>$v) {
		echo ' ', $k, '="', htmlentities($v), '"';
	}
}

function pagination($name, $limit, $extra=array()) {
	$page = get_param($name, 1);
	echo '<ol class="pagination">';
	for ($i = 1; $i <= $limit; $i++) {
		echo '<li>';
		if ($i == $page) {
			echo '<strong>';
		} else {
			echo '<a href="';
			to_self();
			echo '?', htmlentities($name), '=', $i;
			if (count($extra) > 0) {
				echo '&amp;';
				foreach ($extra as $k=>$v) {
					echo htmlentities($k), '=', htmlentities($v);
				}
			}
			echo '">';
		}
		echo $i;
		if ($i == $page) {
			echo '</strong>';
		} else {
			echo '</a>';
		}
		echo '</li>';
	}
	echo '</ol>';
}

/*(

Using HTTP Digest
=================

$users = array(
	'admin' => 'zoop',
	'guest' => 'zing'
);

if (http_digest_require_auth()) {
	die('Text to send if user hits Cancel button');
}

$data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);

dump($data);

// Analyze the PHP_AUTH_DIGEST variable
if (!$data || !isset($users[$data['username']]) ||
		http_digest_is_valid_response($data, $users[$data['username']])) {
	die('Wrong Credentials!');
}

// ok, valid username & password
echo 'Your are logged in as: ' . $data['username'];

)*/

/**
 * Parses PHP_AUTH_DIGEST to pull apart the various elements.
 */
function http_digest_parse($s) {
	// Entries are removed from this as they're parsed. This is later checked
	// to see if all the required entries are present; it should be empty.
	$required = array(
		'nonce'    => 1,
		'nc'       => 1,
		'cnonce'   => 1,
		'qop'      => 1,
		'username' => 1,
		'uri'      => 1,
		'response' => 1
	);

	preg_match_all('@(\w+)=("([^"]+)"|([^ ,]+))@', $s, $matches, PREG_SET_ORDER);
	$result = array();
	foreach ($matches as $m) {
		$result[$m[1]] = $m[3] ? $m[3] : $m[4];
		unset($required[$m[1]]);
	}

	return empty($required) ? $result : false;
}

/**
 * Checks a parsed HTTP digest against the given password.
 */
function http_digest_is_valid_response($d, $password) {
	$a = md5($d['username'] . ':' . $d['realm'] . ':' . $password);
	$b = md5($_SERVER['REQUEST_METHOD'] . ':' . $d['uri']);
	$valid_response = md5($a . ':' . $d['nonce'] . ':' . $d['nc'] . ':' . $d['cnonce'] . ':' . $d['qop'] . ':' . $b);
	return $d['response'] == $valid_response;
}

/**
 * Initiates HTTP digest auth, if no digest was given in the request.
 *
 * @return True if the client is being challenged; otherwise false.
 */
function http_digest_require_auth($realm='Default Realm', $force=false) {
	if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
		header('HTTP/1.1 401 Unauthorized');
		header('WWW-Authenticate: Digest realm="' . $realm .
			'",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) . '"');
		return true;
	}
	return false;
}

function http_get_full_request_url() {
	static $url = '';
	if ($url == '') {
		// It makes sense to build this only once as it's not going to be
		// changing.
		$url = 'http';
		$port = 80;
		if (!empty($_SERVER['HTTPS'])) {
			$url .= 's';
			$port = 443;
		}
		$url .= '://' . $_SERVER['HTTP_HOST'];
		if ($_SERVER['SERVER_PORT'] != $port) {
			$url .= ':' . $_SERVER['SERVER_PORT'];
		}
		$url .= $_SERVER['REQUEST_URI'];
	}
	return $url;
}

function send_error_response_if_any($response) {
	if (isset($response['error'])) {
		header('HTTP/1.1 400 Bad Request');
		echo "<response>\n\t<error>", xml_escape($response['error']), "</error>\n</response>\n";
		return true;
	}
	return false;
}

function send_response($response) {
	header('Content-Type: application/xml');
	if ($response === false || isset($response['error'])) {
		$error = isset($response['error']) ? $response['error'] : 'Request failed.';
		header('HTTP/1.1 400 Bad Request');
		echo "<response>\n\t<error>", xml_escape($error), "</error>\n</response>\n";
	} else {
		if (isset($response['http::response'])) {
			header('HTTP/1.1 ' . $response['http::response']);
		}
		echo "<response>\n";
		if (isset($response['id'])) {
			echo "\t<id>", $response['id'], "</id>\n";
		}
		if (isset($response['uri'])) {
			echo "\t<uri>", xml_escape($response['uri']), "</uri>\n";
		}
		if (isset($response['message'])) {
			echo "\t<message>", xml_escape($response['message']), "</message>\n";
		} else {
			echo "\t<message>OK</message>\n";
		}
		echo "</response>\n";
	}
}

function method_not_allowed_response($type) {
	header('HTTP/1.1 405 Method Not Allowed');
	$allowed = array();
	$dh = dir("./handlers/$type/");
	while (($entry = $dh->read()) !== false) {
		if ($entry != '.' && $entry != '..') {
			$allowed[] = strtoupper(basename($entry, '.inc'));
		}
	}
	header('Allow: ' . implode(', ', $allowed));
	echo 'Method not allowed.';
}

function not_found_response() {
	header('HTTP/1.1 404 Not Found');
	echo 'Not found.';
}

function get_http_request_method() {
	$method = strtolower($_SERVER['REQUEST_METHOD']);
	if ($method == 'post' && !empty($_GET['__method'])) {
		# Tunneling in over POST.
		$method = strtolower($_GET['__method']);
	}
	return $method;
}

function is_valid_http_method($method) {
	return array_search($method, array('get', 'put', 'delete', 'post', 'head')) !== false;
}

function get_request_resource_type($default) {
	if (!isset($_GET['type'])) {
		$_GET['type'] = $default;
	}

	# Get the type and strip out any junk.
	$type = preg_replace("/([a-z])/", "\\1", $_GET['type']);

	# The type must have a corresponding resource directory.
	if ($_GET['type'] != $type || $type == '' || !is_dir("./handlers/$type")) {
		return false;
	}

	return $type;
}

/**
 *
 */
function magic_quotes($txt) {
	return  str_replace('"', '&#8221;',
			str_replace("'", '&#8217;',
			str_replace('&#8220;\'', '&#8220;&#8216;',
			preg_replace('/(^|\s|&#8216;)\"/', "\\1&#8220;",
			preg_replace('/(^|\s)\'/', "\\1&#8216;", $txt)))));
}

function latin1_to_utf8($txt) {
	# Logic cogged from Sam Ruby's article "Survival Guide to i18n" at
	# http://intertwingly.net/stories/2004/04/14/i18n.html
	return preg_replace('/([\x80-\xFF])/e',
		"ord('\\1') < 192 ? chr(194) . '\\1' : chr(195) . chr(ord('\\1') - 64)",
		$txt);
}

/**
 * Prepares text for output, escaping special characters and converting any
 * typewriter quotes to typographer's quotes, and converting it to UTF-8.
 */
function prepare_text($txt) {
	return magic_quotes(htmlspecialchars(latin1_to_utf8($txt), ENT_NOQUOTES));
}

/**
 * 
 */
function js_escape($s) {
	return strtr($s, array(
		'\\' => '\\\\',
		'"'  => '\\"',
		"'"  => "\\'",
		"\n" => '\\n',
		"\t" => '\\t'
	));
}

/**
 *
 */
function edate($format, $dt) {
	if (!is_int($dt)) {
		$dt = strtotime($dt);
	}
	echo strftime($format, $dt);
}

function iso8601_datetime($dt) {
	if (!is_int($dt)) {
		$dt = strtotime($dt);
	}
	return date('Y-m-d\TH:i:sO', $dt);
}

/**
 * Generates a timespan in seconds.
 *
 * @param  $sec  Number of seconds to include in timespan; may exceed 60.
 * @param  $min  Ditto for minutes.
 * @param  $hr   Ditto for hours; may exceed 24.
 * @param  $dy   Ditto for days.
 *
 * @return Specified timespan in seconds.
 */
function make_timespan($sec=0, $min=0, $hr=0, $dy=0) {
	return $sec + ($min * 60) + ($hr * 360) + ($dy * 8640);
}
?>
