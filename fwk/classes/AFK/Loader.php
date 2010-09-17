<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2010. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * The class and helper loader.
 *
 * @author Keith Gaughan
 */
class AFK_Loader {

	private $class_paths;
	private $helper_paths;
	private $loaded_helpers;

	public function __construct() {
		$this->class_paths = new AFK_PathList();
		$this->helper_paths = new AFK_PathList();
		$this->loaded_helpers = array();
	}

	/** Adds a new directory to use when searching for classes. */
	public function add_class_path($path) {
		$this->class_paths->append($path);
	}

	/** Loads the named class from one of the registered class paths. */
	public function load_class($name) {
		$path = $this->class_paths->load(str_replace('_', '/', $name));
		if ($path === false) {
			throw new AFK_ClassLoadException("Could not load '$name': No class file matching that name.");
		}
		if (!$this->class_or_interface_is_loaded($name)) {
			throw new AFK_ClassLoadException("Could not load '$name': '$path' did not contain it.");
		}
		return true;
	}

	private function class_or_interface_is_loaded($name) {
		// Workaround for changes introduced in PHP 5.0.2.
		return class_exists($name, false) || function_exists('interface_exists') && interface_exists($name, false);
	}

	/** Adds a new directory to use when searching for helpers. */
	public function add_helper_path($path) {
		$this->helper_paths->append($path);
	}

	/** Loads the named helpers. */
	public function load_helper() {
		foreach (func_get_args() as $name) {
			if (!array_key_exists($name, $this->loaded_helpers)) {
				if ($this->helper_paths->load($name) === false) {
					throw new AFK_Exception("Unknown helper: $name");
				}
				// There's no significance to the stored value: we're
				// just using the array keys as a set.
				$this->loaded_helpers[$name] = true;
			}
		}
	}
}
