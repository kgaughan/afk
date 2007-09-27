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
	private $backend = null;

	/* ID of current cache block. */
	private $id;

	public function __construct() {
		$this->set_backend(new AFK_Cache_Null());
	}

	/**
	 * Specify the implementation of the AFK_Cache interface to use as the
	 * persistence mechanism.
	 */
	public function set_backend(AFK_Cache $backend) {
		$this->backend = $backend;
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
	public function start($id, $max_age=300) {
		$content = $this->backend->load($id, $max_age);
		if (!is_null($content)) {
			echo $content;
			return false;
		}
		ob_start();
		ob_implicit_flush(false);
		$this->id = $id;
		return true;
	}

	/** Marks the end the cache block. */
	public function end() {
		$this->backend->save($this->id, ob_get_contents());
		ob_end_flush();
	}

	/** Removes an item from the cache. */
	public function remove($id) {
		$this->ensure_backend();
		$this->backend->invalidate($id);
	}
}
