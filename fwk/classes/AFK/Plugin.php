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
 * about.ini consists of lines of key-value pairs, with the key separated
 * from the value with a equals sign. At a minimum, the following should be
 * specified:
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

	public function __construct() {
		// So that plugins know where they are.
		$robj = new ReflectionObject($this);
		$this->root = dirname($robj->getFileName());

		// Register event listeners.
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

	/**
	 * Loads the given plugins from the given location.
	 *
	 * @param  $location  Path to plugin directories.
	 * @param  $active    List of plugins to load.
	 */
	public static function load($location, $active=array()) {
		foreach ($active as $name) {
			$d = "$location/$name";
			if (is_dir($d) && is_file("$d/plugin.php") && is_file("$d/about.ini")) {
				require "$d/plugin.php";
			}
		}
	}
}
