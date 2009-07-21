<?php
class DB_Exception extends Exception {

	private $q;

	public function __construct($msg, $code=0, $q=null) {
		parent::__construct($msg, $code);
		$this->q = $q;
	}

	public function __toString() {
		$msg = get_class($this) . ": {$this->message}\n";
		if (!empty($this->q)) {
			$msg .= $this->q . "\n";
		}
		return $msg;
	}
}
