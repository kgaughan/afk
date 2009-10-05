<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents the collection of all users.
 *
 * @author Keith Gaughan
 */
abstract class AFK_Users {

	const ANONYMOUS = 0;

	private $instances = array();

	public function __construct() {
		$this->add($this->get_anonymous_user());
	}

	// Implementation Assignment {{{

	private static $impl = null;

	public static function set_implementation(AFK_Users $impl) {
		self::$impl = $impl;
	}

	private static function ensure_implementation() {
		if (is_null(self::$impl)) {
			throw new AFK_Exception("No AFK_Users implementation assigned. Check if you've passed on to AFK_Users::set_implementation().");
		}
	}

	/// }}}

	// Loading User Instances {{{

	protected function add($inst) {
		if (is_null($inst)) {
			$this->instances[self::ANONYMOUS] = null;
		} else {
			$this->instances[$inst->get_id()] = $inst;
		}
	}

	public static function preload(array $ids) {
		self::ensure_implementation();
		self::$impl->internal_preload($ids);
	}

	private function internal_preload(array $ids) {
		$to_load = array_diff($ids, array_keys($this->instances));
		if (count($to_load) > 0) {
			$this->load($to_load);
		}
	}

	protected function get_anonymous_user() {
		return null;
	}

	protected abstract function load(array $ids);

	// }}}

	// Fetching Users {{{

	protected function has($id) {
		return array_key_exists($id, $this->instances);
	}

	private function internal_get($id) {
		if (!$this->has($id)) {
			$this->load(array($id));
			if (!$this->has($id)) {
				throw new AFK_Exception(sprintf("Bad user ID: %s", $id));
			}
		}
		return $this->instances[$id];
	}

	protected function get_current_user_id() {
		return self::ANONYMOUS;
	}

	public static function current() {
		self::ensure_implementation();
		return self::$impl->internal_get(self::$impl->get_current_user_id());
	}

	public static function get($id) {
		self::ensure_implementation();
		return self::$impl->internal_get($id);
	}

	// }}}

	// Access Control {{{

	public static function act_as_effective_user($id) {
		self::ensure_implementation();
		// We delegate it to the actual implementation.
		self::$impl->act_as_effective_user_impl($id);
	}

	public static function revert_to_actual_user() {
		self::ensure_implementation();
		// We delegate it to the actual implementation.
		self::$impl->revert_to_actual_user_impl();
	}

	/**
	 * Temporarily behave as if the application was being executed by the
	 * user/account with id $id. Please note that if the user is already
	 * logged in, this should not overwrite the actual logged-in user's id.
	 * Here's an example implementation:
	 *
	 * <code>
	 * if ($this->actual_id === false) {
	 *     $this->actual_id = $this->id;
	 * }
	 * $this->id = $id;
	 * </code>
	 */
	public function act_as_effective_user_impl($id) {
		// Subclass should implement this.
		throw new Exception(sprintf("%s not implemented.", get_class($this) . '::' . __METHOD__));
	}

	/**
	 * Switch back to the user who's actually logged in. Here's an example
	 * implementation:
	 *
	 * <code>
	 * if ($this->actual_id !== false) {
	 *     $this->id = $this->actual_id;
	 *     $this->actual_id = false;
	 * }
	 * </code>
	 */
	public function revert_to_actual_user_impl() {
		// Subclass should implement this.
		throw new Exception(sprintf("%s not implemented.", get_class($this) . '::' . __METHOD__));
	}

	public static function prerequisites() {
		self::ensure_implementation();
		$reqs = func_get_args();
		$user = self::current();
		if (is_null($user) || !call_user_func_array(array($user, 'can'), $reqs)) {
			self::$impl->require_auth();
		}
	}

	public static function member_of() {
		self::ensure_implementation();
		$reqs = func_get_args();
		$user = self::current();
		if (is_null($user) || !call_user_func_array(array($user, 'member_of'), $reqs)) {
			self::$impl->require_auth();
		}
	}

	public static function force_auth() {
		if (is_null(self::current())) {
			self::$impl->require_auth();
		}
	}

	protected function require_auth() {
		static $called = false;
		if (!$called) {
			$called = true;
			throw new AFK_HttpException(
				'You lack the required credentials.',
				AFK_Context::FORBIDDEN);
		}
	}

	// }}}
}
