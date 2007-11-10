<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A request processing pipeline.
 */
class AFK_Pipeline {

	private $filters = array();

	/**
	 * Adds a filter to the end of the pipeline.
	 *
	 * @return Self.
	 */
	public function add(AFK_Filter $filter) {
		$this->filters[] = $filter;
		return $this;
	}

	/**
	 * Starts processing the pipeline.
	 *
	 * @param  $ctx  The pipeline processing context.
	 */
	public function start($ctx) {
		// Maintainer's note: Don't put a type annotation on this method!
		// AFK_Context isn't the only class that could be used as a pipeline
		// processing context.
		reset($this->filters);
		$this->do_next($ctx);
	}

	/**
	 * Processes the next filter in the pipeline.
	 */
	public function do_next($ctx) {
		$filter = current($this->filters);
		if (is_object($filter)) {
			next($this->filters);
			$filter->execute($this, $ctx);
		}
	}

	/**
	 * Moves to the last filter in the pipeline.
	 */
	public function to_end() {
		end($this->filters);
	}
}
