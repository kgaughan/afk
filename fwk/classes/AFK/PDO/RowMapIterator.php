<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_PDO_RowMapIterator extends AFK_PDO_IteratorBase
{
	private $k;
	private $current;

	public function __construct(PDOStatement $stmt)
	{
		parent::__construct($stmt);
		$this->k = false;
	}

	protected function _fetch(PDOStatement $stmt)
	{
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function current()
	{
		return $this->_current();
	}

	public function key()
	{
		return $this->k;
	}

	protected function _process_current_row()
	{
		$this->k = array_shift($this->current);
	}
}
