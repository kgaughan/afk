<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Requires that all requests are done over HTTPS. Useful if you've no other
 * ways of forcing this such as in an Apache .htaccess file.
 */
class AFK_ForceHttpsFilter implements AFK_Filter {

	private $port;

	/**
	 * @param  port  HTTPS port to use, 443 by default.
	 */
	public function __construct($port=443) {
		$this->port = $port;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if ($ctx->is_secure()) {
			$pipe->do_next($ctx);
		} else {
			$uri = 'https://' . $ctx->HTTP_HOST;
			if ($this->port != 443) {
				$uri .= ':' . $this->port;
			}
			$uri .= $ctx->REQUEST_URI;
			$ctx->permanent_redirect($uri);
		}
	}
}
