<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Compiles routes into a searchable form.
 */
class AFK_Routes
{
	private $map;
	private $defaults = array();
	private $patterns;

	public function __construct(array $patterns=array())
	{
		$this->map = new AFK_RouteMap();
		$this->patterns = $patterns;
	}

	public function get_map()
	{
		return $this->map;
	}

	public function defaults(array $defaults)
	{
		$this->defaults = $defaults;
	}

	/**
	 * Adds a route and an associated resource handler.
	 */
	public function route($route, array $defaults=array(), array $patterns=array())
	{
		$all_patterns = $this->patterns + $patterns;
		$all_defaults = $this->defaults + $defaults;
		list($regex, $keys) = $this->compile_route($route, $all_patterns);
		$this->map->add($regex, $keys, $all_defaults);
		return $this;
	}

	// Route Compilation {{{

	/**
	 * Compiles a route into a regex and an array of placeholder keys.
	 */
	private function compile_route($route, array $patterns)
	{
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

	private function to_pattern($name, $trailer, array $patterns)
	{
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

	/**
	 * Escapes a character if it has a special meaning in a character class.
	 */
	private function escape_class_character($c)
	{
		if (strpos("/^-]\\", $c) !== false) {
			return "\\$c";
		}
		return $c;
	}

	private function quote($s)
	{
		return preg_quote($s, '`');
	}

	// }}}
}
