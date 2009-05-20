<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents a user.
 *
 * @author Keith Gaughan
 */
class AFK_User {

	private $id;
	private $username;
	private $caps = array();

	public function __construct($id, $username) {
		$this->id = $id;
		$this->username = $username;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_display_name() {
		return $this->username;
	}

	public function get_username() {
		return $this->username;
	}

	public function get_profile() {
		return null;
	}

	public function is_logged_in() {
		return $this->id !== 0;
	}

	// Capabilities {{{

	public function add_capabilities(array $to_add) {
		$this->caps = array_unique(array_merge($this->caps, $to_add));
	}

	public function remove_capabilities(array $to_remove) {
		$this->caps = array_diff($this->caps, $to_remove);
	}

	public function can() {
		$reqs = func_get_args();
		$reqs = array_unique($reqs);
		return count($reqs) == count(array_intersect($this->caps, $reqs));
	}

	// }}}
}
