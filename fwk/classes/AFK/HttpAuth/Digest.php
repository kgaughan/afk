<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_HttpAuth_Basic implements AFK_HttpAuth {

	private $opaque;
	private $fields;
	private $realm;

	public function __construct($opaque) {
		$this->opaque = $opaque;
	}

	public function get_name() {
		return 'Digest';
	}

	public function get_authenticate_header($realm) {
		return sprintf(
			'realm="%s", qop="auth", nonce="%s", opaque="%s"',
			$realm, md5(uniqid(rand(), true)), $this->opaque);
	}

	public function initialise($realm, $data) {
		$this->realm = $realm;
		$this->fields = $this->parse($data);
		return $this->fields['username'];
	}

	public function verify(AFK_Environment $env, $expected) {
		$a2 = md5($env->REQUEST_METHOD . ':' . $this->fields['uri']);
		$valid_response = md5(
			$expected . ':' .
			$this->fields['nonce'] . ':' .
			$this->fields['nc'] . ':' .
			$this->fields['cnonce'] . ':' .
			$this->fields['qop'] . ':' .
			$a2);
		return $this->fields['response'] == $valid_response;
	}

	private function parse($data) {
		$missing = array(
			'nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1,
			'username' => 1, 'uri' => 1, 'response' => 1);

		$fields = array();
		preg_match_all('~(\w+)=(?:([\'"])([^\2]+)\2|([^\s,]+))~', $data, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$fields[$m[1]] = $m[3] != '' ? $m[3] : $m[4];
			unset($missing[$m[1]]);
		}

		return count($missing) > 0 ? false : $fields;
	}
}
