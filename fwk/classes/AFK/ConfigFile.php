<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Windows .ini-style configuration file representation.
 */
class AFK_ConfigFile
{
	private $sections;

	public function __construct()
	{
		$this->sections = array();
	}

	public function has_section($section)
	{
		return array_key_exists($section, $this->sections);
	}

	public function add_section($section)
	{
		if (!$this->has_section($section)) {
			$this->sections[$section] = array();
		}
	}

	public function has_option($section, $option)
	{
		return $this->has_section($section) && array_key_exists($option, $this->sections[$section]);
	}

	public function get($section, $option, $default=null)
	{
		return $this->has_option($section, $option) ? $this->sections[$section][$option] : $default;
	}

	public function get_int($section, $option, $default=null)
	{
		$result = $this->get($section, $option);
		return is_null($result) ? $default : intval($result);
	}

	public function get_bool($section, $option, $default=null)
	{
		$result = $this->get($section, $option);
		if ($result == 1 || $result == 'yes' || $result == 'true' || $result == 'on') {
			return true;
		}
		if ($result == 0 || $result == 'no' || $result == 'false' || $result == 'off') {
			return false;
		}
		return $default;
	}

	public function set($section, $option, $value)
	{
		$this->add_section($section);
		$this->sections[$section][$option] = $value;
	}

	public function unset_section($section)
	{
		unset($this->sections[$section]);
	}

	public function unset_option($section, $option)
	{
		if ($this->has_option($section, $option)) {
			unset($this->sections[$section][$option]);
		}
	}

	public function get_sections()
	{
		return array_keys($this->sections);
	}

	public function get_options($section)
	{
		if ($this->has_section($section)) {
			return array_keys($this->sections[$section]);
		}
		return array();
	}

	public function get_section($section)
	{
		if ($this->has_section($section)) {
			return $this->sections[$section];
		}
		return array();
	}

	public function read($fh)
	{
		$section = 'DEFAULT';
		while (!feof($fh)) {
			$line = rtrim(fgets($fh));
			if ($line == '' || substr($line, 0, 1) == ';' || substr($line, 0, 1) == '#') {
				continue;
			}
			if (substr($line, 0, 1) == '[' && substr($line, -1, 1) == ']') {
				$section = substr($line, 1, -1);
				continue;
			}
			$parts = array_map('trim', explode('=', $line, 2));
			if (count($parts) == 1) {
				$parts[1] = '';
			}
			$this->set($section, $parts[0], $parts[1]);
		}
	}

	public function write($fh)
	{
		$after_first_section = false;
		foreach ($this->sections as $section => $options) {
			if ($after_first_section) {
				fwrite($fh, "\n");
			} else {
				$after_first_section = true;
			}
			fwrite($fh, "[$section]\n");
			foreach ($options as $k => $v) {
				fwrite($fh, "$k=$v\n");
			}
		}
	}

	public function read_file($filename)
	{
		$has_loaded = false;
		if ($fh = @fopen($filename, 'r')) {
			if (flock($fh, LOCK_SH)) {
				$this->read($fh);
				flock($fh, LOCK_UN);
				$has_loaded = true;
			}
			fclose($fh);
		}
		return $has_loaded;
	}

	public function write_file($filename)
	{
		$has_saved = false;
		if ($fh = @fopen($filename, 'w')) {
			if (flock($fh, LOCK_EX)) {
				$this->write($fh);
				flock($fh, LOCK_UN);
				$has_saved = true;
			}
			fclose($fh);
		}
		return $has_saved;
	}
}
