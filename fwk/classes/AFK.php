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
class AFK
{
	public static $loader;

	/**
	 * @return The first non-empty argument in the arguments passed in.
	 */
	public static function coalesce()
	{
		$args = func_get_args();
		$default = $args[0];
		foreach ($args as $arg) {
			if (!empty($arg)) {
				return $arg;
			}
		}
		return $default;
	}

	/**
	 * Ensures the given list of contants are set up by setting them if
	 * they are not already defined.
	 */
	public static function ensure_constants(array $cs)
	{
		foreach ($cs as $name => $value) {
			if (!defined($name)) {
				define($name, $value);
			}
		}
	}

	public static function include_if_exists($path, $otherwise=null)
	{
		if (file_exists($path)) {
			include $path;
		} elseif (!is_null($otherwise)) {
			include $otherwise;
		}
	}

	public static function register_autoloader()
	{
		// Attempt to register an autoloader cleanly...
		spl_autoload_register(array(self::$loader, 'load_class'));
	}

	public static function load_helper()
	{
		$args = func_get_args();
		call_user_func_array(array(self::$loader, 'load_helper'), $args);
	}

	public static function add_helper_path($path)
	{
		self::$loader->add_helper_path($path);
	}

	/**
	 * Does a clean HTML dump of the given variable.
	 */
	public static function dump()
	{
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

	/**
	 * A wrapper around getopt() to make it behave more sanely.
	 */
	public static function get_opt($opts, $longopts=null, $argv=null)
	{
		if (is_null($argv)) {
			$argv = $GLOBALS['argv'];
		}
		if (is_null($longopts)) {
			// This avoids some noise on PHP <5.3.0 where PHP complains about
			// long options not being supported.
			$parsed = getopt($opts);
		} else {
			$parsed = getopt($opts, $longopts);
		}

		// We use this filter to eliminate any options from our argument list.
		$filter = array();
		foreach ($parsed as $k => $vs) {
			if (strlen($k) == 1) {
				$flag = '-' . $k;
			} else {
				$flag = '--' . $k;
				if ($vs !== false) {
					$flag .= '=';
				}
			}
			if ($vs === false) {
				$filter[$flag] = false;
			} elseif (is_array($vs)) {
				foreach ($vs as $v) {
					$filter[$flag . $v] = false;
				}
			} else {
				$filter[$flag . $vs] = false;
			}
		}

		$args = array();

		$copy_args = false;
		foreach (array_slice($argv, 1) as $arg) {
			if ($arg == '--') {
				// Any arguments from this point on should just be copied.
				$copy_args = true;
			} elseif ($copy_args || !isset($filter[$arg])) {
				// Copy the current argument.
				$args[] = $arg;
			}
		}

		return array($parsed, $args);
	}

	// Workarounds {{{

	/**
	 * Fixes the superglobals by removing any magic quotes, if present.
	 */
	public static function fix_superglobals()
	{
		// Magic quotes were removed in PHP 5.4.0, and the *_magic_quotes_*
		// methods removed in PHP 7.0. This should be safe on PHP 7 due to
		// shortcircuiting.
		if (version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc()) {
			$cb = array('AFK', 'fix_magic_quotes');
			array_walk_recursive($_GET, $cb);
			array_walk_recursive($_POST, $cb);
			array_walk_recursive($_COOKIE, $cb);
			array_walk_recursive($_REQUEST, $cb);
			set_magic_quotes_runtime(0);
		}
	}

	private static function fix_magic_quotes(&$val, $_)
	{
		$val = stripslashes($val);
	}

	/// }}}

	// Framework {{{

	/**
	 * Basic bootstrapping logic. Feel free to write your own.
	 */
	public static function bootstrap()
	{
		self::fix_superglobals();

		self::$loader = new AFK_Loader();
		self::$loader->add_helper_path(AFK_ROOT . '/helpers');
		if (defined('APP_ROOT')) {
			self::$loader->add_helper_path(APP_ROOT . '/lib/helpers');
		}
		self::$loader->add_class_path(AFK_ROOT . '/classes');
		if (defined('APP_ROOT')) {
			self::$loader->add_class_path(APP_ROOT . '/lib/classes');
			self::$loader->add_class_path(APP_ROOT . '/classes');
			self::$loader->add_class_path(APP_ROOT . '/handlers');
		}
		self::register_autoloader();

		AFK_Registry::set_instance($registry = new AFK_Registry());

		if (defined('APP_ROOT')) {
			include APP_ROOT . '/config.php';
			if (file_exists(APP_ROOT . '/lib/lib.php')) {
				include APP_ROOT . '/lib/lib.php';
			}
		}

		if (function_exists('routes')) {
			$registry->routes = routes()->get_map();
		}

		$paths = new AFK_PathList();
		$paths->prepend(AFK_ROOT . '/templates');
		if (defined('APP_TEMPLATE_ROOT')) {
			$paths->prepend(APP_TEMPLATE_ROOT);
		}
		$registry->template_engine = new AFK_TemplateEngine($paths);

		if (function_exists('init')) {
			return init();
		}
		return array();
	}

	/**
	 * Basic dispatcher logic. Feel free to write your own dispatcher.
	 */
	public static function process_request(AFK_RouteMap $map, array $extra_filters=array())
	{
		$p = new AFK_Pipeline();
		$p->add(new AFK_ExceptionTrapFilter());
		$p->add(new AFK_RouteFilter($map, $_SERVER, $_REQUEST));
		$p->add(array('AFK_CoreFilters', 'populate_uploaded_files'));
		foreach ($extra_filters as $filter) {
			$p->add($filter);
		}
		$p->add(array('AFK_CoreFilters', 'dispatch'));
		$p->add(array('AFK_CoreFilters', 'render'));
		$p->start(AFK_Registry::context());
	}

	/**
	 * Helper method for writing a cron job runner.
	 */
	public static function run_callables(array $callables, $lock_directory=false)
	{
		if (count($callables) == 0) {
			return;
		}

		if ($lock_directory === false || count($callables) == 1 && $callables[0] == 'cronjob') {
			$locker = new AFK_NullLockingStrategy();
		} elseif (is_dir($lock_directory)) {
			$locker = new AFK_FileLockingStrategy($lock_directory . '/job.%s.lock');
		} else {
			throw new AFK_Exception(sprintf("Bad lock directory: '%s'", $lock_directory));
		}

		set_time_limit(0);
		foreach ($callables as $callable) {
			self::run_callable($callable, $locker);
		}
	}

	private static function run_callable($callable_name, AFK_LockingStrategy $locker)
	{
		$callable = explode('::', $callable_name, 2);
		// Callable is a function rather than a static method?
		if (count($callable) == 1) {
			$callable = $callable[0];
		}
		if (!is_callable($callable)) {
			printf("No such callable: %s()\n", $callable_name);
		} elseif (!$locker->lock(APP_ROOT . ':' . $callable_name)) {
			printf("Cannot lock for callable job %s()\n", $callable_name);
		} else {
			try {
				call_user_func($callable);
				$locker->unlock();
			} catch (Exception $ex) {
				$locker->unlock();
				list($unhandled) = trigger_event(
					'afk:internal_error',
					array('ctx' => null, 'exception' => $ex)
				);
				// The nuclear option - you should have something to handle
				// errors.
				if ($unhandled) {
					AFK::dump($ex);
				}
			}
		}
	}

	// }}}
}
