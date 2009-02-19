<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Forces the current AFK_Users implementation to authenticate the user.
 */
class AFK_RequireAuthFilter implements AFK_Filter {

	public function execute(AFK_Pipeline $pipe, $ctx) {
		AFK_Users::force_auth();
		$pipe->do_next($ctx);
	}
}
