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
 * While there isn't much code in AFK_EventBroker, it's behaviour is relatively
 * complex and counterintuitive in places.
 *
 * Events have three stages: the pre-event stage, the event stage, and the
 * post-event stage. Your code can register for any and all of these. Given an
 * event 'foo', the respective stage names are 'pre:foo', 'foo', and
 * 'post:foo', and the callbacks for each stage are executed in the order they
 * were registered.
 *
 * A callback takes two arguments, the _event stage_ and a _value_, and
 * returns a two-element array, the first of which specifies whether any
 * further callbacks for this stage should be called, and the second being the
 * possibly mutated input value, which is then passed to the next callback.
 *
 * Note that if a callback at _any stage_ in the event's processing aborts
 * further processing, processing is aborted for the _whole event_, not just
 * the stage.
 *
 * @author Keith Gaughan
 */
class AFK_EventBroker {

	private $callbacks = array();

	/**
	 * Registers a callback for an event stage.
	 *
	 * @param  $stage      Event stage name.
	 * @param  $callback   Function or method to trigger on that stage.
	 * @param  $singleton  Should it be the only one listening on that
	 *                     stage?
	 */
	public function register($stage, $callback, $singleton=false) {
		if (!array_key_exists($stage, $this->callbacks) ||
				($singleton && count($this->callbacks[$stage]) > 0)) {
			$this->callbacks[$stage] = array($callback);
		} else {
			$this->callbacks[$stage][] = $callback;
		}
	}

	/**
	 * Trigger's an event.
	 *
	 * @param  $event  Event being triggered.
	 * @param  $value  Value to be processed by the event.
	 *
	 * @return A two element array; the first element of which, when true
	 *         specifies that all registered callbacks ran to completion, and
	 *         when false specifies that one of the callbacks blocked further
	 *         processing after itself, e.g., because it completely processed
	 *         the event; the second of which is the value returned by the
	 *         last callback executed.
	 */
	public function trigger($event, $value) {
		foreach (array("pre:$event", $event, "post:$event") as $stage) {
			list($continue, $value) = $this->trigger_stage($stage, $value);
			if (!$continue) {
				break;
			}
		}
		return array($continue, $value);
	}

	/** Internal processing of an event stage. */
	private function trigger_stage($stage, $value) {
		if (array_key_exists($stage, $this->callbacks)) {
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
