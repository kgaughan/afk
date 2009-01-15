<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A subclass of AFK_Context specifically for testing purposes.
 *
 * @author Keith Gaughan
 */
class AFK_TestContext extends AFK_Context {

	private $status_code = 200;
	private $headers = array();

	public function header($header, $replace=true, $status=null) {
		if (substr($header, 5) == 'HTTP/') {
			list(, $new_status) = explode(' ', $header, 3);
			$this->status_code = intval($new_status);
		} else {
			if (!is_null($status)) {
				$this->status_code = intval($new_status);
			}
			list($name, $value) = explode(':', $header, 2);
			$name = strtolower($name);
			if ($replace || !array_key_exists($name, $this->headers)) {
				$this->headers[$name] = trim($value);
			}
		}
	}

	public function get_response_status() {
		return $this->status_code;
	}

	public function get_response_headers() {
		return $this->headers;
	}
}
