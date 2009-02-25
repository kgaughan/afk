<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Converts an XML request into something more tractable for code further down
 * the pipeline.
 *
 * It validates the request against a schema, parses the request into a
 * SimpleXMLElement. It then invokes a mathod called parse_<root>(), where
 * _root_ is the name of the document's root element. The result of this
 * method is an array which is merged into the request context.
 *
 * @author Keith Gaughan
 */
class AFK_XmlParseFilter implements AFK_Filter {

	private $content_type;
	private $schema;

	/**
	 * @param  $content_type  Content type(s) to parse. Can be an array
	 *                        or string.
	 * @param  $schema        Location of schema to validate against.
	 */
	public function __construct($content_type, $schema=false) {
		if (!is_array($content_type)) {
			$content_type = array($content_type);
		}
		$this->content_type = $content_type;
		$this->schema = $schema;
	}

	// Filter Execution {{{

	public function execute(AFK_Pipeline $pipe, $ctx) {
		list($request_content_type) = explode(';', $ctx->CONTENT_TYPE, 2);
		if (in_array($request_content_type, $this->content_type, true)) {
			$doc = $this->load_and_validate($ctx->_raw);
			$ctx->_root_element = $doc->getName();
			$ctx->merge($this->parse($doc));
			unset($doc);
		}
		$pipe->do_next($ctx);
	}

	// }}}

	// Initial Parsing and Validation {{{

	/**
	 * @return Parsed and validated document.
	 */
	public function load_and_validate($xml) {
		if ($xml == '') {
			throw new AFK_ParseException("Invalid document:\nEmpty request!");
		}

		$old_use_errors = libxml_use_internal_errors(true);
		libxml_clear_errors();

		$result = false;
		$doc = @DOMDocument::loadXML(
			$xml,
			LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET | LIBXML_NOCDATA);
		if ($doc !== false && ($this->schema === false || $this->validate($doc, $this->schema))) {
			$result = simplexml_import_dom($doc);
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($old_use_errors);
		if (count($errors) > 0) {
			// TODO: Hack! This should be done more cleanly!
			throw new AFK_ParseException(sprintf(
				"Invalid document:\n%s", $this->join_errors($errors)));
		}

		return $result;
	}

	/**
	 * Validates the documents if a schema was given.
	 */
	protected function validate(DOMDocument $doc, $schema) {
		 return $doc->relaxNGValidate($schema);
	}

	private function join_errors(array $errors) {
		return implode("\n", array_map(array($this, 'error_to_string'), $errors));
	}

	private function error_to_string($error) {
		return sprintf(
			"%s at line %s, column %s: %s",
			$this->get_level_name($error->level),
			$error->line, $error->column, trim($error->message));
	}

	private function get_level_name($level) {
		switch ($level) {
		case LIBXML_ERR_WARNING:
			return "Warning";
		case LIBXML_ERR_ERROR:
			return "Error";
		case LIBXML_ERR_FATAL:
			return "Fatal error";
		}
		return "Unknown error";
	}

	// }}}

	public function parse(SimpleXMLElement $doc) {
		$callable = array($this, 'parse_' . $doc->getName());
		if (is_callable($callable)) {
			return call_user_func($callable, $doc);
		}
		throw new AFK_ParseException(sprintf(
			"Cannot accept messages rooted by the '%s' element.",
			$doc->getName()));
	}
}
