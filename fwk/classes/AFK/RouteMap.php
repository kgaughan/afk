<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A compiled representation of the routes.
 *
 * Does the actual matching; matching is done in specification order.
 *
 * @author Keith Gaughan
 */
class AFK_RouteMap {

	/* Maps routes to handlers. */
	private $map = array();

	public function add($route, array $keys, array $defaults) {
		$this->map[$route] = array($keys, $defaults);
	}

	/**
	 * Finds the first route that matches the given path.
	 */
	public function search($path) {
		$result = $this->internal_search($path);
		if ($result !== false) {
			return $result;
		}

		// Tweak it to remove or append a trailing slash.
		$path = substr($path, -1) == '/' ? substr($path, 0, -1) : "$path/";
		foreach ($this->map as $regex => $_) {
			if ($this->match($regex, $path)) {
				return $path;
			}
		}

		throw new AFK_HttpException('', AFK_Context::NOT_FOUND);
	}

	private function internal_search($path) {
		foreach ($this->map as $regex => $v) {
			// This is only needed because PHP's parser is too dumb to allow
			// the use for list() in the foreach.
			list($keys, $defaults) = $v;

			$values = $this->match($regex, $path);
			if ($values !== false) {
				return array_merge($defaults, $this->combine($keys, $values));
			}
		}
		return false;
	}

	/**
	 * Checks if a given route regex matches a path.
	 *
	 * @return false if it doesn't, otherwise an array of extracted values.
	 */
	private function match($regex, $path) {
		$values = array();
		if (preg_match('`^' . $regex . '$`', $path, $values)) {
			// We only want the parts parsed out, not the whole string.
			array_shift($values);
			return $values;
		}
		return false;
	}

	// Utilities {{{

	/**
	 * Behaves like array_combine(), but is safe when there's no keys.
	 */
	private function combine(array $keys, array $values) {
		if (count($keys) > 0) {
			return array_combine($keys, $values);
		}
		return array();
	}

	// }}}
}
