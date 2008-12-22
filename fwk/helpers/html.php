<?php
/**
 * @return The full URL path of the application's assets drectory.
 */
function afk_get_assets_location($where=null) {
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

	foreach ($styles as $medium => $stylesheet) {
		if (is_numeric($medium)) {
			$medium = $stylesheet;
		}
		if (!is_array($stylesheet)) {
			$stylesheet = array($stylesheet);
		}
		foreach ($stylesheet as $s) {
			echo '<link rel="stylesheet" type="text/css" media="', $medium;
			echo '" href="', $root_enc, $s, '.css">';
		}
	}
	echo "\n";
}

function javascript(array $scripts, $where=null) {
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
