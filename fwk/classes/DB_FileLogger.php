<?php
/**
 * a logger that echos any queries to standard output for debugging purposes.
 */
class DB_EchoingLogger extends DB_BasicLogger {

	private $location;

	public function __construct($location) {
		$this->location = $location;
	}

	public function log($q) {
		parent::log($q);
		error_log($q, 3, $this->location);
	}
}
?>
