<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A thin wrapper around the expat XML parsing functions with neat support
 * for namespaces.
 */
class AFK_XmlParser {

	// Maximum chunk size we use for parsing (64kB)
	const MAX_CHUNK = 65536;

	private $namespaces;
	private $parser;

	public function __construct() {
		$this->namespaces = array();

		foreach ($this->get_prefixes() as $namespace_uri => $prefix) {
			if (!in_array($prefix, $this->namespaces)) {
				$this->namespaces[$namespace_uri] = $prefix;
			} else {
				throw new AFK_XmlParserException(sprintf(
					"The prefix '%s' is already assigned to a namespace.",
					$prefix));
			}
		}

		$this->parser = xml_parser_create_ns('UTF-8', ' ');
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, '_on_start_tag', '_on_end_tag');
		xml_set_character_data_handler($this->parser, '_on_text');
	}

	public function __destruct() {
		xml_parser_free($this->parser);
		unset($this->parser);
	}

	private function qualify($tag) {
		$parts = explode(' ', $tag, 2);
		if (count($parts) == 2 && array_key_exists($parts[0], $this->namespaces)) {
			return $this->namespaces[$parts[0]] . ':' . $parts[1];
		}
		return $tag;
	}

	public function parse($data, $is_final=true) {
		$len = strlen($data);
		for ($offset = 0; $offset < $len; $offset += self::MAX_CHUNK) {
			$chunk = substr($data, $offset, self::MAX_CHUNK);
			$is_really_final = $is_final && $offset + self::MAX_CHUNK >= $len;
			if (!xml_parse($this->parser, $chunk, $is_really_final)) {
				$code = xml_get_error_code($this->parser);
				throw new AFK_XmlParserException(xml_error_string($code), $code);
			}
		}
	}

	private function _on_start_tag($p, $tag, array $attrs) {
		$qualified_attrs = array();
		foreach ($attrs as $k => $v) {
			$qualified_attrs[$this->qualify($k)] = $v;
		}
		$this->on_start_tag($this->qualify($tag), $qualified_attrs);
	}

	private function _on_end_tag($p, $tag) {
		$this->on_end_tag($this->qualify($tag));
	}

	private function _on_text($p, $text) {
		$this->on_text($text);
	}

	protected function get_prefixes() {
		return array();
	}

	protected function on_start_tag($tag, array $attrs) {
	}

	protected function on_end_tag($tag) {
	}

	protected function on_text($text) {
	}
}
