<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Base class for implementing session handling classes.
 *
 * @author Keith Gaughan
 */
abstract class AFK_Session {

	public function __construct() {
		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc'));
	}

	public abstract function open($save_path, $name);

	public function close() {
		return true;
	}

	public abstract function read($id);

	public abstract function write($id, $data);

	public abstract function destroy($id);

	public abstract function gc($max_age);
}
