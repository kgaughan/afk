<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Container for a string that should be base64 encoded in XML-RPC responses.
 */
class AFK_Blob {

	public $blob;

	public function __construct($blob) {
		$this->blob = $blob;
	}
}
