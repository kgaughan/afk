<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Allow random headers to be always added to responses.
 */
class AFK_RandomHeadersFilter implements AFK_Filter {

	private $headers;

	/**
	 * @param  headers  Array of headers to always add to responses.
	 */
	public function __construct(array $headers) {
		$this->headers = $headers;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		foreach ($this->headers as $name => $value) {
			$ctx->header("$name: $value", false);
		}
		$pipe->do_next($ctx);
	}
}
