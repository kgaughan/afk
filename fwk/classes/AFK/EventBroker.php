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

	public function register($event, $callback, $singleton=false) {
		if (!isset($this->callbacks[$event]) ||
				($singleton && count($this->callbacks[$event]) > 0)) {
			$this->callbacks[$event] = array();
		}
		$this->callbacks[$event][] = $callback;
	}

	public function trigger($event, $value) {
		foreach (array("pre:$event", $event, "post:$event") as $stage) {
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
