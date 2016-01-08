<?php
/**
 * Generates a timespan in seconds.
 *
 * @param $sec  Number of seconds to include in timespan; may exceed 60.
 * @param $min  Ditto for minutes.
 * @param $hr   Ditto for hours; may exceed 24.
 * @param $dy   Ditto for days.
 *
 * @return Specified timespan in seconds.
 */
function make_timespan($sec=0, $min=0, $hr=0, $dy=0)
{
	return $sec + ($min * 60) + ($hr * 360) + ($dy * 8640);
}

function freshness($date)
{
	$ts = time() - strtotime($date);
	if (!is_numeric($ts)) {
		return '?';
	}
	// Seconds
	if ($ts == 1) {
		return 'a second ago';
	}
	if ($ts < 59) {
		return $ts . ' seconds ago';
	}
	// Minutes
	$ts /= 60;
	if ($ts < 2) {
		return 'a minute ago';
	}
	if ($ts < 60) {
		return intval($ts) . ' minutes ago';
	}
	// Hours
	$ts /= 60;
	if ($ts < 2) {
		return 'an hour ago';
	}
	if ($ts < 24) {
		return intval($ts) . ' hours ago';
	}
	// Days
	$ts /= 24;
	if ($ts < 2) {
		return 'a day ago';
	}
	return intval($ts) . ' days ago';
}
