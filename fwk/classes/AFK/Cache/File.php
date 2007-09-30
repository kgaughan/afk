<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Simple file-based cache.
 *
 * @author Keith Gaughan
 */
class AFK_Cache_File implements AFK_Cache {

	private $cache_path;

	/**
	 * Initialises the class.
	 *
	 * @param  $cache_path  Path to the cache directory. Must end in a slash.
	 */
	public function __construct($cache_path) {
		$this->cache_path = $cache_path;
	}

	public function invalidate($id) {
		$path = $this->cache_path . md5($id);
		if (is_file($path)) {
			unlink($path);
		}
	}

	public function invalidate_all($max_age=0) {
		$dh = dir($this->cache_path);
		while ($file = $dh->read()) {
			if (filemtime($this->cache_path . $file) < time() - $max_age) {
				unlink($this->cache_path . $file);
			}
		}
		$dh->close();
	}

	public function load($id, $max_age=300) {
		$path = $this->cache_path . md5($id);
		if (is_file($path)) {
			if (filemtime($path) >= time() - $max_age) {
				return unserialize(file_get_contents($path));
			}
			unlink($path);
		}
		return null;
	}

	public function save($id, $item) {
		file_put_contents($this->cache_path . md5($id), serialize($item), LOCK_EX);
    }
}
