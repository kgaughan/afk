<?php
/**
 * Common logic for AFK-specific exceptions.
 */
class AFK_Exception extends Exception {

	public function __construct($msg, $code=0) {
		parent::__construct($msg, $code);
	}

	public function __toString() {
		return get_class($this) . " in {$this->file} at {$this->line}:\nCode {$this->code}: {$this->message}\n\n";
	}
}
