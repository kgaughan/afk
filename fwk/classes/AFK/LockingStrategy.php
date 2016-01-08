<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2010. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

abstract class AFK_LockingStrategy
{
	private $lock;
	private $key;

	public function __construct()
	{
		$this->lock = false;
		$this->key = false;
	}

	public function lock($key)
	{
		if ($this->lock !== false) {
			throw new AFK_LockingException(
				sprintf("Cannot lock for '%s': lock active for '%s'", $key, $this->key)
			);
		}
		$this->lock = $this->lock_int($key);
		if ($this->lock === false) {
			return false;
		}
		$this->key = $key;
		return true;
	}

	public function unlock()
	{
		if ($this->lock === false) {
			throw new AFK_LockingException('Lock is not active.');
		}
		$this->unlock_int($this->lock, $this->key);
		$this->lock = false;
		$this->key = false;
	}

	/**
	 * @return An object representing the lock if successful, otherwise false.
	 */
	protected abstract function lock_int($key);

	protected abstract function unlock_int($lock, $key);
}
