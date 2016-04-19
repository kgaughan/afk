<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Parses and routes the current request URL.
 */
class AFK_RouteFilter implements AFK_Filter
{
	private $map;
	private $server;
	private $request;

	/**
	 * @param  $map      Routing map to use when parsing the request.
	 * @param  $server   Server variables to use. These have the highest
	 *                   priority and are added to the request context
	 *                   first. In production, this will be $_SERVER.
	 * @param  $request  The request variables to use. These have the lowest
	 *                   priority and will be added to the request context
	 *                   after the request has been routed. In production,
	 *                   this will be $_REQUEST.
	 */
	public function __construct(AFK_RouteMap $map, array $server, array $request)
	{
		$this->map = $map;
		$this->server = $server;
		$this->request = $request;
	}

	// Filter Execution {{{

	public function execute(AFK_Pipeline $pipe, $ctx)
	{
		$ctx->merge($this->server);
		// Ensures the request URI doesn't contain double-slashes.
		$canon = preg_replace('~/{2,}~', '/', $ctx->REQUEST_URI);
		if ($canon !== $ctx->REQUEST_URI) {
			$ctx->permanent_redirect($canon);
		}

		// Generate reliable QUERY_STRING and PATH_INFO values.
		$app_root = substr($ctx->SCRIPT_NAME, 0, strrpos($ctx->SCRIPT_NAME, '/'));
		$parts = parse_url($ctx->REQUEST_URI);
		if (isset($parts['query'])) {
			$ctx->QUERY_STRING = $parts['query'];
		}
		$ctx->PATH_INFO = substr($parts['path'], strlen($app_root));

		$result = $this->map->search($ctx->PATH_INFO);
		if (is_string($result)) {
			// Result is a normalised URL. The original request URL was
			// most likely missing a trailing slash or had one it
			// shouldn't have had. Also, ensure there's no duplicate
			// slashes.
			$path = $ctx->application_root() . substr($result, 1);
			if ($ctx->QUERY_STRING != '') {
				$path .= '?' . $ctx->QUERY_STRING;
			}
			$ctx->permanent_redirect($path);
			$pipe->to_end();
		} else {
			// The result is the attributes.
			$ctx->merge($result, $this->request);
			unset($result);
		}
		$pipe->do_next($ctx);
	}

	// }}}
}
