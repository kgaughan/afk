<?php
/**
 * Implement this if you're creating a pipeline filter.
 */
interface AFK_Filter {

	/**
	 * Executes the action represented by this filter.
	 *
	 * @param  $pipe  The current pipeline.
	 * @param  $ctx   The current processing context.
	 */
	function execute(AFK_Pipeline $pipe, $ctx);
}
