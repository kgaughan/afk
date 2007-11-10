<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Deprecated fluent interface around AFK_Router.
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
