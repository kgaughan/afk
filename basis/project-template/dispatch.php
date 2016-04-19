<?php
require(dirname(__FILE__) . '/bootstrap.php');
// If we're running the CLI server, do request passthrough.
if (AFK::cli_server_passthrough()) {
	return false;
}
AFK::process_request(AFK_Registry::routes(), $filters);
