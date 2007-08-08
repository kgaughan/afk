<?php
class AFK_Registry {

	private static $inst;

	public static function set_instance(AFK_Registry $inst) {
		self::$inst = $inst;
	}

	public static function broker() {
		return self::$inst->broker;
	}

	public static function context() {
		return self::$inst->context;
	}

	public static function output_cache() {
		return self::$inst->output_cache;
	}

	public static function slots() {
		return self::$inst->slots;
	}

	public static function routes() {
		return self::$inst->routes;
	}

	public $broker;
	public $context;
	public $output_cache;
	public $slots;
	public $routes;

	public function __construct() {
		$this->broker = new AFK_EventBroker();
		$this->context = new AFK_Context();
		$this->output_cache = new AFK_OutputCache();
		$this->slots = new AFK_Slots();
	}
}
