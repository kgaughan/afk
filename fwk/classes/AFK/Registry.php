<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * One of the singlemost crappy elements of AFK, this is essentially a way of
 * having global variables without polluting the global scope, though aside
 * for providing access to the current context and routes, it's not really
 * meant for external use.
 *
 * This has one advantage: registries can be swapped in and out for testing
 * purposes.
 */
class AFK_Registry {

	// Swappable Implementation {{{

	private static $inst;

	public static function set_instance(AFK_Registry $inst) {
		$old_inst = self::$inst;
		self::$inst = $inst;
		return $old_inst;
	}

	// }}}

	// External Interface {{{

	public static function context() {
		return self::$inst->ctx;
	}

	public static function routes() {
		return self::$inst->routes;
	}

	public static function _($name) {
		return self::$inst->__get($name);
	}

	// }}}

	// Internal Instance Implementation {{{

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

	// }}}
}
