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
		if ($this->__isset($key)) {
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
			if (!$this->__isset($k)) {
				$this->__set($k, '');
			}
		}
	} // }}}

	/** Use the given defaults if the named fields aren't set. */
	public function defaults(array $defaults) { // {{{
		foreach ($defaults as $k => $v) {
			if (!$this->__isset($k)) {
				$this->__set($k, $v);
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
			$this->env = array_merge($a, $this->env);
		}
	} // }}}

	/** @return Part or all of the environment as an array. */
	public function as_array($indices=false) { // {{{
		if ($indices === false) {
			return $this->env;
		}
		if (is_array($indices)) {
			$extracted = array();
			foreach ($indices as $i) {
				if ($this->__isset($i)) {
					$extracted[$i] = $this->__get($i);
				}
			}
			return $extracted;
		}
		return array();
	} // }}}
}
