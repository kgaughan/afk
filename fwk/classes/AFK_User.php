<?php
class AFK_User {

	private $id;
	private $username;
	private $caps = array();

	public function __construct($id, $username, $caps=array()) {
		$this->id = $id;
		$this->username = $username;
		$this->add_capabilities($caps);
	}

	public function get_id() {
		return $this->id;
	}

	public function get_username() {
		return $this->username;
	}

	public function get_profile() {
		return null;
	}

	public function add_capabilities($to_add) {
		$this->caps = array_unique(array_merge($this->caps, $to_add));
	}

	public function remove_capabilities($to_remove) {
		$this->caps = array_diff($this->caps, $to_remove);
	}

	public function can() {
		$reqs = func_get_args();
		$reqs = array_unique($reqs);
		return count($reqs) == count(array_intersect($this->caps, $reqs));
	}
}
?>
