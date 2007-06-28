<?php
/*
 * afk.php
 * by Keith Gaughan
 *
 * Copyright (c) Keith Gaughan, 2007.
 * All Rights Reserved.
 *
 * Permission is given to use, modify and distribute modified and unmodified
 * versions of this software on condition that all copyright notices are
 * retained and a record of changes made to this is software is kept and
 * distributed with any modified version. No warranty, implied or otherwise,
 * is given on this software as to its fitness for any purpose. The author is
 * not liable for any damage, loss of data, or other misfortune caused as a
 * result of the use/misuse of this software.
 */

define('CRLF', "\r\n");

define('AFK_ROOT', dirname(__FILE__));
define('AFK_VERSION', '1.2.1');

AFK::register_autoloader();
AFK::add_helper_path(AFK_ROOT . '/helpers');
AFK_TemplateEngine::add_paths(AFK_ROOT . '/templates');

// ---------------------------------------------------- Utility Functions --

/** Returns the first non-empty argument in the arguments passed in. */
function coalesce() {
	$args = func_get_args();
	$default = $args[0];
	foreach ($args as $arg) {
		if (!empty($arg)) {
			return $arg;
		}
	}
	return $default;
}

/** Entity encodes the given string. */
function e($s) {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Entity-encoded echo: encodes all the special characters in it's arguments
 * and echos them.
 */
function ee() {
	$args = func_get_args();
	echo htmlspecialchars(implode('', $args), ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------- Web Application Library --

/**
 * Various framework utilities, mostly for internal use.
 */
class AFK {

	private static $class_paths = array();
	private static $helper_paths = array();
	private static $loaded_helpers = array();
	private static $registry = array();

	public static function register_autoloader() {
		if (function_exists('spl_autoload_register')) {
			// Attempt to register an autoloader cleanly...
			spl_autoload_register(array(__CLASS__, 'load_class'));
		} else {
			// ...but if all else fails, just slam it in.
			function __autoload($name) {
				AFK::load_class($name);
			}
		}
	}

	/** Adds a new directory to use when searching for classes. */
	public static function add_class_path($path) {
		self::$class_paths[] = $path;
	}

	/** Loads the named class from one of the registered class paths. */
	public static function load_class($name) {
		return self::load(self::$class_paths, $name);
	}

	/** Adds a new directory to use when searching for helpers. */
	public static function add_helper_path($path) {
		self::$helper_paths[] = $path;
	}

	/** Loads the named helpers. */
	public static function load_helper() {
		$helpers = func_get_args();
		foreach ($helpers as $name) {
			if (!isset(self::$loaded_helpers[$name]) && !self::load(self::$helper_paths, $name)) {
				throw new AFK_Exception("Unknown helper: $name");
			} else {
				// There's no significance to the stored value: we're just using
				// the array keys as a set.
				self::$loaded_helpers[$name] = true;
			}
		}
	}

	/** Searches a list of directories for the named PHP file. */
	private static function load($paths, $name) {
		foreach ($paths as $path) {
			$file = "$path/$name.php";
			if (is_file($file)) {
				require $file;
				return true;
			}
		}
		return false;
	}

	public static function register($key, $object) {
		if (isset(self::$registry[$key])) {
			throw new AFK_Exception("The object '$key' is already registered.");
		}
		self::$registry[$key] = $object;
		return $object;
	}

	public static function get($key) {
		if (isset(self::$registry[$key])) {
			return self::$registry[$key];
		}
		return false;
	}

	/** Does a clean HTML dump of the given variable. */
	public static function dump() {
		$vs = func_get_args();
		ob_start();
		call_user_func_array('var_dump', $vs);
		$contents = ob_get_contents();
		ob_end_clean();
		if (!extension_loaded('Xdebug') && php_sapi_name() != 'cli') {
			$contents = '<pre style="border:1px solid red;padding:1ex;background:white;color:black;max-height:60em;overflow:auto">' . e($contents) . '</pre>';
		}
		echo $contents;
	}

	/** Fixes the superglobals by removing any magic quotes, if present. */
	public static function fix_superglobals() {
		if (ini_get('magic_quotes_gpc')) {
			self::fix_magic_quotes($_GET);
			self::fix_magic_quotes($_POST);
			self::fix_magic_quotes($_COOKIE);
			self::fix_magic_quotes($_REQUEST);
		}
	}

	/** Walks an array, fixing magic quotes. */
	public static function fix_magic_quotes(&$a) {
		$keys =& array_keys($a);
		$n    =  count($keys);

		for ($i = 0; $i < $n; $i++) {
			$val =& $a[$keys[$i]];
			if (is_array($val)) {
				self::fix_magic_quotes($val);
			} else {
				$val = stripslashes($val);
			}
		}
	}

	/** Basic dispatcher logic. Feel free to write your own dispatcher. */
	public static function process_request($routes, $extra_filters=array()) {
		self::fix_superglobals();

		$p = new AFK_Pipeline();
		$p->add(new AFK_RouteFilter($routes, $_SERVER, $_REQUEST));
		foreach ($extra_filters as $filter) {
			$p->add($filter);
		}
		$p->add(new AFK_DispatchFilter());
		$p->add(new AFK_RenderFilter());
		$p->start();
	}
}

/**
 * A persistent output cache.
 *
 * Use the cache like this:
 *
 * <?php if (AFK_Cache::start('foo')) { ?>
 *     ...expensive to generate content...
 * <?php AFK_Cache::end() } ?>
 */
class AFK_Cache {

	/* Cache backend in use. */
	private static $backend = null;

	/* ID of current cache block. */
	private static $id;

	/**
	 * Specify the implementation of the Cache interface to use as the
	 * persistence mechanism.
	 */
	public static function set_backend(Cache $backend) {
		self::$backend = $backend;
	}

	private static function ensure_backend() {
		if (is_null(self::$backend)) {
			self::set_backend(new Cache_Null());
		}
	}

	/**
	 * Start a cache block, outputting the previously cached content if
	 * it's still valid.
	 *
	 * @param  id       ID of the cache block.
	 * @param  max_age  Maximum age of the block.
	 *
	 * @return True if the cache is valid, false if not.
	 */
	public static function start($id, $max_age=300) {
		self::ensure_backend();
		$content = self::$backend->load($id, $max_age);
		if (!is_null($content)) {
			echo $content;
			return false;
		}
		ob_start();
		ob_implicit_flush(false);
		self::$id = $id;
		return true;
	}

	/** Marks the end the cache block. */
	public static function end() {
		self::$backend->save(self::$id, ob_get_contents());
		ob_end_flush();
	}

	/** Removes an item from the cache. */
	public static function remove($id) {
		self::ensure_backend();
		self::$backend->invalidate($id);
	}
}

/**
 * Represents the current request context.
 */
class AFK_Context {

	private static $instance = null;

	private $ctx = array();
	private $allow_rendering = true;

	private function __construct() {
	}

	/**
	 * Merges the given arrays into the current request context; existing
	 * values are not overwritten.
	 */
	public function merge() {
		$args = func_get_args();
		foreach ($args as $a) {
			// Values already in the context are preserved.
			$this->ctx = array_merge($a, $this->ctx);
		}
	}

	public function __isset($key) {
		return isset($this->ctx[$key]);
	}

	public function __unset($key) {
		unset($this->ctx[$key]);
	}

	public function __get($key) {
		if ($this->__isset($key)) {
			return $this->ctx[$key];
		}
		return null;
	}

	public function __set($key, $val) {
		$this->ctx[$key] = $val;
	}

	/** Removes the named value from the request context. */
	public function remove($key) {
		unset($this->ctx[$key]);
	}

	/** @return The root URL of the application. */
	public function application_root() {
		static $root = null;
		if (is_null($root)) {
			$path  = $this->REQUEST_URI;
			$len  = strlen($path) - strlen($this->PATH_INFO);
			if ($this->QUERY_STRING == '') {
				$len++;
			} else {
				$len -= strlen($this->QUERY_STRING);
			}
			$root = substr($path, 0, $len);
		}
		return $root;
	}

	/** @return The current request URI (without the query string.) */
	public function request_uri() {
		$path = $this->REQUEST_URI;
		if ($this->QUERY_STRING != '') {
			$path = substr($path, 0, strpos($path, '?'));
		}
		return $path;
	}

	/** @return The HTTP method used for this request. */
	public function method() {
		$method = strtolower($this->REQUEST_METHOD);
		if ($method == 'post') {
			$method = $this->_method ? $this->_method : $method;
		}
		return $method;
	}

	public function is_secure() {
		return isset($this->ctx['HTTPS']);
	}

	/**
	 * @param  $default  Default view name to use.
	 *
	 * @return The view to use for rendering this request.
	 */
	public function view($default='') {
		if (!is_null($this->_view)) {
			return $this->_view;
		}
		return $default;
	}

	/** Alters the view to be rendered later. */
	public function change_view($new) {
		$this->_old_view = $this->_view;
		$this->_view = $new;
	}

	/** @return The context as an array. */
	public function as_array() {
		return array_merge($this->ctx, array('ctx' => $this));
	}

	/** Toggles whether any rendering components should run. */
	public function allow_rendering($allow=true) {
		$this->allow_rendering = $allow;
	}

	public function rendering_is_allowed() {
		return $this->allow_rendering;
	}

	public function redirect($code=303, $to=null) {
		if ($code < 300 || $code > 307 || $code == 306 || $code == 304) {
			throw new AFK_Exception("Bad redirect code: $code");
		}
		// For backward compatibility, see RFC2616, SS10.3.4
		if ($code == 303 && $this->SERVER_PROTOCOL == 'HTTP/1.0') {
			$code = 302;
		}
		if (is_null($to)) {
			$to = $this->REQUEST_URI;
		}
		header('Location: ' . $this->to_absolute_uri($to), true, $code);
	}

	public function not_found($msg='') {
		throw new AFK_HttpException($msg, 404);
	}

	public function no_such_method($available_methods) {
		throw new AFK_HttpException('', 405, array('Allow' => $available_methods));
	}

	public function to_absolute_uri($path) {
		if ($path[0] == '/') {
			$prefix = ($this->is_secure() ? 'https' : 'http') . '://' . $this->HTTP_HOST;
			if ($this->is_secure() && $this->SERVER_PORT != 441 || !$this->is_secure() && $this->SERVER_PORT != 80) {
				$prefix .= ':' . $this->SERVER_PORT;
			}
			return $prefix . $path;
		}
		return $path;
	}

	public function set_response_code($code) {
		// For backward compatibility, see RFC2616, SS10.3.4
		if ($code == 303 && $this->SERVER_PROTOCOL == 'HTTP/1.0') {
			$code = 302;
		}
		if (array_search(array(204, 205, 407, 411, 413, 414, 415, 416, 417), $code) !== false) {
			$this->allow_rendering(false);
		}
		header("HTTP/1.1 $code " . $this->get_response_msg($code));
	}

	private function get_response_msg($code) {
		switch ($code) {
		// Informational.
		case 100: return 'Continue';
		case 101: return 'Switching Protocols';
		// Success.
		case 200: return 'OK';
		case 201: return 'Created';
		case 202: return 'Accepted';
		case 203: return 'Non-Authoritative Information';
		case 204: return 'No Content';
		case 205: return 'Reset Content';
		case 206: return 'Partial Content';
		// Redirection.
		case 300: return 'Multiple Choices';
		case 301: return 'Moved Permanently';
		case 302: return 'Found';
		case 303: return 'See Other';
		case 304: return 'Not Modified';
		case 305: return 'Use Proxy';
		case 307: return 'Temporary Redirect';
		// Client Error.
		case 400: return 'Bad Request';
		case 401: return 'Unauthorised';
		case 402: return 'Payment Required';
		case 403: return 'Forbidden';
		case 404: return 'Not Found';
		case 405: return 'Method Not Allowed';
		case 406: return 'Not Acceptable';
		case 407: return 'Proxy Authentication Required';
		case 408: return 'Request Timeout';
		case 409: return 'Conflict';
		case 410: return 'Gone';
		case 411: return 'Length Required';
		case 412: return 'Precondition Failed';
		case 413: return 'Request Entity Too Large';
		case 414: return 'Request-URI Too Long';
		case 415: return 'Unsupported Media Type';
		case 416: return 'Request Range Not Satisfiable';
		case 417: return 'Expectation Failed';
		// Server Error.
		case 500: return 'Internal Server Error';
		case 501: return 'Not Implemented';
		case 502: return 'Bad Gateway';
		case 503: return 'Service Unavailable';
		case 504: return 'Gateway Timeout';
		case 505: return 'HTTP Version Not Supported';
		}
		return '';
	}

	/**
	 * Creates a query string from the given array. If an array element has
	 * a textual key, that's used as the query string key and the value is
	 * used as the value. If the key is numeric, the value is used as the
	 * key and the value to associate with that key is taken from the request
	 * context.
	 */
	public function to_query($vars) {
		$result = '';
		foreach ($vars as $k=>$v) {
			if (is_numeric($k)) {
				$k = $v;
				$v = $this->ctx[$k];
			}
			if ($v != '') {
				if ($result != '') {
					$result .= '&';
				}
				$result .= rawurlencode($k) . '=' . rawurlencode($v);
			}
		}
		if ($result != '') {
			$result = '?' . $result;
		}
		return $result;
	}

	/** Default the named fields to empty strings. */
	public function default_to_empty() {
		$fields = func_get_args();
		foreach ($fields as $k) {
			$this->ctx[$k] = isset($this->ctx[$k]) ? trim($this->ctx[$k]) : '';
		}
	}

	/** Use the given defaults if the named fields aren't set. */
	public function defaults($defaults) {
		foreach ($defaults as $k=>$v) {
			if (!isset($this->ctx[$k])) {
				$this->ctx[$k] = $v;
			}
		}
	}

	public static function get() {
		if (!isset(self::$instance)) {
			self::$instance = new AFK_Context();
		}
		return self::$instance;
	}
}

/**
 *
 */
class AFK_DispatchFilter implements AFK_Filter {

	public function execute(AFK_Pipeline $pipe, AFK_Context $ctx) {
		set_error_handler(array('AFK_TrappedErrorException', 'convert_error'), E_ALL);

		try {
			if (is_null($ctx->_handler)) {
				throw new AFK_Exception('No handler specified.');
			}
			$handler_class = $ctx->_handler . 'Handler';
			if (!class_exists($handler_class)) {
				throw new AFK_Exception("No such handler: $handler_class");
			}
			$handler = new $handler_class();
			$handler->handle($ctx);
			$pipe->do_next($ctx);
		} catch (AFK_HttpException $he) {
			foreach ($he->get_headers() as $h) {
				header($h);
			}
			$ctx->message = $he->getMessage();
			$this->report_error($he->getCode(), $pipe, $ctx);
		} catch (Exception $e) {
			$this->render_error500($ctx, $e);
			$this->report_error(500, $pipe, $ctx);
		}
	}

	private function render_error($code, AFK_Pipeline $pipe, AFK_Context $ctx) {
		// Assuming the last pipeline element is a rendering filter.
		// Not entirely happy with this.
		$pipe->to_end();
		$this->change_view('error' . $code);
		$ctx->set_response_code($code);
	}

	private function render_error500(AFK_Context $ctx, $ex) {
		$traceback = array();
		$traceback[] = $this->make_traceback_frame($ex->getFile(), $ex->getLine());
		foreach ($ex->getTrace() as $f) {
			if (!empty($f['file']) && !$this->should_ignore($f)) {
				 $traceback[] = $this->make_traceback_frame(
					$f['file'], $f['line'],
					$this->frame_to_name($f). '()');
			}
		}

		$ctx->page_title = get_class($ex) . ' in ' .
			$this->truncate_filename($ex->getFile());
		$ctx->message = $ex->getMessage();
		$ctx->traceback = $traceback;
	}

	private function make_traceback_frame($file, $line, $function='') {
		return array(
			'file'    => $this->truncate_filename($file),
			'line'    => $line,
			'context' => $this->get_context_lines($file, $line),
			'method'  => $function);
	}

	/* Converts an exception trace frame to a function/method name. */
	private function frame_to_name($frame) {
		$name = '';
		if (isset($frame['class'])) {
			$name .= $frame['class'] . $frame['type'];
		}
		$name .= $frame['function'];
		return $name;
	}

	private function get_context_lines($file, $line_no, $amount=3) {
		$context = array();
		$lines = file($file);
		for ($i = $line_no - $amount; $i <= $line_no + $amount; $i++) {
			if ($i >= 0 && isset($lines[$i - 1])) {
				$context[$i] = rtrim($lines[$i - 1]);
			}
		}
		return $context;
	}

	/* Truncates an application/library filename. */
	private function truncate_filename($filename) {
		if (substr($filename, 0, strlen(AFK_ROOT)) == AFK_ROOT) {
			return 'AFK:' . substr($filename, strlen(AFK_ROOT) + 1);
		}
		if (defined('APP_ROOT') && substr($filename, 0, strlen(APP_ROOT)) == APP_ROOT) {
			return substr($filename, strlen(APP_ROOT) + 1);
		}
		return $filename;
	}

	/* */
	private function should_ignore($f) {
		static $methods_to_ignore = array(
			'AFK_Pipeline'       => array('start', 'do_next'),
		//	'AFK_Filter'         => array('execute'),
			'AFK_TemplateEngine' => array('internal_render'));
		static $functions_to_ignore = array('require');
		if (isset($f['class'])) {
			foreach ($methods_to_ignore as $class=>$methods) {
				if ($class == $f['class'] || is_subclass_of($class, $f['class'])) {
					return array_search($f['function'], $methods) !== false;
				}
			}
			return false;
		}
		return array_search($f['function'], $functions_to_ignore) !== false;
	}
}

/**
 * Common logic for AFK-specific exceptions.
 */
class AFK_Exception extends Exception {

	public function __construct($msg, $code=0) {
		parent::__construct($msg, $code);
	}

	public function __toString() {
		return get_class($this) . ": [{$this->code}]: {$this->message}\n";
	}
}

/**
 * Implement this if you're creating a request filter.
 */
interface AFK_Filter {

	/**
	 * Executes the action represented by this filter.
	 *
	 * @param  $pipe  The current request pipeline.
	 * @param  $ctx   The current request context.
	 */
	function execute(AFK_Pipeline $pipe, AFK_Context $ctx);
}

/**
 * Represents a HTTP request handler.
 */
interface AFK_Handler {

	/** Handles a HTTP request. */
	function handle(AFK_Context $ctx);
}

/**
 * Basic functionality common to all handlers.
 */
class AFK_HandlerBase implements AFK_Handler {

	public function handle(AFK_Context $ctx) {
		$method = $this->get_handler_method($ctx->method(), $ctx->view());
		if ($method != '') {
			call_user_func(array($this, $method), $ctx);
		} else {
			$methods = $this->get_available_methods($ctx->view());
			if (count($methods) == 0) {
				$ctx->not_found();
			} else {
				$ctx->no_such_method($methods);
			}
		}
	}

	private function get_handler_method($method, $view) {
		$suffix = empty($view) ? '' : "_$view";
		if (method_exists($this, "on_$method$suffix")) {
			return "on_$method$suffix";
		}
		if ($method === 'head') {
			return $this->get_handler_method('get', $view);
		}
		if ($view != '') {
			return $this->get_handler_method($method, '');
		}
		return '';
	}

	private function get_available_methods($view) {
		static $allowed = array();
		if (count($allowed) == 0) {
			$methods = get_class_methods(get_class($this));
			$allowed = array();
			foreach ($methods as $m) {
				$parts = explode('_', $m, 3);
				if ($parts[0] == 'on' && count($parts) > 1) {
					$m_view = isset($parts[2]) ? $parts[2] : '';
					if ($m_view == '' || $m_view == $view) {
						$allowed[] = strtoupper($parts[1]);
					}
				}
			}
			$allowed = array_unique($allowed);
		}
		return $allowed;
	}

	protected function on_options(AFK_Context $ctx) {
		header('Allow: ' . implode(', ', $this->get_available_methods($ctx->view())));
		$ctx->allow_rendering(false);
	}
}

class AFK_HttpException extends AFK_Exception {

	private $headers = array();

	public function __construct($msg, $code, $headers=array()) {
		parent::__construct($msg, $code);
		$this->add_headers($headers);
	}

	public function add_headers($headers) {
		foreach ($headers as $n=>$v) {
			if (is_array($v)) {
				$v = implode(', ', $v);
			}
			$v = str_replace("\n", "\t\r\n", $v);
			if (isset($this->headers[$n])) {
				$this->headers[$n] .= ', ' . $v;
			} else {
				$this->headers[$n] = $v;
			}
		}
	}

	public function get_headers() {
		$result = array();
		foreach ($this->headers as $n=>$v) {
			$result[] = "$n: $v";
		}
		return $result;
	}
}

/**
 * A request processing pipeline.
 */
class AFK_Pipeline {

	private $filters = array();

	public function add(AFK_Filter $filter) {
		$this->filters[] = $filter;
		return $this;
	}

	public function start() {
		reset($this->filters);
		$this->do_next(AFK_Context::get());
	}

	public function do_next(AFK_Context $ctx) {
		$filter = current($this->filters);
		if (is_object($filter)) {
			next($this->filters);
			$filter->execute($this, $ctx);
		}
	}

	public function to_end() {
		end($this->filters);
	}
}

class AFK_NotificationMessage {

	public $field;
	public $msg;

	public function __construct($field, $msg) {
		$this->field = $field;
		$this->msg = $msg;
	}
}

class AFK_Notification {

	const REQUIRED = 'Required field';
	const INVALID  = 'Invalid format';

	private $msgs = array();

	public function is_valid() {
		return count($this->msgs) == 0;
	}

	public function add($field, $msg) {
		$this->msgs[] = new AFK_NotificationMessage($field, $msg);
	}

	public function get_messages($field) {
		$msgs = array();
		foreach ($this->msgs as $n) {
			if ($n->field == $field) {
				$msgs[] = $n->msg;
			}
		}
		return $msgs;
	}

	public function get_all() {
		usort($this->msgs, array($this, 'comparator'));
		// Assume the user won't be a fool and modify it.
		return $this->msgs;
	}

	private function comparator($a, $b) {
		$result = strcmp($a->field, $b->field);
		if ($result == 0) {
			$result = strcasecmp($a->msg, $b->msg);
		}
		return $result;
	}
}

/**
 * Renders the request, if it can.
 */
class AFK_RenderFilter implements AFK_Filter {

	public function execute(AFK_Pipeline $pipe, AFK_Context $ctx) {
		if ($ctx->rendering_is_allowed()) {
			if (defined('APP_TEMPLATE_ROOT')) {
				AFK_TemplateEngine::add_paths(
					APP_TEMPLATE_ROOT,
					APP_TEMPLATE_ROOT . '/' . strtolower($ctx->_handler));
			}
			$t = new AFK_TemplateEngine();
			try {
				ob_start();
				$ctx->defaults(array('page_title' => ''));
				$t->render($ctx->view('default'), $ctx->as_array());
				$pipe->do_next($ctx);
				ob_end_flush();
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}
	}
}

/**
 * Parses and routes the current request URL.
 */
class AFK_RouteFilter implements AFK_Filter {

	private $server;
	private $routes;
	private $request;

	/**
	 * @param  $routes   Routes to use when parsing the request.
	 * @param  $server   Server variables to use. These have the highest
	 *                   priority and are added to the request context
	 *                   first. In production, this will be $_SERVER.
	 * @param  $request  The request variables to use. These have the lowest
	 *                   priority and will be added to the request context
	 *                   after the request has been routed. In production,
	 *                   this will be $_REQUEST.
	 */
	public function __construct(AFK_Routes $routes, $server, $request) {
		$this->routes = $routes;
		$this->server = $server;
		$this->request = $request;
	}

	public function execute(AFK_Pipeline $pipe, AFK_Context $ctx) {
		$ctx->merge($this->server);
		if ($this->ensure_canonicalised_uri($ctx)) {
			$result = $this->routes->search($ctx->PATH_INFO);
			if (is_array($result)) {
				// The result is the attributes.
				$ctx->merge($this->server, $result, $this->request);
				$pipe->do_next($ctx);
			} else {
				// Result is a normalised URL. The original request URL was
				// most likely missing a trailing slash or had one it
				// shouldn't have had.
				$path = $ctx->application_root() . $result;
				if ($ctx->QUERY_STRING != '') {
					$path .= '?' . $ctx->QUERY_STRING;
				}
				$this->permanent_redirect($path);
			}
		}
	}

	/* Ensures the request URI doesn't contain double-slashes. */
	private function ensure_canonicalised_uri($ctx) {
		$canon = preg_replace('~(/)/+~', '$1', $ctx->REQUEST_URI);
		if ($canon !== $ctx->REQUEST_URI) {
			$this->permanent_redirect($canon);
			return false;
		}
		return true;
	}

	private function permanent_redirect($path) {
		// I don't really think this belongs here.
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $path");
	}
}

/**
 * Thrown if a malformed route is given.
 */
class AFK_RouteSyntaxException extends AFK_Exception { }

/**
 * URL parsing and routing.
 */
class AFK_Routes {

	/** Maps routes to handlers. */
	private $routes = array();

	/** Fallback handler. */
	private $fallback = null;

	public function __construct() {
	}

	/** If none of the routes match, this resource handler is used. */
	public function fallback($defaults) {
		$this->fallback = $defaults;
		return $this;
	}

	/** Adds a route and an associated resource handler. */
	public function route($route, $defaults=array(), $patterns=array()) {
		list($regex, $keys) = $this->compile_route($route, $patterns);
		$this->routes[$regex] = array($keys, $defaults);
		return $this;
	}

	/** Finds the first route that matches the given path. */
	public function search($path) {
		$result = $this->internal_search($path);
		if ($result !== false) {
			return $result;
		}

		// Tweak it to remove or append a trailing slash.
		$path = substr($path, -1) == '/' ? substr($path, 0, -1) : "$path/";
		foreach ($this->routes as $regex=>$_) {
			if ($this->match($regex, $path)) {
				return $path;
			}
		}

		return $this->fallback;
	}

	private function internal_search($path) {
		foreach ($this->routes as $regex=>$v) {
			// This is only needed because PHP's parser is too dumb to allow
			// the use for list() in the foreach.
			list($keys, $defaults) = $v;

			$values = $this->match($regex, $path);
			if ($values !== false) {
				return array_merge($defaults, $this->combine($keys, $values));
			}
		}
		return false;
	}

	private function combine($keys, $values) {
		if (count($keys) > 0) {
			return array_combine($keys, $values);
		}
		return array();
	}

	/**
	 * Checks if a given route regex matches a path.
	 *
	 * @return false if it doesn't, otherwise an array of extracted values.
	 */
	private function match($regex, $path) {
		$values = array();
		if (preg_match('`^' . $regex . '$`', $path, $values)) {
			// We only want the parts parsed out, not the whole string.
			array_shift($values);
			return $values;
		}
		return false;
	}

	/** Compiles a route into a regex and an array of placeholder keys. */
	private function compile_route($route, $patterns) {
		// Construct a regular expression to match this route. This method is
		// meant to be simple, not to be fast. If speed becomes necessary, it
		// can probably be improved using some complex nonsense with
		// preg_replace_callback().
		// Idea: compile the routes, then serialise them somewhere, possibly
		// a file. Will need some caching mechanism first and some way of
		// figuring out if the routes have changed.
		$keys  = array();
		$regex = '';
		$parts = explode('{', $route);
		foreach ($parts as $i=>$part) {
			$matches = array();
			if (preg_match('/^(?:([a-z_]+)})?([^a-z_]?.*)$/i', $part, $matches)) {
				if (strlen($matches[1]) == 0) {
					// No placeholder: most likely the first segment.
					$regex .= preg_quote($matches[0], '`');
				} else {
					$keys[] = $matches[1];
					$regex .= $this->to_pattern($matches[1], $matches[2], $patterns);
				}
			} else {
				// Malformed route!
				throw new AFK_RouteSyntaxException($route);
			}
		}
		return array($regex, $keys);
	}

	private function to_pattern($name, $trailer, $patterns) {
		if (isset($patterns[$name])) {
			$p = $patterns[$name];
			if (is_array($p)) {
				$p = implode('|', array_map(array($this, 'quote'), $p));
			}
		} elseif ($trailer == '') {
			$p = '.+';
		} else {
			$p = '[^' . $this->escape_class_character($trailer[0]) . ']+';
		}
		return "($p)" . preg_quote($trailer, '`');
	}

	/** Escapes a character if it has a special meaning in a character class. */
	private function escape_class_character($c) {
		if (strpos("/^-]\\", $c) !== false) {
			return "\\$c";
		}
		return $c;
	}

	private function quote($s) {
		return preg_quote($s, '`');
	}

	/** Replaces any attribute placeholders in the given URI. */
	public function expand_uri_template($template, $params=array()) {
		$quoted = array_map('rawurlencode', $params);
		return preg_replace('/:([a-z_]+)/ie', '$quoted[\'\\1\']', $template);
	}
}

abstract class AFK_Session {

	public function __construct() {
		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc'));
	}

	public abstract function open($save_path, $name);

	public function close() {
		return true;
	}

	public abstract function read($id);

	public abstract function write($id, $data);

	public abstract function destroy($id);

	public abstract function gc($max_age);
}

/**
 * A port to AFK of the new, cleaner session handler I wrote for Tempus Wiki.
 *
 * To create the appropriate, you'll need to run something like the following:
 * 
 * CREATE TABLE sessions (
 *     id   CHAR(32) NOT NULL,
 *     name CHAR(16) NOT NULL,
 *     ts   INTEGER  NOT NULL,
 *     data TEXT     NOT NULL,
 * 
 *     PRIMARY KEY (id),
 *     INDEX ix_timestamp (ts)
 * );
 *
 * I'm pretty sure the table schema and the class itself should work on just
 * about every RDBMS out there.
 *
 * You will need an implementation of DB_Base to get this to work.
 */
class AFK_Session_DB extends AFK_Session {

	private $dbh;
	private $name;
	private $table;

	public function __construct(DB_Base $dbh, $table='sessions') {
		parent::__construct();
		$this->dbh = $dbh;
		$this->table = $table;
	}

	public function open($save_path, $name) {
		$this->name = $name;
		return true;
	}

	public function read($id) {
		$data = $this->dbh->query_value("
			SELECT	data
			FROM	{$this->table}
			WHERE	name = %s AND id = %s", $this->name, $id);
		return is_null($data) ? '' : $data;
	}

	public function write($id, $data) {
		$n = $this->dbh->query_value("
			SELECT	COUNT(*)
			FROM	{$this->table}
			WHERE	name = %s AND id = %s", $this->name, $id);
		if ($n == 0) {
			$query = "INSERT INTO {$this->table} (data, ts, name, id) VALUES (%s, %d, %s, %s)";
		} else {
			$query = "UPDATE {$this->table} SET data = %s, ts = %d WHERE name = %s AND id = %s";
		}
		$this->dbh->execute($query, $data, time(), $this->name, $id);
		return true;
	}

	public function destroy($id) {
		$this->dbh->execute("
			DELETE
			FROM	{$this->table}
			WHERE	name = %s AND id = %s", $this->name, $id);
		return true;
	}

	public function gc($max_age) {
		$this->dbh->execute("
			DELETE
			FROM	{$this->table}
			WHERE	ts < %d", time() - $max_age);
		return true;
	}
}

/** */
class AFK_SlotException extends AFK_Exception { }

/**
 * A slot is a placeholder whose content can be generated in one place and
 * output in a completely different place elsewhere.
 */ 
class AFK_Slots {

	/* Stuff for handling slots. */
	private $current = null;
	private $slots = array();

	/** Checks if the named slot has content. */
	public function has($slot) {
		return isset($this->slots[$slot]);
	}

	/** Writes out the content in the given slot. */
	public function get($slot, $default='') {
		echo $this->has($slot) ? $this->slots[$slot] : $default;
	}

	/** Sets the contents of the given slot. */
	public function set($slot, $contents) {
		$this->slots[$slot] = $contents;
	}

	/** Appends content to the given slot. */
	public function append($slot, $contents) {
		$this->slots[$slot] .= $contents;
	}

	/**
	 * Delimit the start of a block of code which will generate content for
	 * the given slot.
	 */
	public function start($slot) {
		if (!is_null($this->current)) {
			throw new AFK_SlotException("Cannot start new slot '$slot': already in slot '{$this->current}'.");
		}
		$this->current = $slot;
		ob_start();
		ob_implicit_flush(false);
	}

	/**
	 * Delimits the end of a block started with ::start().
	 */
	public function end() {
		if (is_null($this->current)) {
			throw new AFK_SlotException("Attempt to end a slot while not in a slot.");
		}
		$this->set($this->current, ob_get_contents());
		ob_end_clean();
		$this->current = null;
	}

	/**
	 * Like ::end(), but the delimited content is appended to whatever's
	 * already in the slot.
	 */
	public function end_append() {
		if (is_null($this->current)) {
			throw new AFK_SlotException("Attempt to end a slot while not in a slot.");
		}
		$this->append($this->current, ob_get_contents());
		ob_end_clean();
		$this->current = null;
	}
}

/** 
 * A flexible yet compact template rendering system.
 */
class AFK_TemplateEngine {

	/* Paths to use when searching for templates. */
	private static $paths = array();
	/* Cached template locations. */
	private static $locations = array();

	/* Envelope stack for the current rendering contexts. */
	private $envelopes = array();

	private $current_template = '';

	private $context = array();

	/** Renders the named template. */
	public function render($name, $values=array()) {
		$this->start_rendering_context($name);
		$this->internal_render($this->find($name), $values);
		$this->end_rendering_context($values);
	}

	/**
	 * Renders a bunch of rows, cycling through a list of named templates; if
	 * there are no rows and a default template is given, that's rendered
	 * instead.
	 */
	public function render_each($names, &$rows, $default=null) {
		if (!empty($rows)) {
			if (!is_array($names)) {
				$names = array($names);
			}
			if (count($names) == 0) {
				throw new AFK_TemplateException('You must specify at least one template for ::render_each()');
			}

			$paths = array_map(array($this, 'find'), $names);

			$this->start_rendering_context($names[0]);

			$row_count = count($rows);
			$current_row = 0;
			foreach ($rows as $r) {
				$r = array_merge($r, compact('row_count', 'current_row'));
				$this->internal_render($paths[$current_row % count($paths)], $r);
				$current_row++;
			}

			$values = compact('row_count');
			$this->end_rendering_context($values);
		} elseif (!is_null($default)) {
			$this->render($default);
		}
	}

	/** As ::render(), but the result is returned rather than echoed. */
	public function buffered_render($name, $values=array()) {
		ob_start();
		ob_implicit_flush(false);
		$this->render($name, $values);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/** As ::render_each(), but the result is returned rather than echoed. */
	public function buffered_render_each($names, &$rows, $default=null) {
		ob_start();
		ob_implicit_flush(false);
		$this->render_each($names, $rows, $default);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/* Creates a dedicated scope for rendering a PHP template. */
	private function internal_render($__path, &$__values) {
		array_unshift($this->context, $__values);
		try {
			foreach ($this->context as $__c) {
				extract($__c, EXTR_SKIP);
			}
			require($__path);
		} catch (Exception $__e) {
			// Prevent envelopes from inadvertantly wrapping the error.
			for ($i = 0; $i < count($this->envelopes); $i++) {
				ob_end_clean();
			}
			$this->envelopes = array();
			throw $__e;
		}
		array_shift($this->context);
	}

	/* Prepares the current template rendering context. */
	private function start_rendering_context($name) {
		$this->current_template = $name;
		$this->envelopes[] = null;
	}

	/* Concludes the current template rendering context, possibly wrapping it
	 * with an enveloping template if one's been specified. */
	private function end_rendering_context(&$values) {
		$envelope = array_pop($this->envelopes);
		if (!is_null($envelope)) {
			$values['generated_content'] = ob_get_contents();
			ob_end_clean();
			$this->render($envelope . '.envelope', $values);
		}
	}

	/**
	 * Specifies the enveloping template to use with the current rendering
	 * context. Only call this within a template!
	 */
	protected function with_envelope($name=null) {
		if (is_null($name)) {
			$name = $this->current_template;
		}
		$end = count($this->envelopes) - 1;
		if ($end < 0) {
			throw new AFK_TemplateException(
				"Um, ::with_envelope() can't be called outside of a template rendering context.");
		} elseif (is_null($this->envelopes[$end])) {
			if ($this->internal_find($name . '.envelope') === false) {
				$name = 'default';
			}
			$this->envelopes[$end] = $name;
			ob_start();
			ob_implicit_flush(false);
		} elseif ($name != $this->envelopes[$end]) {
			throw new AFK_TemplateException("Attempt to replace an envelope: $name");
		}
	}

	/**
	 * Adds a number of template search directory paths in the order they'll
	 * be searched.
	 */
	public static function add_paths() {
		$paths = func_get_args();
		$paths = array_reverse($paths);
		foreach ($paths as $path) {
			array_unshift(self::$paths, $path);
		}
	}

	/* Searches the template directories for a named template. */
	protected function find($name, $fallback=null) {
		$location = $this->internal_find($name);
		if ($location === false) {
			throw new AFK_TemplateException("Unknown template: $name");
		}
		return $location;
	}

	private function internal_find($name) {
		if (isset(self::$locations[$name])) {
			return self::$locations[$name];
		}
		if (count(self::$paths) == 0) {
			throw new AFK_TemplateException('No template search paths specified!');
		}
		foreach (self::$paths as $d) {
			if (file_exists("$d/$name.php")) {
				self::$locations[$name] = "$d/$name.php";
				return "$d/$name.php";
			}
		}
		return false;
	}
}

/**
 * Thrown if something goes wrong in the process of rendering a template.
 */
class AFK_TemplateException extends AFK_Exception { }

/**
 * Wraps a PHP error that's been converted to an exception.
 */
class AFK_TrappedErrorException extends AFK_Exception {

	protected $ctx;

	public function __construct($msg, $code, $file, $line, $ctx) {
		parent::__construct($msg, $code);
		$this->file = $file;
		$this->line = $line;
		$this->ctx  = $ctx;
	}

	public static function convert_error($errno, $errstr, $errfile, $errline, $ctx) {
		throw new AFK_TrappedErrorException($errstr, $errno, $errfile, $errline, $ctx);
	}
}

// ---------------------------------------------------------- Convenience --

/**
 * A simple fallback handler so authors won't have to write their own.
 */
class AFK_DefaultHandler extends AFK_HandlerBase {

	public function on_get(AFK_Context $ctx) {
		header('HTTP/1.1 404 Not Found');
		$ctx->change_view('error404');
		$ctx->page_title = 'You are wandering through a maze of tunnels, all alike...';
	}
}

// ------------------------------------------------------- Object Caching --

/**
 * Implement this interface to add extra caching mechanisms for use with
 * AFK.
 */
interface Cache {

	/**
	 * Invalidates the given item, removing it from the cache.
	 *
	 * @param  $id  Id of the cached item.
	 */
	function invalidate($id);

	/**
	 * Invalidates the whole cache.
	 */
	function invalidate_all($max_age=0);

	/**
	 * Load the item with the given key.
	 *
	 * @param  $id       ID of the cached item.
	 * @param  $max_age  Seconds to cache the item for. Defaults to 5 minutes.
	 * @return A reference to the cached item, or null if none found.
	 */
	function load($id, $max_age=300);

	/**
	 * Saves the given item to the cache.
	 * 
	 * @param  $id    ID of the cached item.
	 * @param  $item  The item to be cached.
	 */
	function save($id, $item);
}

/**
 * A basic caching mechanism that's not meant to persist over multiple pages.
 *
 * This cache is most useful in cases where you are testing something that
 * requires a caching mechanism or where you need a default one that actually
 * works, i.e., where Cache_Null is insufficient. This caching mechanism is
 * not, however, meant to be used in production code.
 *
 * Please note that if you are excercising something which uses caching, it
 * ought to be tested using both Cache_Array and Cache_Null as both implement
 * different elements of the Cache interface contract that code using caches
 * ought to expect.
 */
class Cache_Array implements Cache {

	private $cache = array();
	private $timestamps = array();

	public function invalidate($id) {
		unset($this->cache[$id], $this->timestamps[$id]);
	}

	public function invalidate_all($max_age=0) {
		$now = time();
		foreach ($this->timestamps as $id=>$ts) {
			if ($ts + $max_age <= $now) {
				$this->invalidate($id);
			}
		}
	}

	public function load($id, $max_age=300) {
		if (isset($this->cache[$id]) && $this->timestamps[$id] + $max_age > time()) {
			return $this->cache[$id];
		}
		return null;
	}

	public function save($id, $item) {
		$this->cache[$id] = $item;
		$this->timestamps[$id] = time();
	}
}

/**
 * To create the appropriate, you'll need to run something like the following:
 * 
 * CREATE TABLE cache (
 *     id   CHAR(32) NOT NULL,
 *     ts   INTEGER  NOT NULL,
 *     data TEXT     NOT NULL,
 * 
 *     PRIMARY KEY (id),
 *     INDEX ix_timestamp (ts)
 * );
 *
 * I'm pretty sure the table schema and the class itself should work on just
 * about every RDBMS out there.
 *
 * You will need an implementation of DB_Base to get this to work.
 */
class Cache_DB implements Cache {

	private $dbh;
	private $table;

	public function __construct(DB_Base $dbh, $table='cache') {
		$this->dbh = $dbh;
		$this->table = $table;
	}

	public function invalidate($id) {
		$this->dbh->execute("DELETE FROM {$this->table} WHERE id = %s", md5($id));
	}

	public function invalidate_all($max_age=0) {
		$this->dbh->execute("DELETE FROM {$this->table} WHERE ts < %d", time() - $max_age);
	}

	public function load($id, $max_age=300) {
		$data = $this->dbh->query_value("
			SELECT	data
			FROM	{$this->table}
			WHERE	id = %s AND ts > %d", md5($id), time() - $max_age);

		if (!is_null($data)) {
			return unserialize($data);
		}
		return null;
	}

	public function save($id, $item) {
		$hash = md5($id);

		$item_exists = $this->dbh->query_value("
			SELECT	COUNT(*)
			FROM	{$this->table}
			WHERE	id = %s", $hash) != 0;

		if ($item_exists) {
			$query = "UPDATE {$this->table} SET data = %s, ts = %d WHERE id = %s";
		} else {
			$query = "INSERT INTO {$this->table} (data, ts, id) VALUES (%s, %d, %s)";
		}
		$this->dbh->execute($query, serialize($item), time(), $hash);
	}
}

/**
 * Simple file-based cache.
 */
class Cache_File implements Cache {

	private $cache_path;

	/**
	 * Initialises the class.
	 *
	 * @param  $cache_path  Path to the cache directory. Must end in a slash.
	 */
	public function __construct($cache_path) {
		$this->cache_path = $cache_path;
	}

	public function invalidate($id) {
		$path = $this->cache_path . md5($id);
		if (is_file($path)) {
			unlink($path);
		}
	}

	public function invalidate_all($max_age=0) {
		$dh = dir($this->cache_path);
		while ($file = $dh->read()) {
			if (filemtime($this->cache_path . $file) < time() - $max_age) {
				unlink($this->cache_path . $file);
			}
		}
		$dh->close();
	}

	public function load($id, $max_age=300) {
		$path = $this->cache_path . md5($id);
		if (is_file($path)) {
			if (filemtime($path) >= time() - $max_age) {
				return unserialize(file_get_contents($path));
			}
			unlink($path);
		}
		return null;
	}

	public function save($id, $item) {
		file_put_contents($this->cache_path . md5($id), serialize($item), LOCK_EX);
    }
}

/**
 * A null cache. Doesn't actually cache anything but instead always responds
 * that the item wasn't found.
 *
 * This cache is useful as a default one for use in development environments.
 *
 * Please note that if you're exercising something which uses caching, it
 * ought to be tested using both Cache_Array and Cache_Null as both implement
 * different elements of the Cache interface contract that code using caches
 * ought to expect.
 */
class Cache_Null implements Cache {

	public function invalidate($id) {
		// Do nothing.
	}

	public function invalidate_all($max_age=0) {
		// Do nothing.
	}

	public function load($id, $max_age=300) {
		// Do nothing.
		return null;
	}

	public function save($id, $item) {
		// Do nothing.
    }
}

// --------------------------------------------------- Database Interface --

define('DB_ASSOC', 0);
define('DB_NUM',   1);

/**
 * Wrapper around the various DB drivers to abstract away various repetitive
 * work.
 */
abstract class DB_Base {

	private $logger = null;

	public function set_logger($logger) {
		$this->logger = $logger;
	}

	public function get_logger() {
		return $this->logger;
	}

	/**
	 * Close the database connection.
	 */
	abstract public function close();

	abstract public function is_connected();

	/**
	 * Executes a update query of some kind against the database currently
	 * connected to. This class implements a kind of poor man's prepared
	 * statements. If you provide just a single argument--the query--it is
	 * sent to the DB as-is. If you provide more than one, the query is taken
	 * to be a template to be passed to the compose method. If the query runs
	 * successfully, it returns the last insert id for INSERT statements, the
	 * number of rows affected if it ran successfully, otherwise false if it
	 * didn't.
	 */
	abstract public function execute();

	/**
	 * Run a query against the database currently connected to. This class
	 * implements a kind of poor man's prepared statements. If you provide
	 * just a single argument--the query--it is sent to the DB as-is. If you
	 * provide more than one, the query is taken to be a template to be passed
	 * to the compose method. It returns true if the query runs successfully, 
	 * and false if it didn't.
	 */
	abstract public function query();

	/**
	 * Fetch the next tuple in the current resultset as an associative array.
	 */
	public function fetch($type=DB_ASSOC, $free_now=false) {
		return false;
	}

	/**
	 * Queries the database and returns the first matching tuple. Returns
	 * false if there was no match.
	 */
	public function query_row() {
		$args = func_get_args();
		if (call_user_func_array(array($this, 'query'), $args) &&
				($r = $this->fetch(DB_ASSOC, true))) {
			return $r;
		} 
		return false;
	}

	public function query_tuple() {
		$args = func_get_args();
		if (call_user_func_array(array($this, 'query'), $args) &&
				($r = $this->fetch(DB_NUM, true))) {
			return $r;
		} 
		return false;
	}

	/**
	 * Queries the database and return the first value in the first matching
	 * tuple. Returns null if there was no match.
	 */
	public function query_value() {
		$args = func_get_args();
		if (call_user_func_array(array($this, 'query'), $args) &&
				($r = $this->fetch(DB_NUM, true))) {
			return $r[0];
		}
		return null;
	}

	/**
	 * Like query_value(), but operates over the whole resultset, pulling the
	 * first value of each tuple into an array.
	 */
	public function query_list() {
		$args = func_get_args();
		$result = array();
		if (call_user_func_array(array($this, 'query'), $args)) {
			while ($r = $this->fetch(DB_NUM)) {
				$result[] = $r[0];
			}
		}
		return $result;
	}

	/**
	 * Returns an associative array derived from a query's two-column
	 * resultset. The first column in each row is used as the key and
	 * the second as the value the key maps to.
	 */
	public function query_map() {
		$args = func_get_args();
		$result = array();
		if (call_user_func_array(array($this, 'query'), $args)) {
			while ($r = $this->fetch(DB_NUM)) {
				$result[$r[0]] = $r[1];
			}
		}
		return $result;
	}

	/**
	 * Convenience method to query the database and convert the resultset into
	 * an array.
	 */
	public function query_all() {
		$args = func_get_args();
		call_user_func_array(array($this, 'query'), $args);
		$rows = array();
		while ($r = $this->fetch()) {
			$rows[] = $r;
		}
		return $rows;
	}

	/**
	 * Convenience method for starting a transaction.
	 */
	public function begin() {
		return  $this->execute('BEGIN');
	}

	/**
	 * Convenience method for committing a transaction.
	 */
	public function commit() {
		return $this->execute('COMMIT');
	}

	/**
	 * Convenience method for rolling back a transaction.
	 */
	public function rollback() {
		return $this->execute('ROLLBACK');
	}

	/**
	 * Convenience method for doing inserts.
	 *
	 * @param  $table  Name of table to do the insert on.
	 * @param  $data   Associative array with column names for keys and the
	 *                 values to insert on those columns as values.
	 *
	 * @return Last insert ID.
	 */
	public function insert($table, $data) {
		if (count($data) == 0) {
			return false;
		}
		$keys   = implode(', ',   array_keys($data));
		$values = implode("', '", array_map(array($this, 'e'), array_values($data)));
		return $this->execute("INSERT INTO $table ($keys) VALUES ('$values')");
	}

	/**
	 *
	 */
	public function update($table, $data, $qualifiers=array()) {
		if (count($a) == 0) {
			return false;
		}

		$is_first = true;
		$sql = "UPDATE $table SET ";
		foreach ($data as $f=>$v) {
			if (!$is_first) {
				$sql .= ', ';
			} else {
				$is_first = false;
			}
			$sql .= $f . ' = ' . $this->make_safe($v);
		}

		if (count($qualifiers) > 0) {
			$sql .= ' WHERE ';
			$is_first = true;
			foreach ($qualifiers as $f=>$qual) {
				if (!$is_first) {
					$sql .= ' AND ';
				} else {
					$is_first = false;
				}
				$sql .= $f . $qual['op'] . $this->make_safe($qual['value']);
			}
		}

		return $this->execute($sql);
	}

	/**
	 * Escapes a string in a driver dependent manner to make it safe to use
	 * in queries.
	 */
	protected function e($s) {
		// Better than nothing.
		return addslashes($s);
	}

	/** Allows query errors to be logged or echoed to the user. */
	protected function report_error($query='') {
		throw new DB_Exception($this->get_last_error(), $query);
	}

	/** Returns the last error known to the underlying database driver. */
	public abstract function get_last_error();

	/**
	 * The poor man's prepared statements. The first argument is an SQL query
	 * and the rest are a set of arguments to embed in it. The arguments are
	 * converted to forms safe for use in a query. It's advised that you use
	 * %s for your placeholders. Also not that if you pass in an array, it is
	 * flattened and converted into a comma-separated list (this is for
	 * convenience's sake when working with ranged queries, i.e., those that
	 * use the IN operator) and objects passed in are serialised.
	 */
	protected function compose($q, $args) {
		return vsprintf($q, array_map(array($this, 'make_safe'), $args));
	}

	private function make_safe($v) {
		if (is_array($v)) {
			// The nice thing about this is that it will flatten
			// multidimensional arrays.
			return implode(', ', array_map(array($this, 'make_safe'), $v));
		}
		if (is_object($v)) {
			return "'" . $this->e(serialize($v)) . "'";
		}
		if (!is_numeric($v)) {
			return "'" . $this->e($v) . "'";
		}
		return $v;
	}

	protected function log_query($q) {
		if (!is_null($this->logger)) {
			$this->logger->log($q);
		}
	}
}

/**
 * A logger the counts how many queries have been ran.
 */
class DB_BasicLogger {

	private $logged = 0;

	public function log($q) {
		$this->logged++;
	}

	public function logged() {
		return $this->logged;
	}
}

/**
 * a logger that echos any queries to standard output for debugging purposes.
 */
class DB_EchoingLogger extends DB_BasicLogger {

	public function log($q) {
		parent::log($q);
		printf('<pre class="log">%s</pre>', e($q));
	}
}

class DB_Exception extends Exception {

	private $q;

	public function __construct($msg, $q=null) {
		parent::__construct($msg, 0);
		$this->q = $q;
	}

	public function __toString() {
		$msg = get_class($this) . ": {$this->message}\n";
		if (!empty($this->q)) {
			$msg .= $this->q . "\n";
		}
		return $msg;
	}
}

/**
 * An implementation of DB_Base for MySQL.
 */
class DB_MySQL extends DB_Base {

	private $dbh = false;
	private $rs = false;

	/**
	 *
	 */
	public function __construct($host, $user, $pass, $db) {
		$this->dbh = mysql_connect($host, $user, $pass);
		if ($this->dbh) {
			if (version_compare(mysql_get_server_info($this->dbh), '4.1.0', '>=')) {
				$charset = defined('DB_CHARSET') ? constant('DB_CHARSET') : 'UTF8';
				$this->execute("SET NAMES $charset");
			}
			mysql_select_db($db, $this->dbh);
		} else {
			throw new DB_Exception('Could not connect to database.');
		}
	}

	public function close() {
		if ($this->dbh) {
			mysql_close($this->dbh);
			$this->dbh = false;
			$this->rs = false;
		}
	}

	public function is_connected() {
		return $this->dbh !== false;
	}

	public function execute() {
		$args = func_get_args();
		$q = array_shift($args);
		if (count($args) > 0) {
			$q = $this->compose($q, $args);
		}
		$this->log_query($q);
		if (mysql_query($q, $this->dbh) === false && mysql_errno($this->dbh) != 0) {
			$this->report_error($q);
			return false;
		}

		if (strtoupper(substr(ltrim($q), 0, 6)) === 'INSERT') {
			return mysql_insert_id($this->dbh);
		}
		return mysql_affected_rows($this->dbh);
	}

	public function query() {
		$args = func_get_args();
		$q = array_shift($args);
		if (count($args) > 0) {
			$q = $this->compose($q, $args);
		}
		$this->log_query($q);
		$this->rs = mysql_unbuffered_query($q, $this->dbh);
		if ($this->rs === false && mysql_errno($this->dbh) != 0) {
			$this->report_error($q);
			return false;
		}
		return true;
	}

	public function fetch($type=DB_ASSOC, $free_now=false) {
		if (!$this->rs) {
			return false;
		}
		$r = mysql_fetch_array($this->rs, $type == DB_ASSOC ? MYSQL_ASSOC : MYSQL_NUM);
		if ($r === false || $free_now) {
			mysql_free_result($this->rs);
			$this->rs = false;
		}
		return $r;
	}

	protected function e($s) {
		if (function_exists('mysql_real_escape_string')) {
			return mysql_real_escape_string($s, $this->dbh);
		}
		return mysql_escape_string($s);
	}

	public function get_last_error() {
		return mysql_error($this->dbh);
	}

	public function begin() {
		return $this->execute('SET AUTOCOMMIT=0') && $this->execute('BEGIN');
	}

	public function commit() {
		return $this->execute('COMMIT') && $this->execute('SET AUTOCOMMIT=1');
	}

	public function rollback() {
		return $this->execute('ROLLBACK') && $this->execute('SET AUTOCOMMIT=1');
	}
}

// ------------------------------------------------------------------ XML --

/**
 * Wrapper around SimpleXML to make building XML documents easier.
 */
class XML_ElementNode {

	private $node;

	public function __construct($elem, $nss=array()) {
		if (is_string($elem)) {
			$xml = "<$elem";
			foreach ($nss as $ns=>$uri) {
				$xml .= ' xmlns';
				if (!is_numeric($ns)) {
					$xml .= ':' . $ns;
				}
				$xml .= '="' . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8') . '"';
			}
			$xml .= '/>';

			$this->node = new SimpleXMLElement($xml);
		} else {
			// Otherwise, this is a subnode we're creating.
			$this->node = $elem;
		}
	}

	public function attr($name, $value='', $ns=null) {
		$this->node->addAttribute($name, $value, $ns);
		return $this;
	}

	public function child($name, $text=null, $ns=null) {
		$child = $this->node->addChild($name, $text, $ns);
		return new XML_ElementNode($child);
	}

	public function with($name, $text=null, $ns=null) {
		$this->child($name, $text, $ns);
		return $this;
	}

	public function as_xml() {
		return $this->node->asXML();
	}
}
?>
