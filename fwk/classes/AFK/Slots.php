<?php
/**
 * A slot is a placeholder whose content can be generated in one place and
 * output in a completely different place elsewhere.
 */
class AFK_Slots {

	/* Stuff for handling slots. */
	private $current = null;
	private $slots = array();

	/** Checks if the named slot has content. */
	public function has($slot) {
		return isset($this->slots[$slot]);
	}

	/** Writes out the content in the given slot. */
	public function get($slot, $default='') {
		echo $this->has($slot) ? $this->slots[$slot] : $default;
	}

	/** Sets the contents of the given slot. */
	public function set($slot, $contents) {
		$this->slots[$slot] = $contents;
	}

	/** Appends content to the given slot. */
	public function append($slot, $contents) {
		$this->slots[$slot] .= $contents;
	}

	/**
	 * Delimit the start of a block of code which will generate content for
	 * the given slot.
	 */
	public function start($slot) {
		if (!is_null($this->current)) {
			throw new AFK_SlotException("Cannot start new slot '$slot': already in slot '{$this->current}'.");
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
}