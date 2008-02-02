<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Represents a HTTP status code which must be processed immediately, such
 * as a 404 Page Not Found.
 *
 * @author Keith Gaughan
 */
class AFK_HttpException extends AFK_Exception {

	private $headers = array();

	/**
	 * @param  $msg      Message to render.
	 * @param  $code     HTTP status code.
	 * @param  $headers  Associative array of HTTP headers to attach to this
	 *                   exception.
	 */
	public function __construct($msg, $code, $headers=array()) {
		parent::__construct($msg, $code);
		$this->add_headers($headers);
	}

	/**
	 * Adds additional HTTP headers to this exception.
	 */
	public function add_headers(array $headers) {
		foreach ($headers as $n => $v) {
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

	/**
	 * @return The headers associated with this
	 */
	public function get_headers() {
		$result = array();
		foreach ($this->headers as $n => $v) {
			$result[] = "$n: $v";
		}
		return $result;
	}
}
