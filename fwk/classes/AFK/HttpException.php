<?php
class AFK_HttpException extends AFK_Exception {

	private $headers = array();

	public function __construct($msg, $code, $headers=array()) {
		parent::__construct($msg, $code);
		$this->add_headers($headers);
	}

	public function add_headers(array $headers) {
		foreach ($headers as $n=>$v) {
			if (is_array($v)) {
				$v = implode(', ', $v);
			}
			$v = str_replace("\n", "\t\r\n", $v);
			if (isset($this->headers[$n])) {
				$this->headers[$n] .= ', ' . $v;
			} else {
				$this->headers[$n] = $v;
			}
		}
	}

	public function get_headers() {
		$result = array();
		foreach ($this->headers as $n=>$v) {
			$result[] = "$n: $v";
		}
		return $result;
	}
}
