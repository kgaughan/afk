<?php
/**
 * Represents a HTTP request handler.
 */
interface AFK_Handler {

	/** Handles a HTTP request. */
	function handle(AFK_Context $ctx);
}
