<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A cache that chains several caches together, each of which is less local
 * and slower than the next. The cache chain starts out empty, making it
 * effectively a null cache.
 *
 * @author Keith Gaughan
 */
class AFK_Cache_Chained implements AFK_Cache
{
	private $chain;

	public function __construct()
	{
		$this->chain = array();
	}

	/**
	 * Append another cache onto the end of the chain.
	 */
	public function append(AFK_Cache $c)
	{
		$this->chain[] = $c;
	}

	public function invalidate($id)
	{
		foreach ($this->chain as $c) {
			$c->invalidate($id);
		}
	}

	public function invalidate_all($max_age=0)
	{
		foreach ($this->chain as $c) {
			$c->invalidate_all($max_age);
		}
	}

	public function load($id, $max_age=300)
	{
		for ($i = 0; $i < count($this->chain); $i++) {
			$result = $this->chain[$i]->load($id, $max_age);
			if (!is_null($result)) {
				for ($j = 0; $j < $i; $j++) {
					$this->chain[$j]->save($id, $result);
				}
				return $result;
			}
		}
		return null;
	}

	public function save($id, $item)
	{
		foreach ($this->chain as $c) {
			$c->save($id, $item);
		}
	}
}
