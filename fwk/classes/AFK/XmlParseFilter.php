<?php
class AFK_XmlParseFilter implements AFK_Filter {

	private $content_type;
	private $schema_path;

	public function __construct($content_type, $schema_path) {
		$this->content_type = $content_type;
		// APP_ROOT . '/schemas/blackreg.rng'
		$this->schema_location = $schema_location;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		if ($ctx->CONTENT_TYPE === $this->content_type) {
			try {
				$ctx->merge($this->parse($this->load_and_validate($ctx->_raw)));
			} catch (AFK_ParseException $pex) {
				$ctx->bad_request($pex->getMessage());
			}
		}
		$pipe->do_next($ctx);
	}

	/**
	 * @return Parsed and validated document, or false on failure.
	 */
	private function load_and_validate($xml) {
		$old_use_errors = libxml_use_internal_errors(true);
		libxml_clear_errors();

		$result = false;
		$doc = DOMDocument::loadXML($xml);
		if ($doc !== false && $doc->relaxNGValidate($this->schema_location)) {
			$result = simplexml_import_dom($doc);
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($old_use_errors);
		if (count($errors) == 0) {
			$message = "Invalid document:\n\n * " .
				implode("\n * ", array_map(array($this, 'error_to_string'), $errors));
			throw new AFK_ParseException($message);
		}

		return $result;
	}

	private function error_to_string($error) {
		$message = " at line {$error->line}, column {$error->column}: {$error->message}";
		switch ($error->level) {
		case LIBXML_ERR_WARNING:
			return "Warning" . $message;
		case LIBXML_ERR_ERROR:
			return "Error" . $message;
		case LIBXML_ERR_FATAL:
			return "Fatal Error" . $message;
		}
		return "Unknown" . $message;
	}

	public function parse(SimpleXMLElement $doc) {
		$callable = array($this, 'parse_' . $doc->getName());
		if (is_callable($callable)) {
			return call_user_func($callable, $doc);
		}
		throw new AFK_ParseException(
			"Cannot accept messages rooted by the '" . $doc->getName() . "' element.");
	}
}
