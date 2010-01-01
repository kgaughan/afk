<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_XmlRpcFaultException extends AFK_Exception {

	// See http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php
	// Parser errors
	const FAULT_NOT_WELL_FORMED      = -32700; // Not well formed
	const FAULT_UNSUPPORTED_ENCODING = -32701; // Unsupported encoding
	const FAULT_INVALID_CHARACTER    = -32702; // Invalid character for encoding
	// Server errors
	const FAULT_INVALID_XMLRPC       = -32600; // Invalid XML-RPC; not conforming to spec
	const FAULT_UNKNOWN_METHOD       = -32601; // Requested method not found
	const FAULT_BAD_PARAMETERS       = -32602; // Invalid method parameters
	const FAULT_INTERNAL_ERROR       = -32603; // Internal XML-RPC error
	// Others
	const FAULT_APPLICATION_ERROR    = -32500; // Application error
	const FAULT_SYSTEM_ERROR         = -32400; // System error
	const FAULT_TRANSPORT_ERROR      = -32300; // Transport error
}
