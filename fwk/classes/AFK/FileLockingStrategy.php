<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2010. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_FileLockingStrategy extends AFK_LockingStrategy {

	private $lock_file_template;

	public function __construct($lock_file_template) {
		parent::__construct();
		$this->lock_file_template = $lock_file_template;
	}

	private function make_lock_file_path($key) {
		return sprintf($this->lock_file_template, sha1($key));
	}

	protected function lock_int($key) {
		$lock = @fopen($this->make_lock_file_path($key), 'x+');
		if ($lock === false) {
			return false;
		}
		fwrite($lock, $key);
		fclose($lock);
		return true;
	}

	protected function unlock_int($lock, $key) {
		unlink($this->make_lock_file_path($key));
	}
}

