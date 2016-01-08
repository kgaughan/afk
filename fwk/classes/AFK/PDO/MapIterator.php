<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_PDO_MapIterator extends AFK_PDO_IteratorBase
{
	public function key()
	{
		$current = $this->_current();
		return $current === false ? null : $current[0];
	}

	public function current()
	{
		$current = $this->_current();
		return $current === false ? null : $current[1];
	}

	protected function _fetch(PDOStatement $stmt)
	{
		return $stmt->fetch(PDO::FETCH_NUM);
	}
}
