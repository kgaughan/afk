<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

abstract class AFK_PDO_IteratorBase implements Iterator
{
	private $stmt;
	protected $n;
	protected $current;
	private $open;
	private $peeked;

	public function __construct(PDOStatement $stmt)
	{
		$this->stmt = $stmt;
		$this->n = -1;
		$this->current = false;
		$this->open = true;
		$this->peeked = false;
	}

	protected function _current($peek=false)
	{
		if ($this->open && $this->current === false) {
			if (!$peek || !$this->peeked) {
				$this->current = $this->_fetch($this->stmt);
				if ($this->current === false) {
					$this->stmt->closeCursor();
					$this->open = false;
				} else {
					$this->_process_current_row();
				}
			}
		}
		$this->peeked = $peek;
		return $this->current;
	}

	protected abstract function _fetch(PDOStatement $stmt);

	protected function _process_current_row()
	{
		$this->n++;
	}

	public function rewind()
	{
		// Erm...
		$this->next();
	}

	public function key()
	{
		return $this->n;
	}

	public function next()
	{
		// Resetting our cached current value will force the next item to be
		// fetched.
		if (!$this->peeked) {
			$this->current = false;
		}
		return $this->current();
	}

	public function valid()
	{
		return $this->_current(true) !== false;
	}
}
