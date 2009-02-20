<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

abstract class AFK_HttpAuth {

	protected static $realm = 'Realm';
	private static $methods = array();

	public static function set_realm($realm) {
		self::$realm = $realm;
	}

	public static function add_method(AFK_HttpAuth $method) {
		self::$methods[$method->get_name()] = $method;
	}

	private static function collect_authenticate_headers() {
		$headers = array();
		foreach (self::$methods as $method) {
			$headers[] = $method->get_authenticate_header();
		}
		return $headers;
	}

	public static function check(AFK_Environment $env, $expected) {
		foreach (self::$methods as $method) {
			if ($method->check_impl($env, $expected)) {
				return true;
			}
		}
		return false;
	}

	public static function force_authentication() {
		static $called = false;
		if (!$called) {
			$called = true;
			throw new AFK_HttpException(
				'You are not authorised for access.',
				AFK_Context::UNAUTHORISED,
				array('WWW-Authenticate' => self::collect_authenticate_headers()));
		}
	}

	public static function get_username(AFK_Environment $env) {
		return isset($env->PHP_AUTH_USER) ? $env->PHP_AUTH_USER : false;
	}

	protected abstract function check_impl(AFK_Environment $env, $expected);

	protected abstract function get_name();

	protected abstract function get_authenticate_header();
}
