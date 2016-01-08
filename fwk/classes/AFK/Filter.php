<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Implement this if you're creating a pipeline filter.
 */
interface AFK_Filter
{
	/**
	 * Executes the action represented by this filter.
	 *
	 * @param $pipe  The current pipeline.
	 * @param $ctx   The current processing context.
	 */
	function execute(AFK_Pipeline $pipe, $ctx);
}
