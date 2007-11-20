<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Thrown when there are problems loading classes, obviously.
 */
class AFK_ClassLoadException extends AFK_HttpException {

	public function __construct($msg) {
		// i.e. 500 Internal Server Error status code.
		parent::__construct($msg, 500);
	}
}
