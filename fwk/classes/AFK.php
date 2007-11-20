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

	// Autoloading {{{

	private static $class_paths = array();
	private static $helper_paths = array();
	private static $loaded_helpers = array();

	public static function register_autoloader() {
		require dirname(__FILE__) . '/AFK/Exception.php';
		require dirname(__FILE__) . '/AFK/HttpException.php';
		require dirname(__FILE__) . '/AFK/ClassLoadException.php';
		if (function_exists('spl_autoload_register')) {
			// Attempt to register an autoloader cleanly...
			spl_autoload_register(array(__CLASS__, 'load_class'));
		} else {
			// ...but if all else fails, just slam it in.
			function __autoload($name) {
				AFK::load_class($name);
			}
		}
	}

	/** Adds a new directory to use when searching for classes. */
	public static function add_class_path($path) {
		self::$class_paths[] = $path;
	}

	/** Loads the named class from one of the registered class paths. */
	public static function load_class($name) {
		$location = self::load(self::$class_paths, str_replace('_', '/', $name));
		if ($location === false) {
			throw new AFK_ClassLoadException("Could not load '$name': No file matching that name.");
		}
		if (!self::class_or_interface_is_loaded($name)) {
			throw new AFK_ClassLoadException("Count not load '$name': '$location' did not contain it.");
		}
		return true;
	}

	private static function class_or_interface_is_loaded($name) {
		// Workaround for changes introduced in PHP 5.0.2.
		return class_exists($name, false) || function_exists('interface_exists') && interface_exists($name, false);
	}

	/** Adds a new directory to use when searching for helpers. */
	public static function add_helper_path($path) {
		self::$helper_paths[] = $path;
	}

	/** Loads the named helpers. */
	public static function load_helper() {
		$helpers = func_get_args();
		foreach ($helpers as $name) {
			if (!isset(self::$loaded_helpers[$name]) && self::load(self::$helper_paths, $name) === false) {
				throw new AFK_Exception("Unknown helper: $name");
			} else {
				// There's no significance to the stored value: we're just using
				// the array keys as a set.
				self::$loaded_helpers[$name] = true;
			}
		}
	}

	/** Searches a list of directories for the named PHP file. */
	private static function load(array $paths, $name) {
		foreach ($paths as $path) {
			$file = "$path/$name.php";
			if (is_file($file)) {
				require $file;
				return $file;
			}
		}
		return false;
	}

	// }}}

	// Diagnostics {{{

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

	// }}}

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
		self::register_autoloader();

		self::add_helper_path(AFK_ROOT . '/helpers');
		self::add_class_path(AFK_ROOT . '/classes');
		AFK_TemplateEngine::add_paths(AFK_ROOT . '/templates');
		AFK_Registry::set_instance($registry = new AFK_Registry());

		if (defined('APP_ROOT')) {
			self::add_helper_path(APP_ROOT . '/lib/helpers');
			self::add_class_path(APP_ROOT . '/lib/classes');
			self::add_class_path(APP_ROOT . '/classes');
			self::add_class_path(APP_ROOT . '/handlers');

			include(APP_ROOT . '/config.php');
			if (file_exists(APP_ROOT . '/lib/lib.php')) {
				include(APP_ROOT . '/lib/lib.php');
			}
		}

		$registry->routes = routes()->get_router();
		return init();
	}

	/** Basic dispatcher logic. Feel free to write your own dispatcher. */
	public static function process_request(AFK_Router $router, $extra_filters=array()) {
		self::fix_superglobals();

		$p = new AFK_Pipeline();
		$p->add(new AFK_ExceptionTrapFilter());
		$p->add(new AFK_RouteFilter($router, $_SERVER, $_REQUEST));
		foreach ($extra_filters as $filter) {
			$p->add($filter);
		}
		$p->add(new AFK_DispatchFilter());
		$p->add(new AFK_RenderFilter());
		$p->start(AFK_Registry::context());
	}

	// }}}
}
