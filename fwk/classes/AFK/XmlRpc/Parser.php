<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_XmlRpc_Parser extends AFK_XmlParser {

	private $method_name;

	private $tag_stack;
	private $expected_tags;
	private $current_tag;

	private $data_stack;
	private $current_value;

	private $is_fault = false;

	public function __construct() {
		parent::__construct();

		$this->method_name = null;

		$this->tag_stack = array();
		$this->expected_tags = $this->get_expected_subtags(null);
		$this->current_tag = null;

		$this->data_stack = array();
		$this->current_value = array();
	}

	protected function on_start_tag($new_tag, array $_attrs) {
		if (is_array($this->expected_tags) && in_array($new_tag, $this->expected_tags)) {
			$this->tag_stack[] = array($this->current_tag, $this->expected_tags);
			$this->expected_tags = $this->get_expected_subtags($new_tag);

			if ($new_tag == 'array' || $new_tag == 'struct') {
				$this->data_stack[] = $this->current_value;
				$this->current_value = array();
			} elseif ($new_tag == 'member') {
				$this->data_stack[] = $this->current_value;
				// Place for the member name.
				$this->data_stack[] = null;
				$this->current_value = null;
			} elseif ($new_tag == 'value') {
				if (is_array($this->current_value)) {
					$this->current_value[] = null;
				} else {
					$this->current_value = null;
				}
			} elseif ($new_tag == 'fault') {
				$this->is_fault = true;
			}

			$this->current_tag = $new_tag;
		} elseif ($this->expected_tags !== true) {
			throw new AFK_XmlParserException("Unexpected tag: '$new_tag'");
		}
	}

	protected function on_end_tag($new_tag) {
		list($this->current_tag, $this->expected_tags) = array_pop($this->tag_stack);
		if ($new_tag == 'array' || $new_tag == 'struct') {
			$to_append = $this->current_value;
			$top = array_pop($this->data_stack);
			if (is_array($top)) {
				$this->current_value = $top;
				$this->current_value[count($this->current_value) - 1] = $to_append;
			} elseif (is_string($top)) {
				$this->current_value = array_pop($this->data_stack);
				$this->current_value[$top] = $to_append;
			}
		} elseif ($new_tag == 'member') {
			$name = array_pop($this->data_stack);
			$value = $this->current_value;
			$this->current_value = array_pop($this->data_stack);
			$this->current_value[$name] = $value;
		}
	}

	protected function on_text($text) {
		if ($this->current_tag == 'value' || $this->expected_tags === true) {
			$text = trim($text);
			$i_head = count($this->data_stack) - 1;
			switch ($this->current_tag) {
			case 'methodName':
				$this->method_name = $text;
				break;

			case 'name':
				$this->data_stack[$i_head] = $text;
				break;

			case 'i4':
			case 'int':
				$this->set_current(intval($text));
				break;

			case 'double':
				$this->set_current(doubleval($text));
				break;

			case 'boolean':
				$this->set_current($text == '1');
				break;

			case 'dateTime.iso8601':
				// We (the server) assume UTC because we've no way of knowing
				// otherwise. DW should have included timezone offset
				// support, but he's a jerk.
				$p = strptime($text, "%Y%m%dT%T");
				if ($p === false) {
					throw new AFK_XmlParserException("Unparseable dateTime: '$text'");
				}
				$d = new DateTime();
				$d->setDate($p['tm_year'] + 1900, $p['tm_mon'] + 1, $p['tm_mday']);
				$d->setTime($p['tm_hour'], $p['tm_min'], $p['tm_sec']);
				$d->setTimezone(new DateTimeZone('UTC'));
				$this->set_current($d);
				break;

			case 'base64':
				$this->set_current(new AFK_Blob(base64_decode($text)));
				break;

			case 'string':
				$this->set_current($text);
				break;

			case 'value':
				if ($text != '') {
					$this->set_current($text);
				}
				break;
			}
		}
	}

	private function set_current($value) {
		if (is_array($this->current_value)) {
			$this->current_value[count($this->current_value) - 1] = $value;
		} else {
			$this->current_value = $value;
		}
	}

	public function get_result() {
		if ($this->is_fault) {
			if (isset($this->current_value[0])) {
				$fault = $this->current_value[0];
				if (!isset($fault['faultString'])) {
					$fault['faultString'] = '';
				}
				if (!isset($fault['faultCode'])) {
					$fault['faultCode'] = 0;
				}
			} else {
				$fault = array('faultCode' => 0, 'faultString' => '');
			}
			return new AFK_XmlRpc_Fault($fault['faultCode'], $fault['faultString']);
		}
		return array($this->method_name, $this->current_value);
	}

	private function get_expected_subtags($tag) {
		static $branches = null;
		static $leaves = null;

		if (is_null($branches)) {
			// Tags that can contain other tags, and which ones are expected
			// within them.
			$branches = array(
				null => array('methodCall', 'methodResponse'),
				'methodCall' => array('methodName', 'params'),
				'methodResponse' => array('params', 'fault'),
				'fault' => array('value'),
				'params' => array('param'),
				'param' => array('value'),
				'value' => array(
					'i4', 'int', 'double', 'boolean', 'dateTime.iso8601',
					'string', 'base64', 'struct', 'array'),
				'struct' => array('member'),
				'array' => array('data'),
				'data' => array('value'),
				'member' => array('name', 'value'));

			// Leaf tags - those that are expected to contain values.
			$leaves = array(
				'methodName', 'name',
				'i4', 'int', 'double', 'boolean', 'dateTime.iso8601',
				'string', 'base64');
		}

		return array_key_exists($tag, $branches) ? $branches[$tag] : in_array($tag, $leaves);
	}

	public static function serialise_request($method, array $args) {
		$root = new AFK_ElementNode('methodCall');
		$root->methodName($method);
		if (count($args) > 0) {
			$container = $root->params();
			foreach ($args as $arg) {
				self::serialise_value($container->param()->value(), $arg);
			}
		}
		return $root->as_xml();
	}

	public static function serialise_response($value) {
		$root = new AFK_ElementNode('methodResponse');
		if (get_class($value) == 'AFK_XmlRpc_Fault') {
			$container = $root->fault()->value();
		} else {
			$container = $root->params()->param()->value();
		}
		self::serialise_value($container, $value);
		return $root->as_xml();
	}

	public static function serialise_value(AFK_ElementNode $parent, $value) {
		if (is_array($value)) {
			$is_numeric = true;
			foreach ($value as $k => $_v) {
				if (!is_int($k)) {
					$is_numeric = false;
					break;
				}
			}
			if ($is_numeric) {
				$container = $parent->array()->data();
				foreach ($value as $v) {
					self::serialise_value($container->value(), $v);
				}
			} else {
				$container = $parent->struct();
				foreach ($value as $k => $v) {
					$member = $container->member()->with('name', $k);
					self::serialise_value($member->value(), $v);
				}
			}
		} elseif (is_object($value)) {
			switch (get_class($value)) {
			case 'DateTime':
				$parent->child('dateTime.iso8601', $value->format('Ymd\TH:i:s'));
				break;
			case 'AFK_Blob':
				$parent->base64(base64_encode($value->blob));
				break;
			default:
				$container = $parent->struct();
				foreach ($value as $k => $v) {
					$member = $container->member()->with('name', $k);
					self::serialise_value($member->value(), $v);
				}
			}
		} elseif (is_int($value)) {
			$parent->int($value);
		} elseif (is_double($value)) {
			$parent->double($value);
		} elseif (is_bool($value)) {
			$parent->boolean($value === true ? '1' : '0');
		} else {
			$parent->string((string) $value);
		}
	}
}
