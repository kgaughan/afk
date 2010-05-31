<?php
require(dirname(__FILE__) . '/bootstrap.php');
AFK::run_callables(array_slice($argv, 1), '/var/lock');
