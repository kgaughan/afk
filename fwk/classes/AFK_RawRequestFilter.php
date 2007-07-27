<?php
class AFK_RawRequestFilter implements AFK_Filter {

	const OK = 1;
	const BAD_REQUEST = 2;
	const TOO_BIG = 3;

	private $limit;

	public function __construct($limit=false) {
		$this->limit = $limit;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if (!$this->is_parsed_type($ctx->CONTENT_TYPE)) {
			list($status, $request) = $this->read('php://input', $this->limit);
			if ($status == self::BAD_REQUEST) {
				throw new AFK_HttpException('Could not read request body', 500);
			} elseif ($status == self::TOO_BIG) {
				throw new AFK_HttpException('Request was too big, limit is ' . $this->limit, 413);
			}
			$ctx->_raw = $request;
		}

		$pipe->do_next($ctx);
	}

	private function is_parsed_type($content_type) {
		return $content_type == 'application/x-www-form-urlencoded' || $content_type == 'multipart/form-data';
	}

	private function read($source, $amount=false) {
		$data = false;
		$fp = fopen($source, 'rb');
		if ($fp) {
			$status = self::OK;
			if ($amount === false) {
				$data = stream_get_contents($fp);
			} else {
				$data = fread($fp, $amount);
				if (!feof($fp)) {
					$status = self::TOO_BIG;
				}
			}
			fclose($fp);
		} else {
			$status = self::BAD_REQUEST;
		}
		return array($status, $data);
	}
}
?>
