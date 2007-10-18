<?php
/**
 * Parses and routes the current request URL.
 */
class AFK_RouteFilter implements AFK_Filter {

	private $server;
	private $routes;
	private $request;

	/**
	 * @param  $routes   Routes to use when parsing the request.
	 * @param  $server   Server variables to use. These have the highest
	 *                   priority and are added to the request context
	 *                   first. In production, this will be $_SERVER.
	 * @param  $request  The request variables to use. These have the lowest
	 *                   priority and will be added to the request context
	 *                   after the request has been routed. In production,
	 *                   this will be $_REQUEST.
	 */
	public function __construct(AFK_Routes $routes, array $server, array $request) {
		$this->routes = $routes;
		$this->server = $server;
		$this->request = $request;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		$ctx->merge($this->server);
		if ($this->ensure_canonicalised_uri($ctx)) {
			$result = $this->routes->search($ctx->PATH_INFO);
			if (is_array($result)) {
				// The result is the attributes.
				$ctx->merge($this->server, $result, $this->request);
				$pipe->do_next($ctx);
			} else {
				// Result is a normalised URL. The original request URL was
				// most likely missing a trailing slash or had one it
				// shouldn't have had.
				$path = $ctx->application_root() . $result;
				if ($ctx->QUERY_STRING != '') {
					$path .= '?' . $ctx->QUERY_STRING;
				}
				$ctx->permanent_redirect($path);
			}
		}
	}

	/* Ensures the request URI doesn't contain double-slashes. */
	private function ensure_canonicalised_uri(AFK_Context $ctx) {
		$canon = preg_replace('~(/)/+~', '$1', $ctx->REQUEST_URI);
		if ($canon !== $ctx->REQUEST_URI) {
			$ctx->permanent_redirect($canon);
			return false;
		}
		return true;
	}
}
