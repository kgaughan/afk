<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A basic caching mechanism that's not meant to persist over multiple pages.
 *
 * This cache is most useful in cases where you are testing something that
 * requires a caching mechanism or where you need a default one that actually
 * works, i.e., where AFK_Cache_Null is insufficient. This caching mechanism
 * is not, however, meant to be used in production code.
 *
 * Please note that if you are exercising something which uses caching, it
 * ought to be tested using both AFK_Cache_Array and AFK_Cache_Null as both
 * implement different elements of the AFK_Cache interface contract that code
 * using caches ought to expect.
 *
 * @author Keith Gaughan
 */
class AFK_Cache_Array implements AFK_Cache {

	private $cache = array();
	private $timestamps = array();

	public function invalidate($id) {
		unset($this->cache[$id], $this->timestamps[$id]);
	}

	public function invalidate_all($max_age=0) {
		$now = time();
		foreach ($this->timestamps as $id=>$ts) {
			if ($ts + $max_age <= $now) {
				$this->invalidate($id);
			}
		}
	}

	public function load($id, $max_age=300) {
		if (isset($this->cache[$id]) && $this->timestamps[$id] + $max_age > time()) {
			return $this->cache[$id];
		}
		return null;
	}

	public function save($id, $item) {
		$this->cache[$id] = $item;
		$this->timestamps[$id] = time();
	}
}
