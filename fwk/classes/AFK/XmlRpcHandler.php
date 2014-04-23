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
 *
 * This handler acts as a dispatcher for various registered classes and
 * methods, performing serialisation and error handling.
 *
 * There are two ways in which classes can be registered with the handler.
 * Either by (a) subclassing this class and implementing the
 * add_registrations() method, or (b) by listening for the
 * 'afk:xmlrpc_register' event.
 *
 * In the latter case, the event is triggered when then handle() method
 * is called, and the argument supplied is a reference to the current
 * handler instance. This should give any listening plugins, &c. an opportunity
 * to register themselves to handle various calls.
 */
class AFK_XmlRpcHandler implements AFK_Handler {

	// Map from method names to pairs of callbacks, the first of which is the
	// method handler callback, and the second being the introspection
	// callback.
	private $method_table = array();
	private $introspection_table = array();

	// Internal request processing mechanics {{{

	public function handle(AFK_Context $ctx) {
		$this->add_registrations();
		trigger_event('afk:xmlrpc_register', $this);
		switch ($ctx->method()) {
		case 'post':
			$content = $ctx->_raw;
			if (is_null($content)) {
				$content = AFK_RawRequestFilter::read('php://input');
			}
			try {
				$p = new AFK_XmlRpc_Parser();
				$p->parse($content);
				list($method, $args) = $p->get_result();
				$result = $this->process_request($method, $args);
			} catch (AFK_XmlParserException $xpex) {
				$result = new AFK_XmlRpc_Fault(
					AFK_XmlRpc_Fault::NOT_WELL_FORMED,
					"Cannot parse request: " . $xpex);
			}
			$ctx->header("Content-Type: application/xml; charset=utf-8");
			echo AFK_XmlRpc_Parser::serialise_response($result);
			/* PASSTHROUGH */
		case 'options':
			$ctx->allow_rendering(false);
			$ctx->header('Allow: POST, OPTIONS');
			break;
		case 'get':
		case 'head':
		default:
			$ctx->no_such_method(array('POST', 'OPTIONS'));
		}
	}

	private function process_request($method, array $params) {
		if (!isset($this->method_table[$method])) {
			return new AFK_XmlRpc_Fault(
				AFK_XmlRpc_Fault::UNKNOWN_METHOD,
				"Unknown method: '$method'");
		}
		try {
			return call_user_func_array($this->method_table[$method], $params);
		} catch (Exception $ex) {
			trigger_event('afk:xmlrpc_fault', compact('method', 'params', 'ex'));
			return new AFK_XmlRpc_Fault(
				AFK_XmlRpc_Fault::INTERNAL_ERROR,
				"Uncaught internal exception.");
		}
	}

	public function register($name, $method_callback, $introspection_callback) {
		if (!isset($this->method_table[$name]) && is_callable($method_callback)) {
			$this->method_table[$name] = $method_callback;
			if (is_callable($introspection_callback)) {
				$this->introspection_table[$name] = $introspection_callback;
			}
		}
	}

	protected function register_each(array $methods) {
		$introspection_cb = array($this, 'on_introspection');
		foreach ($methods as $method) {
			$info = $this->on_introspection($method);
			$this->register($method, array($this, $info['callback']), $introspection_cb);
		}
	}

	// }}}

	// Registration and metadata {{{

	protected function add_registrations() {
		$this->register_each(array(
			'system.getCapabilities',
			'system.listMethods',
			'system.methodSignature',
			'system.methodHelp',
			'system.multicall'));
	}

	protected function on_introspection($method) {
		switch ($method) {
		case 'system.getCapabilities':
			return array(
				'signatures' => array('struct'),
				'callback' => 'call_get_capabilities',
				'help' => 'Returns a struct giving the capabilities exposed by this server.');
		case 'system.listMethods':
			return array(
				'signatures' => array('array'),
				'callback' => 'call_list_methods',
				'help' => 'Returns an array of the methods exposed by this server.');
		case 'system.methodSignature':
			return array(
				'signatures' => array('array, string'),
				'callback' => 'call_method_signature',
				'help' => 'Returns the signatures of the given method.');
		case 'system.methodHelp':
			return array(
				'signatures' => array('string, string'),
				'callback' => 'call_method_help',
				'help' => 'Returns a description of what the given method does. This description *may* be HTML.');
		case 'system.multicall':
			return array(
				'signatures' => array('array, array'),
				'callback' => 'call_multicall',
				'help' => 'Executes the given list of boxcarred method calls on this server.');
		}
		return null;
	}

	// }}}

	// Default methods {{{

	protected function call_get_capabilities() {
		return array(
			'faults_interop' => array(
				'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
				'specVersion' => 20010516),
			'introspect' => array(
				'specUrl' => 'http://xmlrpc-c.sourceforge.net/xmlrpc-c/introspection.html',
				'specVersion' => 1));
	}

	protected function call_list_methods() {
		return array_keys($this->method_table);
	}

	protected function get_introspection($method) {
		if (isset($this->introspection_table[$method])) {
			return call_user_func($this->introspection_table[$method], $method);
		}
		return null;
	}

	protected function call_method_signature($method) {
		$introspection = $this->get_introspection($method);
		if (!is_array($introspection) || !isset($introspection['signatures'])) {
			return array();
		}
		return $introspection['signatures'];
	}

	protected function call_method_help($method) {
		$introspection = $this->get_introspection($method);
		if (!is_array($introspection) || !isset($introspection['help'])) {
			return '';
		}
		return $introspection['help'];
	}

	protected function call_multicall(array $calls) {
		$results = array();
		foreach ($calls as $call) {
			if (!is_array($call) || !isset($call['methodName'])) {
				$results[] = new AFK_XmlRpc_Fault(
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

	// }}}
}
