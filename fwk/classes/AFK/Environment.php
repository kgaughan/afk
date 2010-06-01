<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2008. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents an execution environment.
 */
class AFK_Environment {

	private $env = array();

	// Magic Methods for Variables {{{

	public function __isset($key) { // {{{
		return array_key_exists($key, $this->env);
	} // }}}

	public function __unset($key) { // {{{
		unset($this->env[$key]);
	} // }}}

	public function __get($key) { // {{{
		if (array_key_exists($key, $this->env)) {
			return $this->env[$key];
		}
		return null;
	} // }}}

	public function __set($key, $val) { // {{{
		$this->env[$key] = $val;
	} // }}}

	// }}}

	// Defaults {{{

	/** Default the named fields to empty strings. */
	public function default_to_empty() { // {{{
		$fields = func_get_args();
		foreach ($fields as $k) {
			if (!array_key_exists($k, $this->env)) {
				$this->env[$k] = '';
			}
		}
	} // }}}

	/** Use the given defaults if the named fields aren't set. */
	public function defaults(array $defaults) { // {{{
		foreach ($defaults as $k => $v) {
			if (!array_key_exists($k, $this->env)) {
				$this->env[$k] = $v;
			}
		}
	} // }}}

	// }}}

	/**
	 * Merges the given arrays into the environment; existing values are
	 * not overwritten.
	 */
	public function merge() { // {{{
		$args = func_get_args();
		foreach ($args as $a) {
			// Values already in the context are preserved.
			$this->env += $a;
		}
	} // }}}

	/** @return Part or all of the environment as an array. */
	public function as_array($keys=false) { // {{{
		if ($keys === false) {
			return $this->env;
		}
		if (is_array($keys)) {
			$extracted = array();
			foreach ($keys as $k) {
				if (array_key_exists($k, $this->env)) {
					$extracted[$k] = $this->env[$k];
				}
			}
			return $extracted;
		}
		return array();
	} // }}}
}
