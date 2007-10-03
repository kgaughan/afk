<?php
abstract class AFK_Plugin {

	protected $root;

	public function __construct() {
		// So that plugins know where they are.
		$this->root = dirname(__FILE__);

		// Register event listeners.
		$evts = $this->get_events();
		foreach ($evts as $evt => $fn) {
			if (is_numeric($evt)) {
				$evt = $fn;
			}
			register_listener($evt, array($this, $fn));
		}
	}

	protected abstract function get_events();

	public static function load($location, $active=array()) {
		foreach ($active as $name) {
			$d = "$location/$name";
			if (is_dir($d) && is_file("$d/plugin.php") && is_file("$d/about.ini")) {
				require "$d/plugin.php";
			}
		}
	}
}
