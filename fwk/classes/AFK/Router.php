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
class AFK_Router {

	/** Maps routes to handlers. */
	private $routes = array();

	// Programmatic Routes Building {{{

	/** Adds a route and an associated resource handler. */
	public function route($route, $defaults=array(), $patterns=array()) {
		list($regex, $keys) = $this->compile_route($route, $patterns);
		$this->routes[$regex] = array($keys, $defaults);
	}

	// }}}

	// Route Searching {{{

	/** Finds the first route that matches the given path. */
	public function search($path) {
		$result = $this->internal_search($path);
		if ($result !== false) {
			return $result;
		}

		// Tweak it to remove or append a trailing slash.
		$path = substr($path, -1) == '/' ? substr($path, 0, -1) : "$path/";
		foreach ($this->routes as $regex => $_) {
			if ($this->match($regex, $path)) {
				return $path;
			}
		}

		throw new AFK_HttpException('', AFK_Context::NOT_FOUND);
	}

	private function internal_search($path) {
		foreach ($this->routes as $regex => $v) {
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

	// }}}

	// Route Compilation {{{

	/** Compiles a route into a regex and an array of placeholder keys. */
	private function compile_route($route, array $patterns) {
		// Construct a regular expression to match this route. This method is
		// meant to be simple, not to be fast. If speed becomes necessary, it
		// can probably be improved using some complex nonsense with
		// preg_replace_callback().
		// Idea: compile the routes, then serialise them somewhere, possibly
		// a file. Will need some caching mechanism first and some way of
		// figuring out if the routes have changed.
		$keys  = array();
		$regex = '';
		$parts = explode('{', $route);
		foreach ($parts as $i => $part) {
			$matches = array();
			if (preg_match('/^(?:([a-z_]+)})?([^a-z_]?.*)$/i', $part, $matches)) {
				if (strlen($matches[1]) == 0) {
					// No placeholder: most likely the first segment.
					$regex .= $this->quote($matches[0]);
				} else {
					$keys[] = $matches[1];
					$regex .= $this->to_pattern($matches[1], $matches[2], $patterns);
				}
			} else {
				// Malformed route!
				throw new AFK_RouteSyntaxException($route);
			}
		}
		return array($regex, $keys);
	}

	private function to_pattern($name, $trailer, array $patterns) {
		if (array_key_exists($name, $patterns)) {
			$p = $patterns[$name];
			if (is_array($p)) {
				$p = implode('|', array_map(array($this, 'quote'), $p));
			}
		} elseif ($trailer == '') {
			$p = '.+';
		} else {
			$p = '[^' . $this->escape_class_character($trailer[0]) . ']+';
		}
		return "($p)" . $this->quote($trailer);
	}

	/** Escapes a character if it has a special meaning in a character class. */
	private function escape_class_character($c) {
		if (strpos("/^-]\\", $c) !== false) {
			return "\\$c";
		}
		return $c;
	}

	private function quote($s) {
		return preg_quote($s, '`');
	}

	// }}}

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
