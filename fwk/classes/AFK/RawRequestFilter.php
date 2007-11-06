<?php
class AFK_RawRequestFilter implements AFK_Filter {

	private $limit;

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
				throw new AFK_HttpException('Request was too big, limit is ' . $this->limit, 413);
			}
		}
		fclose($fp);
		return $data;
	}
}
