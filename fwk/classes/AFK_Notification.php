<?php
class AFK_Notification {

	const REQUIRED = 'Required field';
	const INVALID  = 'Invalid format';

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
}

class AFK_NotificationMessage {

	public $field;
	public $msg;

	public function __construct($field, $msg) {
		$this->field = $field;
		$this->msg = $msg;
	}
}
?>
