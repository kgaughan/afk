<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A simple fallback handler so authors won't have to write their own.
 *
 * @author Keith Gaughan
 */
class AFK_DefaultHandler extends AFK_HandlerBase {

	public function on_get(AFK_Context $ctx) {
		$ctx->page_title = "Lost?";
		$ctx->not_found();
	}
}
?>
