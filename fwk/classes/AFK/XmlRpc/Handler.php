<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Basic functionality common to all handlers.
 */
class AFK_XmlRpc_Handler implements AFK_Handler {

	// Map from method name prefixes to pairs of callbacks, the first of
	// which is the method handler callback, and the second being the
	// introspection callback.
	private $prefix_table = array();
	private $method_table = array();

	// Design:
	//
	// This handler acts as a dispatcher for various registered classes and
	// methods, performing serialisation and error handling.
	//
	// There are two ways in which classes can be registered with the handler.
	// Either by (a) subclassing this class and implementing the
	// get_prefixes() method, or (b) by listening for the
	// 'afk:xmlrpc.register' event.
	//
	// In the latter case, the event is triggered when then handle() method
	// is called, and the argument supplied is a reference to the current
	// handler instance. This should give any listening plugins, &c. an 
	// opportunity to register themselves to handle various calls.
	//
	// Two tables are maintained: a prefix table and a method table. Entries
	// are added to the prefix table when the method specified for a callback
	// ends in '*', thus that callback handles all calls for methods with
	// the given prefix. In precedence, the method table is searched before
	// the prefix table.

	public function handle(AFK_Context $ctx) {
		$ctx->header('Allow: GET, POST');
		trigger_event('afk:xmlrpc.register', $this);
		switch ($ctx->method()) {
		case 'post':
			$p = new AFK_XmlRpc_Parser();
			$p->parse($ctx->_raw);
			list($method, $args) = $p->get_result();
			echo AFK_XmlRpc_Parser::serialise($this->process_request($method, $args));
			break;
		case 'get':
			$this->send_introspection_information();
			break;
		default:
			$ctx->no_such_method(array('get', 'post'));
		}
	}

	public function register($name, $method_callback, $introspection_callback) {
		// TODO: Check both are callable.
		$pair = array($method_callback, $introspection_callback);
		if (substr($name, -1) == '*') {
			$this->prefix_table[substr($name, 0, -1)] = $pair;
		} else {
			$this->method_table[$name] = $pair;
		}
	}
}
