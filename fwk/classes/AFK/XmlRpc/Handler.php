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

	// Map from method names to pairs of callbacks, the first of which is the 
	// method handler callback, and the second being the introspection 
	// callback.
	private $method_table = array();

	// Design:
	//
	// This handler acts as a dispatcher for various registered classes and
	// methods, performing serialisation and error handling.
	//
	// There are two ways in which classes can be registered with the handler. 
	// Either by (a) subclassing this class and implementing the 
	// add_registrations() method, or (b) by listening for the 
	// 'xmlrpc:register' event.
	//
	// In the latter case, the event is triggered when then handle() method
	// is called, and the argument supplied is a reference to the current
	// handler instance. This should give any listening plugins, &c. an 
	// opportunity to register themselves to handle various calls.
	//

	private function add_default_registrations() {
		$introspection_cb = array($this, 'on_introspection');
		$this->register('system.getCapabilities', array($this, 'on_get_capabilities'), $introspection_cb);
		$this->register('system.listMethods', array($this, 'on_list_methods'), $introspection_cb);
		$this->register('system.methodSignature', array($this, 'on_method_signature'), $introspection_cb);
		$this->register('system.methodHelp', array($this, 'on_method_help'), $introspection_cb);
		$this->register('system.multicall', array($this, 'on_multicall'), $introspection_cb);
	}

	protected function add_registrations() {
	}

	public function handle(AFK_Context $ctx) {
		$ctx->header('Allow: POST');
		$this->add_default_registrations();
		$this->add_registrations();
		trigger_event('xmlrpc:register', $this);
		switch ($ctx->method()) {
		case 'post':
			$this->allow_rendering(false);
			$p = new AFK_XmlRpc_Parser();
			$p->parse($ctx->_raw);
			list($method, $args) = $p->get_result();
			echo AFK_XmlRpc_Parser::serialise($this->process_request($method, $args));
			break;
		default:
			$ctx->no_such_method(array('post'));
		}
	}

	public function register($name, $method_callback, $introspection_callback) {
		if (!isset($this->method_table[$name])) {
			// TODO: Check both are callable.
			$this->method_table[$name] = array($method_callback, $introspection_callback);
		}
	}

	private function on_get_capabilities() {
		return array(
			'faults_interop' => array(
				'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
				'specVersion' => 20010516),
			'introspect' => array(
				'specUrl' => 'http://xmlrpc-c.sourceforge.net/xmlrpc-c/introspection.html',
				'specVersion' => 1)); 
	}

	private function on_list_methods() {
		return array_keys($this->method_table);
	}

	private function get_introspection($method) {
		if (isset($this->method_table[$method])) {
			list(, $introspection_db) = $this->method_table[$method];
			return call_user_func($introspection_cb, $method);
		}
		return null;
	}

	private function on_method_signature($method) {
		$introspection = $this->get_introspection($method);
		if (!is_array($introspection) || !isset($introspection['signatures'])) {
			return array();
		}
		return $introspection['signatures'];
	}

	private function on_method_help($method) {
		$introspection = $this->get_introspection($method);
		if (!is_array($introspection) || !isset($introspection['help'])) {
			return '';
		}
		return $introspection['help'];
	}

	private function on_multicall(array $calls) {
		$results = array();
		foreach ($calls as $call) {
			if (!is_array($call)) {
				return new AFK_XmlRpc_Fault(
					AFK_XmlRpc_Fault::NOT_WELL_FORMED,
					'system.multicall expected struct');
			} elseif ($call['methodName'] == 'system.multicall') {
				$results[] = new AFK_XmlRpc_Fault(
					AFK_XmlRpc_Fault::UNKNOWN_METHOD,
					'Recursive system.multicall forbidden');
			} else {
				$result = $this->process_request($call['methodName'], $call['params']);
				if (is_object($result) && get_class($result) == 'AFK_XmlRpc_Fault') {
					$results[] = $result;
				} else {
					$results[] = array($result);
				}
			}
		}
		return $results;
	}

	private function on_introspection($method) {
		switch ($method) {
		case 'system.getCapabilities':
			return array(
				'signatures' => array('struct'),
				'help' => 'Returns a struct giving the capabilities exposed by this server.');
		case 'system.listMethods':
			return array(
				'signatures' => array('array'),
				'help' => 'Returns an array of the methods exposed by this server.');
		case 'system.methodSignature':
			return array(
				'signatures' => array('array, string'),
				'help' => 'Returns the signatures of the given method.');
		case 'system.methodHelp':
			return array(
				'signatures' => array('string, string'),
				'help' => 'Returns a description of what the given method does. This description *may* be HTML.');
		case 'system.multicall':
			return array(
				'signatures' => array('array, array'),
				'help' => 'Executes the given list of boxcarred method calls on this server.');
		}
		return null;
	}

	private function process_request($method, array $params) {
		return call_user_func_array($this->method_table[$method], $params);
	}
}
