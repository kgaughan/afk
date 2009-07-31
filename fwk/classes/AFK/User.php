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
	private $groups = array();

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
		return $this->id !== AFK_Users::ANONYMOUS;
	}

	// Capabilities {{{

	public function add_capabilities(array $to_add) {
		$this->caps = array_unique(array_merge($this->caps, $to_add));
	}

	public function remove_capabilities(array $to_remove) {
		$this->caps = array_diff($this->caps, $to_remove);
	}

	public function can() {
		$reqs = array_unique(func_get_args());
		return count($reqs) == count(array_intersect($this->caps, $reqs));
	}

	// }}}

	// Groups {{{

	public function add_groups(array $to_add) {
		$this->groups = array_unique(array_merge($this->groups, $to_add));
	}

	public function remove_groups(array $to_remove) {
		$this->groups = array_diff($this->groups, $to_remove);
	}

	public function member_of() {
		return count(array_intersect($this->groups, array_unique(func_get_args()))) > 0;
	}

	// }}}
}
