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

	private static $class_paths = array();
	private static $helper_paths = array();
	private static $loaded_helpers = array();

	public static function register_autoloader() {
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
		return self::load(self::$class_paths, str_replace('_', '/', $name));
	}

	/** Adds a new directory to use when searching for helpers. */
	public static function add_helper_path($path) {
		self::$helper_paths[] = $path;
	}

	/** Loads the named helpers. */
	public static function load_helper() {
		$helpers = func_get_args();
		foreach ($helpers as $name) {
			if (!isset(self::$loaded_helpers[$name]) && !self::load(self::$helper_paths, $name)) {
				throw new AFK_Exception("Unknown helper: $name");
			} else {
				// There's no significance to the stored value: we're just using
				// the array keys as a set.
				self::$loaded_helpers[$name] = true;
			}
		}
	}

	/** Searches a list of directories for the named PHP file. */
	private static function load($paths, $name) {
		foreach ($paths as $path) {
			$file = "$path/$name.php";
			if (is_file($file)) {
				require $file;
				return true;
			}
		}
		return false;
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

	/** Fixes the superglobals by removing any magic quotes, if present. */
	public static function fix_superglobals() {
		if (get_magic_quotes_gpc()) {
			self::fix_magic_quotes($_GET);
			self::fix_magic_quotes($_POST);
			self::fix_magic_quotes($_COOKIE);
			self::fix_magic_quotes($_REQUEST);
			set_magic_quotes_runtime(0);
		}
	}

	/** Walks an array, fixing magic quotes. */
	public static function fix_magic_quotes(&$a) {
		$keys =& array_keys($a);
		$n    =  count($keys);

		for ($i = 0; $i < $n; $i++) {
			$val =& $a[$keys[$i]];
			if (is_array($val)) {
				self::fix_magic_quotes($val);
			} else {
				$val = stripslashes($val);
			}
		}
	}

	/** Basic dispatcher logic. Feel free to write your own dispatcher. */
	public static function process_request($routes, $extra_filters=array()) {
		self::fix_superglobals();

		$p = new AFK_Pipeline();
		$p->add(new AFK_ExceptionTrapFilter());
		$p->add(new AFK_RouteFilter($routes, $_SERVER, $_REQUEST));
		foreach ($extra_filters as $filter) {
			$p->add($filter);
		}
		$p->add(new AFK_DispatchFilter());
		$p->add(new AFK_RenderFilter());
		$p->start(AFK_Registry::context());
	}
}
