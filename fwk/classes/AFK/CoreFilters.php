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
	 * Renders the request, if it can.
	 */
	public static function render(AFK_Pipeline $pipe, $ctx) {
		if ($ctx->rendering_is_allowed()) {
			if (defined('APP_TEMPLATE_ROOT')) {
				AFK_TemplateEngine::add_paths(
					APP_TEMPLATE_ROOT,
					APP_TEMPLATE_ROOT . '/' . strtolower($ctx->_handler));
			}
			try {
				ob_start();
				ob_implicit_flush(false);

				$ctx->defaults(array('page_title' => ''));

				$env = array_merge($ctx->as_array(), compact('ctx'));
				$t = new AFK_TemplateEngine();
				$t->render($ctx->view('default'), $env);
				unset($t, $env);

				$pipe->do_next($ctx);
				ob_end_flush();
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}
	}

	/**
	 * Forces the current AFK_Users implementation to authenticate the user.
	 */
	public static function require_auth(AFK_Pipeline $pipe, $ctx) {
		AFK_Users::force_auth();
		$pipe->do_next($ctx);
	}

}
