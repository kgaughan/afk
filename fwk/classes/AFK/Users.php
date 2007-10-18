<?php
abstract class AFK_Users {

	private static $impl = null;
	private $instances = array();

	public static function set_implementation(AFK_Users $impl) {
		self::$impl = $impl;
	}

	private static function ensure_implementation() {
		if (is_null(self::$impl)) {
			throw new AFK_Exception("No AFK_Users implementation assigned. Check if you've passed on to AFK_Users::set_implementation().");
		}
	}

	/* Loading users. */

	protected function add(AFK_User $inst) {
		$this->instances[$inst->get_id()] = $inst;
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

	protected abstract function load(array $ids);

	/* Fetching users. */

	protected function has($id) {
		return isset($this->instances[$id]);
	}

	private function internal_get($id) {
		if (!$this->has($id)) {
			$this->load(array($id));
			if (!$this->has($id)) {
				if ($id === 0) {
					return null;
				}
				throw new AFK_Exception("Bad User ID: $id");
			}
		}
		return $this->instances[$id];
	}

	protected function get_current_user_id() {
		return 0;
	}

	public static function current() {
		self::ensure_implementation();
		return self::$impl->internal_get(self::$impl->get_current_user_id());
	}

	public static function get($id) {
		self::ensure_implementation();
		return self::$impl->internal_get($id);
	}

	/* Access control. */

	public static function prerequisites() {
		self::ensure_implementation();
		$reqs = func_get_args();
		$user = self::current();
		if (!call_user_func_array(array($user, 'can'), $reqs)) {
			self::$impl->require_auth();
		}
	}

	protected function require_auth() {
		static $called = false;
		if (!$called) {
			$called = true;
			throw new AFK_HttpException('You lack the required credentials.', 403);
		}
	}
}
