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
	private $id;

	public function __construct($realm) {
		parent::__construct();
		$this->realm = $realm;
		$this->id = null;
	}

	protected function get_current_user_id() {
		$ctx = AFK_Registry::context();
		if (is_null($this->id) && $ctx->__isset('PHP_AUTH_USER')) {
			$this->id = $this->authenticate($ctx->PHP_AUTH_USER,
				md5("{$ctx->PHP_AUTH_USER}:{$this->realm}:{$ctx->PHP_AUTH_PW}"));
		}

		if (!is_null($this->id)) {
			return $this->id;
		}

		return AFK_Users::ANONYMOUS;
	}

	protected abstract function authenticate($username, $hash);

	protected function require_auth() {
		static $called = false;
		if (!$called) {
			$called = true;
			// TODO: Support Digest too.
			throw new AFK_HttpException(
				'You are not authorised for access.',
				AFK_Context::UNAUTHORISED,
				array('WWW-Authenticate' => "Basic realm=\"{$this->realm}\""));
		}
	}
}
