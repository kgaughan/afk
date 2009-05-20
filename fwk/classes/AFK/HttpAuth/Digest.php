<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_HttpAuth_Digest implements AFK_HttpAuth {

	private $opaque;
	private $private_key;
	private $nonce_lifetime;
	private $fields;
	private $realm;
	private $stale;

	public function __construct($opaque, $private_key, $nonce_lifetime=300) {
		$this->opaque = $opaque;
		$this->private_key = $private_key;
		$this->nonce_lifetime = $nonce_lifetime;
		$this->stale = false;
	}

	public function get_name() {
		return 'Digest';
	}

	public function get_authenticate_header($realm) {
		$ctx = AFK_Registry::context();

		return sprintf(
			'realm="%s", domain="%s", qop=auth, algorithm=MD5, nonce="%s", opaque="%s", stale="%s"',
			$realm, $ctx->application_root_path(), $this->make_nonce($ctx), $this->opaque,
			$this->stale ? 'true' : 'false');
	}

	private function make_nonce(AFK_Environment $env) {
		// Method taken straight from Paul James' HTTP Digest code at
		// http://www.peej.co.uk/files/httpdigest.phps
		$time = ceil(time() / $this->nonce_lifetime) * $this->nonce_lifetime;
		return md5(date('Y-m-d H:i', $time) . ':' . $env->REMOTE_ADDR . ':' . $this->private_key);
	}

	public function initialise($realm, $data) {
		$this->realm = $realm;
		$this->fields = $this->parse($data);
		return $this->fields['username'];
	}

	public function verify(AFK_Environment $env, $expected) {
		if ($this->are_fields_good($env)) {
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
		$this->stale = true;
		return false;
	}

	private function are_fields_good(AFK_Environment $env) {
		return
			$this->fields['opaque'] == $this->opaque &&
			$this->fields['uri'] == $env->REQUEST_URI &&
			$this->fields['nonce'] == $this->make_nonce($env);
	}

	private function parse($data) {
		$missing = array(
			'nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1,
			'username' => 1, 'uri' => 1, 'response' => 1);

		$fields = array();
		preg_match_all('~(\w+)=(?:[\'"]([^\'"]+)[\'"]|([^\s,]+))~', $data, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$fields[$m[1]] = $m[2] != '' ? $m[2] : $m[3];
			unset($missing[$m[1]]);
		}

		return count($missing) > 0 ? false : $fields;
	}
}
