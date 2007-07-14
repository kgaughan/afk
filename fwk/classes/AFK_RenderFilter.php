<?php
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
			$t = new AFK_TemplateEngine();
			try {
				ob_start();
				ob_implicit_flush(false);
				$ctx->defaults(array('page_title' => ''));
				$t->render($ctx->view('default'), $ctx->as_array());
				$pipe->do_next($ctx);
				ob_end_flush();
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}
	}
}
?>
