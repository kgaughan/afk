<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * An implicit invocation event broker.
 *
 * @author Keith Gaughan
 */
class AFK_EventBroker {

	private $callbacks = array();
	private $queue = array();

	public function register($event, $callback, $singleton=false) {
		if (!isset($this->callbacks[$event]) ||
				($singleton && count($this->callbacks[$event]) > 0)) {
			$this->callbacks[$event] = array();
		}

		if (is_string($callback) || is_array($callback)) {
			$this->callbacks[$event][] = $callback;
		} elseif (is_object($callback)) {
			if (method_exists($callback, 'pre')) {
				$this->register("pre:$event", array($callback, 'pre'), $singleton);
			}
			if (method_exists($callback, 'handle')) {
				$this->register($event, array($callback, 'handle'), $singleton);
			}
			if (method_exists($callback, 'post')) {
				$this->register("post:$event", array($callback, 'post'), $singleton);
			}
		}
	}

	public function announce($event, $value) {
		$this->queue[] = array($event, $value);
	}

	public function clear() {
		$this->queue = array();
	}

	public function process() {
		while (count($this->queue) > 0) {
			list($event, $value) = array_shift($this->queue);
			$this->trigger($event, $value, true);
		}
	}

	public function trigger($event, $value, $with_universal_observers=false) {
		if ($with_universal_observers) {
			$stages = array("pre:$event", "pre:all", $event, "all", "post:$event", "post:all");
		} else {
			$stages = array("pre:$event", $event, "post:$event");
		}

		foreach ($stages as $stage) {
			list($continue, $value) = $this->trigger_stage($stage, $value);
			if (!$continue) {
				break;
			}
		}

		return array($continue, $value);
	}

	private function trigger_stage($stage, $value) {
		if (isset($this->callbacks[$stage])) {
			foreach ($this->callbacks[$stage] as $cb) {
				list($continue, $value) = call_user_func($cb, $stage, $value);
				if (!$continue) {
					return array(false, $value);
				}
			}
		}
		return array(true, $value);
	}
}
?>
