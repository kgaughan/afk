<?php
class AFK_XmlParseFilter implements AFK_Filter {

	private $content_type;
	private $schema_path;

	public function __construct($content_type, $schema_location) {
		if (!is_array($content_type)) {
			$content_type = array($content_type);
		}
		$this->content_type = $content_type;
		$this->schema_location = $schema_location;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		list($request_content_type) = explode(';', $ctx->CONTENT_TYPE, 2);
		if (array_search($request_content_type, $this->content_type, true) !== false) {
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
	public function load_and_validate($xml) {
		$old_use_errors = libxml_use_internal_errors(true);
		libxml_clear_errors();

		$result = false;
		$doc = DOMDocument::loadXML($xml,
			LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET | LIBXML_NOCDATA);
		if ($doc !== false && $doc->relaxNGValidate($this->schema_location)) {
			$result = simplexml_import_dom($doc);
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($old_use_errors);
		if (count($errors) > 0) {
			// TODO: Hack! This should be done more cleanly!
			throw new AFK_ParseException("Invalid document:\n" .
				implode("\n", array_map(array($this, 'error_to_string'), $errors)));
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
