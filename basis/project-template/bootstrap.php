<?php
define('APP_ROOT', dirname(__FILE__));
define('APP_TEMPLATE_ROOT', APP_ROOT . '/templates');
require(APP_ROOT . '/lib/afk/afk.php');
$filters = AFK::bootstrap();
