<?php
define('APP_ROOT', dirname(__FILE__));
define('APP_TEMPLATE_ROOT', APP_ROOT . '/templates');

require(APP_ROOT . '/lib/afk/afk.php');

AFK::add_class_path(APP_ROOT . '/lib/classes');
AFK::add_class_path(APP_ROOT . '/classes');
AFK::add_class_path(APP_ROOT . '/handlers');

include(APP_ROOT . '/config.php');
include(APP_ROOT . '/lib/lib.php');

AFK::process_request(routes(), init());
?>
