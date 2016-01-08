<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A request processing pipeline. Pipelines can be inserted into other
 * pipelines as filters.
 */
class AFK_Pipeline implements AFK_Filter
{
	private $filters = array();

	/**
	 * Adds a filter to the end of the pipeline, which can be either a
	 * callback or an instance of AFK_Filter; callbacks must accept the same
	 * arguments as AFK_Filter->execute().
	 *
	 * @return Self.
	 */
	public function add($filter)
	{
		if (is_array($filter) || is_string($filter)) {
			$this->filters[] = $filter;
		} else {
			$this->filters[] = array($filter, 'execute');
		}
		return $this;
	}

	/**
	 * Starts processing the pipeline.
	 *
	 * @param $ctx  The pipeline processing context.
	 */
	public function start($ctx)
	{
		// Maintainer's note: Don't put a type annotation on this method!
		// AFK_Context isn't the only class that could be used as a pipeline
		// processing context.
		reset($this->filters);
		$this->do_next($ctx);
	}

	/**
	 * Processes the next filter in the pipeline.
	 */
	public function do_next($ctx)
	{
		$filter = current($this->filters);
		if ($filter !== false) {
			next($this->filters);
			call_user_func($filter, $this, $ctx);
		}
	}

	/**
	 * Moves to the last filter in the pipeline.
	 */
	public function to_end()
	{
		end($this->filters);
	}

	public function execute(AFK_Pipeline $pipe, $ctx)
	{
		$this->start($ctx);
		$pipe->do_next($ctx);
	}
}
