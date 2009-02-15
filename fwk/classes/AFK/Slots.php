<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A slot is a placeholder whose content can be generated in one place and
 * output in a completely different place elsewhere.
 *
 * @author Keith Gaughan
 */
class AFK_Slots {

	// Slot Management {{{

	private $slots = array();

	/** Checks if the named slot has content. */
	public function has($slot) {
		return array_key_exists($slot, $this->slots);
	}

	/** Writes out the content in the given slot. */
	public function get($slot, $default='') {
		if ($this->has($slot)) {
			echo $this->slots[$slot];
			return true;
		}
		echo $default;
		return false;
	}

	/** Sets the contents of the given slot. */
	public function set($slot, $contents) {
		$this->slots[$slot] = $contents;
	}

	/** Appends content to the given slot. */
	public function append($slot, $contents) {
		if ($this->has($slot)) {
			$this->slots[$slot] .= $contents;
		} else {
			$this->slots[$slot] = $contents;
		}
	}

	// }}}

	// Slot Delimiting {{{

	private $current = null;

	/**
	 * Delimit the start of a block of code which will generate content for
	 * the given slot.
	 */
	public function start($slot) {
		if (!is_null($this->current)) {
			throw new AFK_SlotException(sprintf(
				"Cannot start new slot '%s': already in slot '%s'.",
				$slot, $this->current));
		}
		$this->current = $slot;
		ob_start();
		ob_implicit_flush(false);
	}

	/**
	 * Delimits the end of a block started with ::start().
	 */
	public function end() {
		if (is_null($this->current)) {
			throw new AFK_SlotException("Attempt to end a slot while not in a slot.");
		}
		$this->set($this->current, ob_get_contents());
		ob_end_clean();
		$this->current = null;
	}

	/**
	 * Like ::end(), but the delimited content is appended to whatever's
	 * already in the slot.
	 */
	public function end_append() {
		if (is_null($this->current)) {
			throw new AFK_SlotException("Attempt to end a slot while not in a slot.");
		}
		$this->append($this->current, ob_get_contents());
		ob_end_clean();
		$this->current = null;
	}

	// }}}
}
