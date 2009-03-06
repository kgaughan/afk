<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A wrapper around the DOM to make building XML documents easier.
 *
 * @author Keith Gaughan
 */
class AFK_ElementNode {

	const CHARSET = 'utf-8';

	private $node;
	private $ns;

	/**
	 * @param  $elem  Root element name.
	 * @param  $nss   Namespace of the root element.
	 */
	public function __construct($elem, $ns=null) {
		$this->ns = $ns;
		if (is_object($elem) && get_class($elem) == 'DOMElement') {
			// Subnode.
			$dom = $elem->ownerDocument;
			$this->node = $elem;
		} else {
			// Root element.
			$dom = new DOMDocument('1.0', self::CHARSET);
			if (is_null($ns)) {
				$this->node = $dom->createElement($elem);
			} else {
				$this->node = $dom->createElementNS($ns, $elem);
			}
			$dom->appendChild($this->node);
		}
	}

	/**
	 * @return The tree serialised as an XML document.
	 */
	public function as_xml() {
		$dom = $this->node->ownerDocument;
		$dom->normalizeDocument();
		return $dom->saveXML();
	}

	/**
	 * @return The current node serialised as an XML document fragment.
	 */
	public function as_xml_fragment() {
		return $this->node->ownerDocument->saveXML($this->node);
	}

	private function e($text) {
		return htmlspecialchars($text, ENT_QUOTES, self::CHARSET);
	}

	// Attributes {{{

	/**
	 * Adds an attribute to this element.
	 *
	 * @return $this
	 */
	public function attr($name, $value, $ns=null) {
		if (is_null($ns)) {
			$this->node->setAttribute($name, $value);
		} else {
			$prefix = $this->node->ownerDocument->lookupPrefix($ns);
			if ($prefix != '') {
				$name = "$prefix:$name";
			}
			$this->node->setAttributeNS($ns, $name, $value);
		}
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
	 * @param  $ns    Namespace to use, null to inherit from its parent.
	 *
	 * @return Newly-created child node.
	 */
	public function child($name, $text=null, $ns=null) {
		if ($text == '') {
			$text = null;
		} elseif (!is_null($text)) {
			$text = $this->e($text);
		}
		if (is_null($ns)) {
			$ns = $this->ns;
		}
		$dom = $this->node->ownerDocument;
		if (is_null($ns)) {
			$child = $dom->createElement($name, $text);
		} else {
			$prefix = $dom->lookupPrefix($ns);
			if ($prefix != '') {
				$name = "$prefix:$name";
			}
			$child = $dom->createElementNS($ns, $name, $text);
		}
		$this->node->appendChild($child);
		return new AFK_ElementNode($child, $this->ns);
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

	public function with_raw($raw) {
		$dom = $this->node->ownerDocument;
		$this->node->appendChild($dom->createTextNode($raw));
		return $this;
	}

	public function with_text($text) {
		return $this->with_raw($this->e($text));
	}

	/// }}}
}
