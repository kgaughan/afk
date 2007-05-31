<?php
class DefaultHandler extends AFK_HandlerBase {

	public function on_get(AFK_Context $ctx) {
		header('HTTP/1.1 404 Not Found');
		$ctx->view('error404');
		$ctx->page_title = 'Something appears to have gone horribly wrong!';
	}
}
?>
