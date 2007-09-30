<?php
require(dirname(__FILE__) . '/bootstrap.php');
AFK::process_request(AFK_Registry::routes(), $filters);
