<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_PDO_MapIterator implements Iterator {

	private $stmt;
	private $k;
	private $v;
	private $is_valid;

	public function __construct(PDOStatement $stmt) {
		$this->stmt = $stmt;
		$this->k = false;
		$this->v = null;
		$this->is_valid = true;
	}

	public function rewind() {
		// Erm...
		$this->next();
	}

	public function current() {
		return $this->v;
	}

	public function key() {
		return $this->k;
	}

	public function next() {
		if ($r = $this->stmt->fetch(PDO::FETCH_NUM)) {
			list($this->k, $this->v) = $r;
			return $this->v;
		}
		$this->is_valid = false;
		$this->stmt->closeCursor();
		return false;
	}

	public function valid() {
		return $this->is_valid !== false;
	}
}
