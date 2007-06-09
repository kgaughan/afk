<?php
class DefaultHandler extends AFK_HandlerBase {

	public function on_get(AFK_Context $ctx) {
		header('HTTP/1.1 404 Not Found');
		$ctx->change_view('error404');
		$ctx->page_title = 'You are wandering through a maze of tunnels, all alike...';
	}
}
?>
