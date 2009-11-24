<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents a group.
 *
 * @author Keith Gaughan
 */
class AFK_Group {

	private $id;
	private $name;
	private $slug;

	public function  __construct($id, $name, $slug) {
		$this->id = $id;
		$this->name = $name;
		$this->slug = $slug;
	}

	/** @warning For internal use only: prefer AFK_Group::get_slug() */
	public function get_id() {
		return $this->id;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function __toString() {
		return $this->name;
	}

	public function save() {
		// TODO: Do magic with AFK_Groups.
	}
}
