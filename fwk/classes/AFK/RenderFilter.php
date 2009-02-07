<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Renders the request, if it can.
 */
class AFK_RenderFilter implements AFK_Filter {

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if ($ctx->rendering_is_allowed()) {
			if (defined('APP_TEMPLATE_ROOT')) {
				AFK_TemplateEngine::add_paths(
					APP_TEMPLATE_ROOT,
					APP_TEMPLATE_ROOT . '/' . strtolower($ctx->_handler));
			}
			$ctx->defaults(array('page_title' => ''));
			$env = array_merge($ctx->as_array(), compact('ctx'));
			$t = new AFK_TemplateEngine();
			try {
				ob_start();
				ob_implicit_flush(false);
				$t->render($ctx->view('default'), $env);
				$pipe->do_next($ctx);
				ob_end_flush();
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}
	}
}
