<?php
function scrub_creditcard_number($cc) {
	return preg_replace('/[^0-9]/', '', $cc);
}

function luhn_check($cc) {
	$ttl = 0;
	$len = strlen($cc);
	$alt = false;
	for ($i = $len - 1; $i >= 0; $i--) {
		$digit = (int) $cc[$i];
		if ($alt) {
			$digit *= 2;
			if ($digit > 9) {
				$digit -= 9;
			}
		}
		$ttl += $digit;
		$alt = !$alt;
	}
	return $ttl % 10 == 0;
}

