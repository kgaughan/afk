<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2010. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_NullLockingStrategy extends AFK_LockingStrategy
{
	protected function lock_int($key)
	{
		// Always succeeds.
		return true;
	}

	public function unlock_int($lock, $key)
	{
		// Do nothing.
	}
}
