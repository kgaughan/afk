<?php
/**
 * A request processing pipeline.
 */
class AFK_Pipeline {

	private $filters = array();

	public function add(AFK_Filter $filter) {
		$this->filters[] = $filter;
		return $this;
	}

	public function start($ctx) {
		reset($this->filters);
		$this->do_next($ctx);
	}

	public function do_next($ctx) {
		$filter = current($this->filters);
		if (is_object($filter)) {
			next($this->filters);
			$filter->execute($this, $ctx);
		}
	}

	public function to_end() {
		end($this->filters);
	}
}
