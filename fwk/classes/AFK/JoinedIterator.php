<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2010. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Allows iteration over a number of different iterable objects in
 * succession.
 */
class AFK_JoinedIterator implements Iterator
{
	private $iterators = array();

	public function __construct()
	{
		$args = func_get_args();
		$i = 0;
		foreach ($args as $a) {
			if ($a instanceof Iterator) {
				$this->iterators[] = $a;
			} elseif ($a instanceof IteratorAggregate) {
				$this->iterators[] = $a->getIterator();
			} elseif (is_array($a)) {
				$this->iterators[] = new ArrayIterator($a);
			} else {
				throw AFK_NotIterableException(
					sprintf("Argument %d is not iterable", $i)
				);
			}
			$i++;
		}
	}

	public function rewind()
	{
		// Do nothing.
	}

	public function current()
	{
		return $this->iterators[0]->current();
	}

	public function key()
	{
		return $this->iterators[0]->key();
	}

	public function valid()
	{
		return count($this->iterators) > 0 && $this->iterators[0]->valid();
	}

	public function next()
	{
		if (count($this->iterators) > 1 && !$this->iterators[0]->valid()) {
			array_shift($this->iterators);
		}
		$this->iterators[0]->next();
	}
}
