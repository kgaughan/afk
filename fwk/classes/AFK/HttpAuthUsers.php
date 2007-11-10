<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 *
 */
abstract class AFK_HttpAuthUsers extends AFK_Users {

	private $realm;

	public function __construct($realm) {
		$this->realm = $realm;
	}

	protected function get_current_user_id() {
		static $id = null;

		$ctx = AFK_Registry::context();
		if (is_null($id) && $ctx->__isset('PHP_AUTH_USER')) {
			$id = $this->authenticate($ctx->PHP_AUTH_USER,
				md5("{$ctx->PHP_AUTH_USER}:{$this->realm}:{$ctx->PHP_AUTH_PW}"));
		}

		if (is_null($id)) {
			$this->require_auth();
		}

		return $id;
	}

	protected abstract function authenticate($username, $hash);

	protected function require_auth() {
		static $called = false;
		if (!$called) {
			$called = true;
			// TODO: Support Digest too.
			throw new AFK_HttpException(
				'You are not authorised for access.', 401,
				array('WWW-Authenticate' => "Basic realm=\"{$this->realm}\""));
		}
	}
}
