<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Core filter callbacks.
 *
 * @author Keith Gaughan
 */
class AFK_CoreFilters {

	/**
	 * Dispatches the request to the appropriate request handler class, if
	 * possible.
	 */
	public static function dispatch(AFK_Pipeline $pipe, $ctx) {
		if (is_null($ctx->_handler)) {
			throw new AFK_Exception('No handler specified.');
		}
		$handler_class = $ctx->_handler . 'Handler';
		try {
			// The next line will trigger an AFK_ClassLoadException if no such
			// handler exists. It also ensures that the autoloader will be
			// called. Simply attempting to instantiate the class does not.
			class_exists($handler_class);
		} catch (AFK_ClassLoadException $clex) {
			throw new AFK_ClassLoadException(sprintf("No such handler: %s (%s)", $handler_class, $clex->getMessage()));
		}
		$handler = new $handler_class();
		$handler->handle($ctx);
		unset($hander_class, $handler);
		$pipe->do_next($ctx);
	}

	/**
	 * Forces the current AFK_Users implementation to authenticate the user.
	 */
	public static function require_auth(AFK_Pipeline $pipe, $ctx) {
		AFK_Users::force_auth();
		$pipe->do_next($ctx);
	}
}
