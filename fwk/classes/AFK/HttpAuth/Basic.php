<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_HttpAuth_Basic extends AFK_HttpAuth {

	protected function check_impl(AFK_Environment $env, $expected) {
		$a1 = $env->PHP_AUTH_USER . ':' . self::$realm . ':' . $env->PHP_AUTH_PW;
		return $expected == md5($a1);
	}

	/**
	 *
	 */
	protected function get_name() {
		return 'Basic';
	}

	protected function get_authenticate_header() {
		return 'Basic realm="' . self::$realm . '"';
	}
}
