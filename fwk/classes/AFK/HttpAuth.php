<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

interface AFK_HttpAuth {

	/**
	 * @return The name of this auth method as it would appear in the
	 *         Authorization and WWW-Authenticate headers.
	 */
	function get_name();

	/**
	 * @param  $realm  The authentication realm to use.
	 *
	 * @return The data part of the WWW-Authenticate header.
	 */
	function get_authenticate_header($realm);

	/**
	 * Initialises the authentication method with the authorisation data
	 * from the Authorize header.
	 *
	 * @param  $realm  The authentication realm to use.
	 * @param  $data   The contents of the Authorize header, less the method.
	 *
	 * @return The username specified in the Authorize header.
	 */
	function initialise($realm, $data);

	/**
	 * Checks that the authorisation data the authentication method was
	 * initialised with matches the expected authorisation data for the
	 * user being authenticated.
	 *
	 * @param  $env       The current environment (such as the request
	 *                    context).
	 * @param  $expected  The expected authorisation data, such as a
	 *                    password, A1 hash (Basic/Digest), &c.
	 *
	 * @return True if there's a match, otherwise false.
	 */
	function verify(AFK_Environment $env, $expected);
}
