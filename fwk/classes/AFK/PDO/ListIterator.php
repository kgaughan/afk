<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_PDO_ListIterator implements Iterator {

	private $stmt;
	private $n;
	private $current;

	public function __construct(PDOStatement $stmt) {
		$this->stmt = $stmt;
		$this->n = -1;
		$this->current = false;
	}

	public function rewind() {
		// Erm...
		$this->next();
	}

	public function current() {
		return $this->valid() ? $this->current[0] : false;
	}

	public function key() {
		return $this->n;
	}

	public function next() {
		if ($this->current = $this->stmt->fetch(PDO::FETCH_NUM)) {
			$this->n++;
		} else {
			$this->stmt->closeCursor();
		}
		return $this->current();
	}

	public function valid() {
		return $this->current !== false;
	}
}
