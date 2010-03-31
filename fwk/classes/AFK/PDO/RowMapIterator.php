<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_PDO_RowMapIterator implements Iterator {

	private $stmt;
	private $k;
	private $current;

	public function __construct(PDOStatement $stmt) {
		$this->stmt = $stmt;
		$this->k = false;
		$this->current = false;
	}

	public function rewind() {
		// Erm...
		$this->next();
	}

	public function current() {
		return $this->current;
	}

	public function key() {
		return $this->k;
	}

	public function next() {
		if ($this->current = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
			$this->k = array_shift($this->current);
		} else {
			$this->stmt->closeCursor();
		}
		return $this->current;
	}

	public function valid() {
		return $this->current !== false;
	}
}
