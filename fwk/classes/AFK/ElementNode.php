<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A wrapper around SimpleXML to make building XML documents easier.
 *
 * @author Keith Gaughan
 */
class AFK_ElementNode {

	private $_node;

	/**
	 * @param  $elem  Root element name.
	 * @param  $nss   List of namespaces keyed by prefix.
	 */
	public function __construct($elem, $nss=array()) {
		if (is_string($elem)) {
			$xml = "<$elem";
			foreach ($nss as $ns => $uri) {
				$xml .= ' xmlns';
				if (!is_numeric($ns)) {
					$xml .= ':' . $ns;
				}
				$xml .= '="' . e($uri) . '"';
			}
			$xml .= '/>';

			$this->_node = new SimpleXMLElement($xml);
		} else {
			// Otherwise, this is a subnode we're creating.
			$this->_node = $elem;
		}
	}

	// Attributes {{{

	/**
	 * Adds an attribute to this element.
	 *
	 * @return $this
	 */
	public function attr($name, $value='', $ns=null) {
		// Workaround for dumbass PHP bug #46769.
		if (!is_null($ns) && strpos($name, ':') === false) {
			$name = "ipitythefool:$name";
		}
		$this->_node->addAttribute($name, $value, $ns);
		return $this;
	}

	public function __set($name, $value) {
		$this->attr($name, $value);
	}

	// }}}

	// Children {{{

	/**
	 * Creates a child element, returning that child node.
	 *
	 * @param  $name  Element name.
	 * @param  $text  Text to insert into the element.
	 * @param  $ns    Namespace to use, null for the default.
	 *
	 * @return Newly-created child node.
	 */
	public function child($name, $text=null, $ns=null) {
		if ($text == '') {
			$text = null;
		} elseif (!is_null($text)) {
			$text = e($text);
		}
		$child = $this->_node->addChild($name, $text, $ns);
		return new AFK_ElementNode($child);
	}

	public function __call($name, array $args) {
		$text = count($args) > 0 ? $args[0] : null;
		$ns = count($args) > 1 ? $args[1] : null;
		return $this->child($name, $text, $ns);
	}

	/**
	 * Creates a child element, returning the *current* element.
	 *
	 * @return $this.
	 */
	public function with($name, $text=null, $ns=null) {
		$this->child($name, $text, $ns);
		return $this;
	}

	/// }}}

	/**
	 * @return The tree as a SimpleXMLElement.
	 */
	public function as_xml() {
		return $this->_node->asXML();
	}
}
