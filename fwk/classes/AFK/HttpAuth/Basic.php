<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_HttpAuth_Basic implements AFK_HttpAuth
{
	private $a1 = false;

	public function get_name()
	{
		return 'Basic';
	}

	public function get_authenticate_header($realm)
	{
		return 'realm="' . $realm . '"';
	}

	public function initialise($realm, $data)
	{
		list($username, $password) = explode(':', base64_decode($data), 2);
		$this->a1 = md5("$username:$realm:$password");
		return $username;
	}

	public function verify(AFK_Environment $env, $expected)
	{
		return $this->a1 == $expected;
	}
}
