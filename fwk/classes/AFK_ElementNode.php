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

	private $node;

	public function __construct($elem, $nss=array()) {
		if (is_string($elem)) {
			$xml = "<$elem";
			foreach ($nss as $ns=>$uri) {
				$xml .= ' xmlns';
				if (!is_numeric($ns)) {
					$xml .= ':' . $ns;
				}
				$xml .= '="' . e($uri) . '"';
			}
			$xml .= '/>';

			$this->node = new SimpleXMLElement($xml);
		} else {
			// Otherwise, this is a subnode we're creating.
			$this->node = $elem;
		}
	}

	public function attr($name, $value='', $ns=null) {
		$this->node->addAttribute($name, $value, $ns);
		return $this;
	}

	public function child($name, $text=null, $ns=null) {
		if ($text == '') {
			$text = null;
		} elseif (!is_null($text)) {
			$text = e($text);
		}
		$child = $this->node->addChild($name, $text, $ns);
		return new AFK_ElementNode($child);
	}

	public function with($name, $text=null, $ns=null) {
		$this->child($name, $text, $ns);
		return $this;
	}

	public function as_xml() {
		return $this->node->asXML();
	}
}
?>
