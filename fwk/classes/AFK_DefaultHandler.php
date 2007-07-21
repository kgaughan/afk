<?php
/**
 * A simple fallback handler so authors won't have to write their own.
 */
class AFK_DefaultHandler extends AFK_HandlerBase {

	public function on_get(AFK_Context $ctx) {
		$ctx->page_title = "Lost?";
		$ctx->not_found();
	}
}
?>
