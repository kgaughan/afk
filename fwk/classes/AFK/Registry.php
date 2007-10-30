<?php
class AFK_Registry {

	private static $inst;

	public static function set_instance(AFK_Registry $inst) {
		$old_inst = self::$inst;
		self::$inst = $inst;
		return $old_inst;
	}

	public static function context() {
		return self::$inst->ctx;
	}

	public static function routes() {
		return self::$inst->routes;
	}

	public static function _($name) {
		return self::$inst->__get($name);
	}

	private $registry = array();

	public function __construct() {
		$this->broker = new AFK_EventBroker();
		$this->ctx = new AFK_Context();
		$this->output_cache = new AFK_OutputCache();
		$this->slots = new AFK_Slots();
	}

	public function __get($name) {
		if (!isset($this->registry[$name])) {
			throw new AFK_Exception("'$name' is not registered with the current AFK_Registry.");
		}
		return $this->registry[$name];
	}

	public function __set($name, $value) {
		$this->registry[$name] = $value;
	}
}
