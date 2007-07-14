<?php
/**
 * A persistent output cache.
 *
 * Use the cache like this:
 *
 * <?php if (AFK_OutputCache::start('foo')) { ?>
 *     ...expensive to generate content...
 * <?php AFK_OutputCache::end() } ?>
 */
class AFK_OutputCache {

	/* Cache backend in use. */
	private static $backend = null;

	/* ID of current cache block. */
	private static $id;

	/**
	 * Specify the implementation of the AFK_Cache interface to use as the
	 * persistence mechanism.
	 */
	public static function set_backend(AFK_Cache $backend) {
		self::$backend = $backend;
	}

	private static function ensure_backend() {
		if (is_null(self::$backend)) {
			self::set_backend(new AFK_Cache_Null());
		}
	}

	/**
	 * Start a cache block, outputting the previously cached content if
	 * it's still valid.
	 *
	 * @param  id       ID of the cache block.
	 * @param  max_age  Maximum age of the block.
	 *
	 * @return True if the cache is valid, false if not.
	 */
	public static function start($id, $max_age=300) {
		self::ensure_backend();
		$content = self::$backend->load($id, $max_age);
		if (!is_null($content)) {
			echo $content;
			return false;
		}
		ob_start();
		ob_implicit_flush(false);
		self::$id = $id;
		return true;
	}

	/** Marks the end the cache block. */
	public static function end() {
		self::$backend->save(self::$id, ob_get_contents());
		ob_end_flush();
	}

	/** Removes an item from the cache. */
	public static function remove($id) {
		self::ensure_backend();
		self::$backend->invalidate($id);
	}
}
?>
