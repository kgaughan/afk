<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

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
		return get_class($this) . " in {$this->file} at {$this->line}:\nCode {$this->code}: {$this->message}\n\n";
	}
}
