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
class AFK_User
{
	protected $id;
	protected $username;
	private $caps = array();
	private $groups = array();

	public function __construct($id, $username)
	{
		$this->id = $id;
		$this->username = $username;
	}

	public function get_id()
	{
		return $this->id;
	}

	public function get_display_name()
	{
		return $this->username;
	}

	public function get_username()
	{
		return $this->username;
	}

	public function get_profile()
	{
		return null;
	}

	public function is_logged_in()
	{
		return $this->id !== AFK_Users::ANONYMOUS;
	}

	public function __toString()
	{
		return $this->get_display_name();
	}

	// Capabilities {{{

	public function add_capabilities(array $to_add)
	{
		$this->caps = array_unique($to_add + $this->caps);
	}

	public function remove_capabilities(array $to_remove)
	{
		$this->caps = array_diff($this->caps, $to_remove);
	}

	public function can()
	{
		$reqs = func_get_args();
		$reqs = array_unique($reqs);
		return count($reqs) == count(array_intersect($this->caps, $reqs));
	}

	public function get_capabilities()
	{
		return $this->caps;
	}

	// }}}

	// Groups {{{

	public function add_groups(array $to_add)
	{
		$this->groups = array_unique($to_add + $this->groups);
	}

	public function remove_groups(array $to_remove)
	{
		$this->groups = array_diff($this->groups, $to_remove);
	}

	public function set_groups(array $slugs)
	{
		$this->groups = array_unique($slugs);
	}

	public function member_of()
	{
		$groups = func_get_args();
		return count(array_intersect($this->groups, array_unique($groups))) > 0;
	}

	public function get_groups()
	{
		return $this->groups;
	}

	// }}}
}
