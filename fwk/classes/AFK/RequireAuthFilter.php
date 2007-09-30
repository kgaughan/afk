<?php
class AFK_RequireAuthFilter implements AFK_Filter {

	public function execute(AFK_Pipeline $pipe, $ctx) {
		// Fetching the current user will force authorisation.
		AFK_Users::current();
		$pipe->do_next($ctx);
	}
}
