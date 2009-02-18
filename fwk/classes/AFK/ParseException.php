<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Thrown when a request could not be parsed.
 */
class AFK_ParseException extends AFK_HttpException {

	public function __construct($msg) {
		parent::__construct($msg, AFK_Context::BAD_REQUEST);
	}
}
