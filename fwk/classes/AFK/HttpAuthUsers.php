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

	private $id;
	private $actual_id;

	public function __construct() {
		parent::__construct();
		$this->id = false;
		$this->actual_id = false;
	}

	public function act_as_effective_user_impl($id) {
		if ($this->actual_id === false) {
			$this->actual_id = $this->id;
		}
		$this->id = $id;
	}

	public function revert_to_actual_user_impl() {
		if ($this->actual_id !== false) {
			$this->id = $this->actual_id;
			$this->actual_id = false;
		}
	}

	protected function get_current_user_id() {
		if ($this->id === false && ($id = $this->check()) !== false) {
			$this->id = $id;
		}
		return $this->id !== false ? $this->id : AFK_Users::ANONYMOUS;
	}

	private function check() {
		$ctx = AFK_Registry::context();
		$details = $this->authenticate(AFK_HttpAuth::get_username($ctx));
		if ($details !== false) {
			list($id, $expected) = $details;
			if (AFK_HttpAuth::check($ctx, $expected)) {
				return $id;
			}
		}
		return false;
	}

	/**
	 * @return array(id, a1_hash), or false if no such user.
	 */
	protected abstract function authenticate($username);

	protected function require_auth() {
		AFK_HttpAuth::force_authentication();
	}
}
