<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Does the final stage of request processing: rendering.
 *
 * @author Keith Gaughan
 */
class AFK_RenderFilter implements AFK_Filter {

	private $engine;

	public function __construct(AFK_TemplateEngine $engine) {
		$this->engine = $engine;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if ($ctx->rendering_is_allowed()) {
			$this->engine->add_path(APP_TEMPLATE_ROOT);
			$this->engine->add_path(APP_TEMPLATE_ROOT . '/' . strtolower($ctx->_handler));
			$ctx->defaults(array('page_title' => ''));
			$env = array_merge($ctx->as_array(), compact('ctx'));
			try {
				ob_start();
				ob_implicit_flush(false);
				$this->engine->render($ctx->view('default'), $env);
				$pipe->do_next($ctx);
				ob_end_flush();
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}
	}
}
