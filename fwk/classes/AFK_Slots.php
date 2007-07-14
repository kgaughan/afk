<?php
/**
 * A slot is a placeholder whose content can be generated in one place and
 * output in a completely different place elsewhere.
 */ 
class AFK_Slots {

	/* Stuff for handling slots. */
	private static $current = null;
	private static $slots = array();

	/** Checks if the named slot has content. */
	public static function has($slot) {
		return isset(self::$slots[$slot]);
	}

	/** Writes out the content in the given slot. */
	public static function get($slot, $default='') {
		echo self::has($slot) ? self::$slots[$slot] : $default;
	}

	/** Sets the contents of the given slot. */
	public static function set($slot, $contents) {
		self::$slots[$slot] = $contents;
	}

	/** Appends content to the given slot. */
	public static function append($slot, $contents) {
		self::$slots[$slot] .= $contents;
	}

	/**
	 * Delimit the start of a block of code which will generate content for
	 * the given slot.
	 */
	public static function start($slot) {
		if (!is_null(self::$current)) {
			throw new AFK_SlotException("Cannot start new slot '$slot': already in slot '" . self::$current . "'.");
		}
		self::$current = $slot;
		ob_start();
		ob_implicit_flush(false);
	}

	/**
	 * Delimits the end of a block started with ::start().
	 */
	public static function end() {
		if (is_null(self::$current)) {
			throw new AFK_SlotException("Attempt to end a slot while not in a slot.");
		}
		self::set(self::$current, ob_get_contents());
		ob_end_clean();
		self::$current = null;
	}

	/**
	 * Like ::end(), but the delimited content is appended to whatever's
	 * already in the slot.
	 */
	public static function end_append() {
		if (is_null(self::$current)) {
			throw new AFK_SlotException("Attempt to end a slot while not in a slot.");
		}
		self::append(self::$current, ob_get_contents());
		ob_end_clean();
		self::$current = null;
	}
}
?>
