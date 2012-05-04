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
class AFK_Context extends AFK_Environment {

	// Important HTTP response status codes {{{

	const OK = 200;
	const CREATED = 201;
	const ACCEPTED = 202;

	const PERMANENT = 301;
	const FOUND = 302;
	const SEE_OTHER = 303;
	const NOT_MODIFIED = 304;
	const TEMPORARY = 307;

	const BAD_REQUEST = 400;
	const UNAUTHORISED = 401;
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const BAD_METHOD = 405;
	const CONFLICT = 409;
	const ENTITY_TOO_LARGE = 413;

	const INTERNAL_ERROR = 500;

	// }}}

	// Caches {{{
	private $host_prefix = null;
	private $application_root = null;
	private $current_request_uri = null;
	private $current_method = null;
	// }}}

	/**
	 * Creates a query string from the given array. If an array element has
	 * a textual key, that's used as the query string key and the value is
	 * used as the value. If the key is numeric, the value is used as the
	 * key and the value to associate with that key is taken from the request
	 * context.
	 */
	public function to_query(array $vars, $prefix='?', $separator='&') { // {{{
		$result = '';
		foreach ($vars as $k => $v) {
			if (is_numeric($k)) {
				$k = $v;
				$v = $this->__get($k);
			}
			if ($v != '') {
				if ($result != '') {
					$result .= $separator;
				}
				$result .= rawurlencode($k) . '=' . rawurlencode($v);
			}
		}
		if ($result != '') {
			$result = $prefix . $result;
		}
		return $result;
	} // }}}

	/** Merges an array into the context, but if it's empty, cause a 404. */
	public function merge_or_not_found($ary, $msg='') { // {{{
		if (empty($ary)) {
			$this->not_found($msg);
		} else {
			$this->merge($ary);
		}
	} // }}}

	public function is_upload($key) { // {{{
		return $this->__isset($key) && $this->__get($key) instanceof AFK_UploadedFile;
	} // }}}

	// URLs {{{

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
		if (substr($path, 0, 2) == '~/') {
			$path = $this->application_root() . substr($path, 1);
		}
		if (strpos($path, '://') !== false) {
			return AFK_Urls::scrub_path($path);
		}
		return $this->get_host_prefix() . $this->canonicalise_path($path);
	}

	private function canonicalise_path($path) {
		if ($path[0] != '/') {
			$prefix = $this->REQUEST_URI;
			if (substr($prefix, -1) != '/') {
				$prefix = dirname($prefix);
				if ($prefix != '/') {
					$prefix .= '/';
				}
			}
			$path = $prefix . $path;
		}
		return AFK_Urls::scrub_path($path);
	}

	public function get_host_prefix() {
		if (is_null($this->host_prefix)) {
			$this->host_prefix = ($this->is_secure() ? 'https' : 'http') . '://' . $this->HTTP_HOST;
			// This array simplifies the logic for deciding if the port should be
			// included in the URL. The value is the default HTTP/HTTPS port, and
			// the key is whether it's secure (HTTPS) or not (HTTP). This reduces
			// the logic down to an array lookup and a comparison.
			$default_ports = array(true => 443, false => 80);
			if ($default_ports[$this->is_secure()] != $this->SERVER_PORT) {
				$this->host_prefix .= ':' . $this->SERVER_PORT;
			}
		}
		return $this->host_prefix;
	}

	/** @return The root path of the application. */
	public function application_root_path() {
		if (is_null($this->application_root)) {
			$path = $this->REQUEST_URI;
			$excess = strlen($this->PATH_INFO) - 1;
			if ($this->QUERY_STRING != '') {
				$excess += strlen($this->QUERY_STRING) + 1;
			} elseif (substr($path, -1) == '?') {
				$path = rtrim($path, '?');
			}
			$this->application_root = $excess > 0 ? substr($path, 0, -$excess) : $path;
		}
		return $this->application_root;
	}

	/** @return The root URL of the application. */
	public function application_root() {
		return $this->get_host_prefix() . $this->application_root_path();
	}

	public function base_url() {
		$parts = func_get_args();
		return $this->application_root() . implode('/', array_map('rawurlencode', $parts));
	}

	/** @return The current request URI (without the query string.) */
	public function request_uri() {
		if (is_null($this->current_request_uri)) {
			list($this->current_request_uri) = explode('?', $this->REQUEST_URI, 2);
		}
		return $this->current_request_uri;
	}

	// }}}

	// Request Information {{{

	/** @return The HTTP method used for this request. */
	public function method() {
		if (is_null($this->current_method)) {
			$this->current_method = strtolower($this->REQUEST_METHOD);
			if ($this->current_method == 'post' && isset($this->_method)) {
				$this->current_method = strtolower($this->_method);
			}
		}
		return $this->current_method;
	}

	/** @return True if this request is running over SSL/TLS. */
	public function is_secure() {
		return $this->__isset('HTTPS');
	}

	public function is_referrer_this_host() {
		if (isset($ctx->HTTP_REFERER)) {
			$parts = parse_url($ctx->HTTP_REFERER);
			return isset($parts['host']) && $parts['host'] == $ctx->HTTP_HOST;
		}
		return false;
	}

	// }}}

	// Views {{{

	private $allow_rendering = true;

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

	// }}}

	// HTTP Response Helpers {{{

	/**
	 * Wraps the PHP header() function to allow testing.
	 */
	public function header($header, $replace=true, $status=null) {
		if (is_null($status)) {
			header($header, $replace);
		} else {
			header($header, $replace, $status);
		}
	}

	/**
	 * Signal that the application has created a new resource.
	 *
	 * @param  $location  Path to the new resource.
	 */
	public function created($location) {
		$this->allow_rendering(false);
		$this->header('Location: ' . $this->to_absolute_uri($location), true, self::CREATED);
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
			$this->set_response_code(self::ACCEPTED);
		} else {
			$this->header('Location: ' . $this->to_absolute_uri($location), true, self::ACCEPTED);
		}
	}

	/**
	 * Performs a HTTP redirect.
	 *
	 * @param  The redirect type.
	 * @param  Where to redirect to. Defaults to the current URI.
	 *
	 * @note   For RFC2161 compliance, this does _not_ turn off rendering.
	 */
	public function redirect($code, $to=null) {
		if ($code < 300 || $code > 307 || $code == 306 || $code == self::NOT_MODIFIED) {
			throw new AFK_Exception(sprintf("Bad redirect code: %s", $code));
		}
		// For backward compatibility, see RFC2616, SS10.3.4
		if ($code == self::SEE_OTHER && $this->SERVER_PROTOCOL == 'HTTP/1.0') {
			$code = self::FOUND;
		}
		if (is_null($to)) {
			$to = $this->REQUEST_URI;
		}
		$this->header('Location: ' . $this->to_absolute_uri($to), true, $code);
	}

	public function see_other($to=null) {
		$this->redirect(self::SEE_OTHER, $to);
	}

	/** Performs a permanent redirect. See ::redirect(). */
	public function permanent_redirect($to) {
		$this->redirect(self::PERMANENT, $to);
	}

	public function try_not_modified($etag) {
		$status = self::OK;
		$etag = '"' . $etag . '"';
		if (in_array($etag, explode(', ', AFK::coalesce($this->HTTP_IF_NONE_MATCH, '')))) {
			$status = self::NOT_MODIFIED;
			$this->allow_rendering(false);
		}
		$this->header('ETag: ' . $etag, true, $status);
		return $status == self::NOT_MODIFIED;
	}

	/**
	 * Signal that the request was malformed.
	 *
	 * @param  $msg  Description of how the message is malformed.
	 *
	 * @note   This method triggers an immediate non-local jump.
	 */
	public function bad_request($msg='') {
		throw new AFK_HttpException($msg, self::BAD_REQUEST);
	}

	/**
	 * @note   This method triggers an immediate non-local jump.
	 */
	public function forbidden($msg='') {
		throw new AFK_HttpException($msg, self::FORBIDDEN);
	}

	/**
	 * Triggers a HTTP Not Found (404) response.
	 *
	 * @note   This method triggers an immediate non-local jump.
	 */
	public function not_found($msg='') {
		throw new AFK_HttpException($msg, self::NOT_FOUND);
	}

	/**
	 * Triggers a HTTP No Such Method (405) response.
	 *
	 * @note   This method triggers an immediate non-local jump.
	 */
	public function no_such_method(array $available_methods) {
		throw new AFK_HttpException('', self::BAD_METHOD, array('Allow' => $available_methods));
	}

	/**
	 * Triggers a HTTP Conflict (409) response.
	 *
	 * @note   This method triggers an immediate non-local jump.
	 */
	public function conflict($msg='') {
		throw new AFK_HttpException($msg, self::CONFLICT);
	}

	/** Sets the HTTP response code. */
	public function set_response_code($code) {
		// For backward compatibility, see RFC2616, SS10.3.4
		if ($code == self::SEE_OTHER && $this->SERVER_PROTOCOL == 'HTTP/1.0') {
			$code = self::FOUND;
		}
		if (in_array($code, array(204, 205, 304, 407, 411, 413, 414, 415, 416, 417))) {
			$this->allow_rendering(false);
		}
		$this->header("{$this->SERVER_PROTOCOL} $code " . $this->get_status_msg($code));
	}

	private function get_status_msg($code) {
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

	// }}}
}
