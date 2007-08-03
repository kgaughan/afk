<?php
abstract class AFK_Users {

	private static $impl = null;
	private $instances = array();

	public static function set_implementation(AFK_Users $impl) {
		self::$impl = $impl;
	}

	/* Loading users. */

	protected function add(AFK_User $inst) {
		$this->instances[$inst->get_id()] = $inst;
	}

	public static function preload($users) {
		self::$impl->internal_preload($users);
	}

	private function internal_preload($users) {
		$to_load = array_diff($users, array_keys($this->instances));
		if (count($to_load) > 0) {
			$this->load($to_load);
		}
	}

	protected abstract function load($users);

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
		return self::$impl->internal_get(self::$impl->get_current_user_id());
	}

	public static function get($id) {
		return self::$impl->internal_get($id);
	}

	/* Access control. */

	public static function prerequisites() {
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
?>
