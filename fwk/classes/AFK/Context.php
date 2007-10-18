<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents the current request context.
 *
 * @author Keith Gaughan
 */
class AFK_Context {

	const PERMANENT = 301;
	const SEE_OTHER = 303;
	const TEMPORARY = 307;

	private $ctx = array();
	private $allow_rendering = true;

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

	/** Merges an array into the context, but if it's empty, cause a 404. */
	public function merge_or_not_found($ary, $msg='') {
		if (empty($ary)) {
			$this->not_found($msg);
		} else {
			$this->merge($ary);
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

	/**
	 * Canonicalises a path relative to the current request URI to one
	 * relative to the application root URI, producing a path that can be
	 * processed by routing code.
	 *
	 * @return The resolved path, or false if it could not be processed,
	 *         e.g., it resolved to something outside the application.
	 */
	public function resolve($rel_path) {
		$canon = $this->to_absolute_uri($rel_path);
		$root = $this->application_root();
		if (substr_compare($canon, $root, 0, strlen($root)) == 0) {
			return substr($canon, strlen($root) - 1);
		}
		return false;
	}

	/**
	 * Converts the given URI into an absolute one, fit for use with the
	 * HTTP 'Location' header.
	 *
	 * @param  $path  An absolute or relative path to canonicalise into a URI.
	 */
	public function to_absolute_uri($path) {
		if (strpos($path, '://') !== false) {
			return $this->scrub_path($path);
		}
		return $this->get_host_prefix() . $this->canonicalise_path($path);
	}

	private function canonicalise_path($path) {
		if ($path[0] != '/') {
			$prefix = $this->REQUEST_URI;
			if (substr($prefix, -1) != '/') {
				$prefix = dirname($prefix) . '/';
			}
			$path = $prefix . $path;
		}
		return $this->scrub_path($path);
	}

	private function get_host_prefix() {
		static $prefix = null;
		if (is_null($prefix)) {
			$ports = array(true => 443, false => 80);
			$prefix = ($this->is_secure() ? 'https' : 'http') . '://' . $this->HTTP_HOST;
			if ($ports[$this->is_secure()] != $this->SERVER_PORT) {
				$prefix .= ':' . $this->SERVER_PORT;
			}
		}
		return $prefix;
	}

	private function scrub_path($path) {
		// Attempt to canonicalise the path, removing any instances of '..'
		// and '.'. Why? Mainly because it's likely that the client dealing
		// with the request will likely not be smart enough to deal with them
		// itself. This code sucks, and no, realpath() won't work here.
		$path = preg_replace('~/(\.(/|$))+~', '/', $path);
		$c = 0;
		do {
			$path = preg_replace('~/[^./;?]([^./?;][^/?;]*)?/\.\.(/|$)~', '/', $path, -1, $c);
		} while ($c > 0);
		return $path;
	}

	/** @return The root URL path of the application. */
	public function application_root() {
		static $root = null;
		if (is_null($root)) {
			$path = $this->REQUEST_URI;
			$len = strlen($path) - strlen($this->PATH_INFO);
			if ($this->QUERY_STRING == '') {
				$len++;
			} else {
				$len -= strlen($this->QUERY_STRING);
			}
			$root = $this->get_host_prefix() . substr($path, 0, $len);
		}
		return $root;
	}

	/** @return The current request URI (without the query string.) */
	public function request_uri() {
		static $path = null;
		if (is_null($path)) {
			$path = $this->REQUEST_URI;
			if ($this->QUERY_STRING != '') {
				$path = substr($path, 0, strpos($path, '?'));
			}
		}
		return $path;
	}

	/** @return The HTTP method used for this request. */
	public function method() {
		static $method = null;
		if (is_null($method)) {
			$method = strtolower($this->REQUEST_METHOD);
			if ($method == 'post' && isset($this->_method)) {
				$method = strtolower($this->_method);
			}
		}
		return $method;
	}

	/** @return True if this request is running over SSL/TLS. */
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

	/** Toggles whether any rendering components should run. */
	public function allow_rendering($allow=true) {
		$this->allow_rendering = $allow;
	}

	/** @return True if any subsequent template renderer should run. */
	public function rendering_is_allowed() {
		return $this->allow_rendering;
	}

	/**
	 * Signal that the application has created a new resource.
	 *
	 * @param  $location  Path to the new resource.
	 */
	public function created($location) {
		$this->allow_rendering(false);
		header('Location: ' . $this->to_absolute_uri($location), true, 201);
	}

	/**
	 * Signal that the application has accepted but not yet processed the
	 * request.
	 *
	 * @param  $location  Location to poll, if any. If not specified here,
	 *                    give it in the body.
	 */
	public function accepted($location=null) {
		if (is_null($location)) {
			header('HTTP/1.1 202 Accepted');
		} else {
			header('Location: ' . $this->to_absolute_uri($location), true, 202);
		}
	}

	/**
	 * Performs a HTTP redirect.
	 *
	 * @param  The redirect type. Defaults to 'See Other' (303).
	 * @param  Where to redirect to. Defaults to the current URI.
	 */
	public function redirect($code=303, $to=null) {
		if ($code < 300 || $code > 307 || $code == 306 || $code == 304) {
			throw new AFK_Exception("Bad redirect code: $code");
		}
		// For backward compatibility, see RFC2616, SS10.3.4
		if ($code == self::SEE_OTHER && $this->SERVER_PROTOCOL == 'HTTP/1.0') {
			$code = 302;
		}
		if (is_null($to)) {
			$to = $this->REQUEST_URI;
		}
		header('Location: ' . $this->to_absolute_uri($to), true, $code);
	}

	/** Performs a permanent redirect. See ::redirect(). */
	public function permanent_redirect($to) {
		$this->redirect(self::PERMANENT, $to);
	}

	/**
	 * Signal that the request was malformed.
	 *
	 * @param  $msg  Description of how the message is malformed.
	 */
	public function bad_request($msg='') {
		throw new AFK_HttpException($msg, 400);
	}

	public function forbidden($msg='') {
		throw new AFK_HttpException($msg, 403);
	}

	/** Triggers a HTTP Not Found (404) response. */
	public function not_found($msg='') {
		throw new AFK_HttpException($msg, 404);
	}

	/** Triggers a HTTP No Such Method (405) response. */
	public function no_such_method(array $available_methods) {
		throw new AFK_HttpException('', 405, array('Allow' => $available_methods));
	}

	/** Sets the HTTP response code. */
	public function set_response_code($code) {
		// For backward compatibility, see RFC2616, SS10.3.4
		if ($code == self::SEE_OTHER && $this->SERVER_PROTOCOL == 'HTTP/1.0') {
			$code = 302;
		}
		if (array_search($code, array(204, 205, 407, 411, 413, 414, 415, 416, 417)) !== false) {
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
	public function to_query(array $vars) {
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
	public function defaults(array $defaults) {
		foreach ($defaults as $k=>$v) {
			if (!isset($this->ctx[$k])) {
				$this->ctx[$k] = $v;
			}
		}
	}

	/** @return The context as an array. */
	public function as_array() {
		return array_merge($this->ctx, array('ctx' => $this));
	}
}
