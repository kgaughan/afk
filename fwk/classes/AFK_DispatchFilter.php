<?php
/**
 *
 */
class AFK_DispatchFilter implements AFK_Filter {

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if (is_null($ctx->_handler)) {
			throw new AFK_Exception('No handler specified.');
		}
		$handler_class = $ctx->_handler . 'Handler';
		if (!class_exists($handler_class)) {
			throw new AFK_Exception("No such handler: $handler_class");
		}
		$handler = new $handler_class();
		$handler->handle($ctx);
		$pipe->do_next($ctx);
	}
}
?>
