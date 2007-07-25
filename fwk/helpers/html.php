<?php
function afk_get_assets_location($where) {
	if (is_null($where)) {
		return AFK_Registry::context()->application_root() . 'assets/';
	}
	return $where;
}

function stylesheets($styles=array(), $where=null) {
	$root_enc = e(afk_get_assets_location($where));
	if (count($styles) == 0 || !is_array($styles)) {
		$styles = array();
		foreach (glob(APP_ROOT . '/assets/*.css') as $s) {
			$styles[] = basename($s, '.css');
		}
	}

	foreach ($styles as $medium=>$stylesheet) {
		if (is_numeric($medium)) {
			$medium = $stylesheet;
		}
		echo '<link rel="stylesheet" type="text/css" media="', $medium;
		echo '" href="', $root_enc, $stylesheet, '.css">';
	}
	echo "\n";
}

function javascript($scripts, $where=null) {
	$root_enc = e(afk_get_assets_location($where));
	foreach ($scripts as $s) {
		echo '<script type="text/javascript" src="', $root_enc, $s, '.js"></script>';
	}
	echo "\n";
}

function echo_title() {
	$args = func_get_args();
	echo implode(' - ', array_filter(array_map('e', $args)));
}
?>
