<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_Notification {

	const REQUIRED = 'Required field';
	const INVALID  = 'Invalid format';

	const SESSION = '_afk_notification';

	private $msgs = array();

	public function is_valid() {
		return count($this->msgs) == 0;
	}

	public function add($field, $msg) {
		$this->msgs[] = new AFK_NotificationMessage($field, $msg);
	}

	public function get_messages($field) {
		$msgs = array();
		foreach ($this->msgs as $n) {
			if ($n->field == $field) {
				$msgs[] = $n->msg;
			}
		}
		return $msgs;
	}

	public function get_all() {
		usort($this->msgs, array($this, 'comparator'));
		// Assume the user won't be a fool and modify it.
		return $this->msgs;
	}

	private function comparator($a, $b) {
		$result = strcmp($a->field, $b->field);
		if ($result == 0) {
			$result = strcasecmp($a->msg, $b->msg);
		}
		return $result;
	}

	public static function get() {
		if (!isset($_SESSION[self::SESSION])) {
			$_SESSION[self::SESSION] = new AFK_Notification();
		}
		return $_SESSION[self::SESSION];
	}

	public static function render() {
		$msgs = self::get()->get_all();
		unset($_SESSION[self::SESSION]);
		if (count($msgs) > 0) {
			echo '<div class="errors"><ul>';
			foreach ($msgs as $m) {
				echo '<li>', e($m->msg), '</li>';
			}
			echo '</ul></div>';
		}
	}
}

class AFK_NotificationMessage {

	public $field;
	public $msg;

	public function __construct($field, $msg) {
		$this->field = $field;
		$this->msg = $msg;
	}
}
