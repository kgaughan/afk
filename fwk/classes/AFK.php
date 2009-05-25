<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Various framework utilities, mostly for internal use.
 *
 * @author Keith Gaughan
 */
class AFK {

	public static $loader;

	public static function register_autoloader() {
		if (function_exists('spl_autoload_register')) {
			// Attempt to register an autoloader cleanly...
			spl_autoload_register(array(self::$loader, 'load_class'));
		} else {
			// ...but if all else fails, just slam it in.
			function __autoload($name) {
				AFK::$loader->load_class($name);
			}
		}
	}

	public static function load_helper() {
		$args = func_get_args();
		call_user_func_array(array(self::$loader, 'load_helper'), $args);
	}

	/** Does a clean HTML dump of the given variable. */
	public static function dump() {
		$vs = func_get_args();
		ob_start();
		ob_implicit_flush(false);
		call_user_func_array('var_dump', $vs);
		$contents = ob_get_contents();
		ob_end_clean();
		if (!extension_loaded('Xdebug') && php_sapi_name() != 'cli') {
			$contents = '<pre class="afk-dump">' . e($contents) . '</pre>';
		}
		echo $contents;
	}

	// Workarounds {{{

	/** Fixes the superglobals by removing any magic quotes, if present. */
	public static function fix_superglobals() {
		if (get_magic_quotes_gpc()) {
			$cb = array('AFK', 'fix_magic_quotes');
			array_walk_recursive($_GET, $cb);
			array_walk_recursive($_POST, $cb);
			array_walk_recursive($_COOKIE, $cb);
			array_walk_recursive($_REQUEST, $cb);
			set_magic_quotes_runtime(0);
		}
	}

	private static function fix_magic_quotes(&$val, $_) {
		$val = stripslashes($val);
	}

	/// }}}

	// Framework {{{

	/** Basic bootstrapping logic. Feel free to write your own. */
	public static function bootstrap() {
		self::fix_superglobals();

		self::$loader = new AFK_Loader();
		self::$loader->add_helper_path(AFK_ROOT . '/helpers');
		self::$loader->add_helper_path(APP_ROOT . '/lib/helpers');
		self::$loader->add_class_path(AFK_ROOT . '/classes');
		self::$loader->add_class_path(APP_ROOT . '/lib/classes');
		self::$loader->add_class_path(APP_ROOT . '/classes');
		self::$loader->add_class_path(APP_ROOT . '/handlers');
		self::register_autoloader();

		AFK_Registry::set_instance($registry = new AFK_Registry());

		include APP_ROOT . '/config.php';
		if (file_exists(APP_ROOT . '/lib/lib.php')) {
			include APP_ROOT . '/lib/lib.php';
		}

		$registry->routes = routes()->get_map();
		return init();
	}

	/** Basic dispatcher logic. Feel free to write your own dispatcher. */
	public static function process_request(AFK_RouteMap $map, $extra_filters=array()) {
		$paths = new AFK_PathList();
		$paths->prepend(AFK_ROOT . '/templates');

		$p = new AFK_Pipeline();
		$p->add(new AFK_ExceptionTrapFilter());
		$p->add(new AFK_RouteFilter($map, $_SERVER, $_REQUEST));
		foreach ($extra_filters as $filter) {
			$p->add($filter);
		}
		$p->add(array('AFK_CoreFilters', 'dispatch'));
		$p->add(new AFK_RenderFilter(new AFK_TemplateEngine($paths)));
		$p->start(AFK_Registry::context());
	}

	// }}}
}

/**
 * Represents a list of paths to load files from.
 *
 * @author Keith Gaughan
 */
class AFK_PathList {

	private $paths = array();
	private $path_pattern;

	public function __construct($path_pattern="%s/%s.php") {
		$this->path_pattern = $path_pattern;
	}

	public function prepend($path) {
		array_unshift($this->paths, $path);
	}

	public function append($path) {
		$this->paths[] = $path;
	}

	public function find($name) {
		foreach ($this->paths as $d) {
			$path = sprintf($this->path_pattern, $d, $name);
			if (file_exists($path)) {
				return $path;
			}
		}
		return false;
	}

	public function load($name) {
		$path = $this->find($name);
		if ($path !== false) {
			include $path;
			return $path;
		}
		return false;
	}
}

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

/**
 * Common logic for AFK-specific exceptions.
 *
 * @author Keith Gaughan
 */
class AFK_Exception extends Exception {

	public function __construct($msg, $code=0) {
		parent::__construct($msg, $code);
	}

	public function __toString() {
		return sprintf(
			"%s in %s at line %d:\nCode %d: %s\n\n",
			get_class($this), $this->file, $this->line, $this->code, $this->message);
	}
}
