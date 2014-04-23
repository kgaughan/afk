<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A plugin.
 *
 * Plugins are generally stored together in a directory. Each plugin is a
 * directory containing at least two files: plugin.php, which contains the
 * plugin code, and about.ini, which contains information about the plugin.
 *
 * Plugins are usually, but not necessarily, subclasses of AFK_Plugin. They
 * don't even have to be classes! The only real requirement is that they
 * register themselves to listen for the events or event stages they're
 * interested in.
 *
 * about.ini consists of sets of lines of key-value pairs, with the key
 * separated from the value with a equals sign. At a minimum, the section
 * '[plugin]' should be present, and it should contain the following:
 *
 *     Key      Explaination
 *     -------  ------------------------------------------------------------
 *     name     The plugin's name.
 *     author   Who wrote it. The format is "Name <email@example.com>".
 *     address  The plugin's homepage where it is documented and can be
 *              downloaded or the author's homepage.
 *     purpose  An explaination of what the plugin is for.
 *     version  The version number of this plugin.
 *     date     When this plugin was last revised in the format YYYY-MM-DD.
 */
abstract class AFK_Plugin {

	/** This plugin's path. */
	protected $root;
	/** The plugin's internal name. */
	protected $name;

	/** Contents of about.ini. */
	protected $about;

	private static $registry = array();

	public function __construct($name=null) {
		$this->root = $this->get_plugin_root();
		$this->name = coalesce($name, basename($this->root));
		self::$registry[$this->name] = $this;
		$this->about = $this->load_configuration();
		$this->register_listeners();
	}

	private function get_plugin_root() {
		$robj = new ReflectionObject($this);
		return dirname($robj->getFileName());
	}

	public function get_internal_name() {
		return $this->name;
	}

	public static function by_name($name) {
		if (isset(self::$registry[$name])) {
			return self::$registry[$name];
		}
		return false;
	}

	// Settings {{{

	protected function load_configuration() {
		$config = new AFK_ConfigFile();
		if ($config->read_file($this->root . '/about.ini') === false) {
			throw new AFK_ConfigurationException(
				sprintf(
					'%s (%s): could not read configuration',
					$this->get_internal_name(),
					get_class($this)));
		}
		return $config;
	}

	public function get_plugin_description() {
		// Backwards compatibility with old-style about.ini files.
		if (!$this->has_section('plugin')) {
			return $this->about->get_section('DEFAULT');
		}
		return $this->about->get_section('plugin');
	}

	protected function get_setting($name, $default=null) {
		$section = defined('STATUS') ? ('settings:' . STATUS) : 'settings';
		return $this->about->get($section, $name, $default);
	}

	protected function get_bool_setting($name, $default=false) {
		return in_array(
			strtolower($this->get_setting($name, $default ? 'yes' : 'no')),
			array('yes', 'on', 'true', '1'));
	}

	protected function get_setting_descriptions() {
		return $this->about->get_section('setting-descriptions');
	}

	// }}}

	// Events {{{

	private function register_listeners() {
		$evts = $this->get_events();
		foreach ($evts as $evt => $fn) {
			if (is_numeric($evt)) {
				$evt = $fn;
			}
			register_listener($evt, array($this, $fn));
		}
	}

	/**
	 * @return A list of methods on this plugin to call on given events.
	 *
	 * @note The list is an associative array where the key is the event stage
	 *       name and the value is the name of the method, but if the key is
	 *       numeric, the name of the method is taken as the same as the name
	 *       of the event stage.
	 */
	protected abstract function get_events();

	// }}}

	/**
	 * Loads the given plugins from the given location.
	 *
	 * @param  $location  Path to plugin directories.
	 * @param  $active    List of plugins to load.
	 */
	public static function load($location, array $active) {
		foreach ($active as $name) {
			$d = "$location/$name";
			if (is_dir($d) && is_file("$d/plugin.php") && is_file("$d/about.ini")) {
				include "$d/plugin.php";
			}
		}
	}
}
