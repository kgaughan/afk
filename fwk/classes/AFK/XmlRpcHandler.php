<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Basic functionality common to all handlers.
 */
class AFK_XmlRpcHandler implements AFK_Handler {

	public function handle(AFK_Context $ctx) {
		$ctx->header('Allow: POST');

		switch ($ctx->method()) {
		case 'post':
			$this->process_request();
			break;

		default:
			$ctx->no_such_method(array('post'));
			break;
		}
	}
}
