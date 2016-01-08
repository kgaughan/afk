<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2010. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */


/**
 * Represents a list of paths to load files from.
 *
 * @author Keith Gaughan
 */
class AFK_PathList
{
	private $paths = array();
	private $path_pattern;

	public function __construct($path_pattern="%s/%s.php")
	{
		$this->path_pattern = $path_pattern;
	}

	public function prepend($path)
	{
		array_unshift($this->paths, $path);
	}

	public function append($path)
	{
		$this->paths[] = $path;
	}

	public function find($name)
	{
		foreach ($this->paths as $d) {
			$path = sprintf($this->path_pattern, $d, $name);
			if (file_exists($path)) {
				return $path;
			}
		}
		return false;
	}

	public function load($name)
	{
		$path = $this->find($name);
		if ($path !== false) {
			include $path;
			return $path;
		}
		return false;
	}
}
