<?php
function stylesheets() {
	$root_enc = e(AFK_Context::get()->application_root());
	foreach (glob(APP_ROOT . '/assets/*.css') as $s) {
		$type = basename($s, '.css');
		echo '<link rel="stylesheet" type="text/css" media="', $type;
		echo '" href="', $root_enc, 'assets/', $type, '.css"/>';
	}
}
?>
