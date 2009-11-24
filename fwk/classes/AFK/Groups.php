<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents the collection of all groups.
 *
 * @author Keith Gaughan
 * @author Ken Guest
 */
abstract class AFK_Groups {

	private $instances = array();

	public function __construct() {
	}

	// Implementation Assignment {{{

	private static $impl = null;

	public static function set_implementation(AFK_Groups $impl) {
		self::$impl = $impl;
	}

	private static function ensure_implementation() {
		if (is_null(self::$impl)) {
			throw new AFK_Exception("No AFK_Groups implementation assigned. Check if you've passed on to AFK_Users::set_implementation().");
		}
	}

	/// }}}

	// Loading Group Instances {{{

	protected function add(AFK_Group $inst) {
		$this->instances[$inst->get_slug()] = $inst;
	}

	protected abstract function load_all();

	// }}}

	// Fetching Groups {{{

	protected function has($slug) {
		return array_key_exists($slug, $this->instances);
	}

	private function internal_get($slug) {
		if (!$this->has($slug)) {
			$this->load_all();
			if (!$this->has($slug)) {
				throw new AFK_Exception(sprintf("Bad group slug: %s", $slug));
			}
		}
		return $this->instances[$slug];
	}

	private function internal_get_all() {
		if (count($this->instances) == 0) {
			$this->load_all();
		}
		return $this->instances;
	}

	public static function get(array $slugs) {
		self::ensure_implementation();
		$result = array();
		foreach ($slugs as $slug) {
			$result[$slugs] = self::$impl->internal_get($slug);
		}
		return $result;
	}

	public static function get_all() {
		self::ensure_implementation();
		return self::$impl->internal_get_all();
	}

	// }}}
}
