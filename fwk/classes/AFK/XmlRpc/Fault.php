<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_XmlRpc_Fault extends Exception {

	// See http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php
	// Parser errors
	const NOT_WELL_FORMED      = -32700; // Not well formed
	const UNSUPPORTED_ENCODING = -32701; // Unsupported encoding
	const INVALID_CHARACTER    = -32702; // Invalid character for encoding
	// Server errors
	const INVALID_XMLRPC       = -32600; // Invalid XML-RPC; not conforming to spec
	const UNKNOWN_METHOD       = -32601; // Requested method not found
	const BAD_PARAMETERS       = -32602; // Invalid method parameters
	const INTERNAL_ERROR       = -32603; // Internal XML-RPC error
	// Others
	const APPLICATION_ERROR    = -32500; // Application error
	const SYSTEM_ERROR         = -32400; // System error
	const TRANSPORT_ERROR      = -32300; // Transport error

	public $faultCode;
	public $faultString;

	public function __construct($faultCode, $faultString) {
		parent::__construct($faultString, $faultCode);
		$this->faultCode = $faultCode;
		$this->faultString = $faultString;
	}
}
