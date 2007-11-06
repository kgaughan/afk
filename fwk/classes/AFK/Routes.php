<?php
/**
 * URL parsing and routing.
 */
class AFK_Routes {

	private $router;

	public function __construct() {
		$this->router = new AFK_Router();
	}

	/** @deprecated */
	public function fallback(array $defaults) {
		$this->router->fallback($defaults);
		return $this;
	}

	/** @deprecated */
	public function route($route, $defaults=array(), $patterns=array()) {
		$this->router->route($route, $defaults, $patterns);
		return $this;
	}

	/** @deprecated */
	public function search($path) {
		return $this->router->search($path);
	}

	public function get_router() {
		return $this->router;
	}
}
