<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 *
 */
abstract class AFK_HttpAuthUsers extends AFK_Users {

	private static $realm = 'Realm';
	private static $methods = array();

	private $id;
	private $actual_id;

	public function __construct() {
		parent::__construct();
		$this->id = false;
		$this->actual_id = false;
	}

	public static function set_realm($realm) {
		self::$realm = $realm;
	}

	public static function add_method(AFK_HttpAuth $method) {
		self::$methods[$method->get_name()] = $method;
	}

	private function collect_authenticate_headers() {
		$headers = array();
		foreach (self::$methods as $method) {
			$headers[] = $method->get_name() . ' ' . $method->get_authenticate_header(self::$realm);
		}
		return $headers;
	}

	private function get_header(AFK_Environment $ctx) {
		$header = false;
		if (isset($ctx->HTTP_AUTHORIZATION)) {
			$header = $ctx->HTTP_AUTHORIZATION;
		} elseif (isset($ctx->Authorization)) {
			$header = $ctx->Authorization;
		} elseif (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (array_key_exists('Authorization', $headers)) {
				$header = $headers['Authorization'];
			}
		}
		if ($header !== false) {
			return explode(' ', $header, 2);
		}
		return false;
	}

	private function check() {
		$ctx = AFK_Registry::context();
		$header = $this->get_header($ctx);
		if ($header !== false) {
			list($method_name, $data) = $header;
			$method = self::$methods[$method_name];

			$username = $method->initialise(self::$realm, $data);
			$authentication_info = $this->authenticate($username);
			if ($authentication_info !== false) {
				list($id, $expected) = $authentication_info;
				if ($method->verify($ctx, $expected)) {
					return $id;
				}
			}
		}
		return false;
	}

	public function act_as_effective_user_impl($id) {
		if ($this->actual_id === false) {
			$this->actual_id = $this->id;
		}
		$this->id = $id;
	}

	public function revert_to_actual_user_impl() {
		if ($this->actual_id !== false) {
			$this->id = $this->actual_id;
			$this->actual_id = false;
		}
	}

	protected function get_current_user_id() {
		if ($this->id === false && ($id = $this->check()) !== false) {
			$this->id = $id;
		}
		return $this->id !== false ? $this->id : AFK_Users::ANONYMOUS;
	}

	/**
	 * @return array(id, a1_hash), or false if no such user.
	 */
	protected abstract function authenticate($username);

	protected function require_auth() {
		static $called = false;
		if (!$called) {
			$called = true;
			throw new AFK_HttpException(
				'You are not authorised for access.',
				AFK_Context::UNAUTHORISED,
				array('WWW-Authenticate' => $this->collect_authenticate_headers()));
		}
	}
}
