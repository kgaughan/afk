<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Grabs the contents of the request when it's not form data and stuffs it
 * into the context variable '_raw'.
 */
class AFK_RawRequestFilter implements AFK_Filter {

	private $limit;

	/**
	 * @param  $limit  Maximum request size to accept. False for no limit.
	 */
	public function __construct($limit=false) {
		$this->limit = $limit;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if (!$this->is_parsed_type($ctx->CONTENT_TYPE)) {
			$ctx->_raw = $this->read('php://input', $this->limit);
		}
		$pipe->do_next($ctx);
	}

	private function is_parsed_type($content_type) {
		return $content_type == 'application/x-www-form-urlencoded' || $content_type == 'multipart/form-data';
	}

	private function read($source, $amount) {
		$data = false;
		$fp = fopen($source, 'rb');
		if (!$fp) {
			throw new AFK_HttpException('Could not read request body', 500);
		}
		if ($amount === false) {
			$data = stream_get_contents($fp);
		} else {
			$data = fread($fp, $amount);
			if (!feof($fp)) {
				fclose($fp);
				throw new AFK_HttpException(sprintf("Request was too big, limit is %d", $this->limit), 413);
			}
		}
		fclose($fp);
		return $data;
	}
}
