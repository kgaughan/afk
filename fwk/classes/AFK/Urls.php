<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Utilities for manipulating URLs.
 *
 * @author Keith Gaughan
 */
class AFK_Urls {

	public static function canonicalise($url, $base=null) {
		$pu = parse_url($url);
		if (array_key_exists('path', $pu)) {
			$pu['path'] = self::scrub_path($pu['path']);
		}

		if (is_null($base) || array_key_exists('scheme', $pu)) {
			// URL must be absolute.
			if (!array_key_exists('scheme', $pu)) {
				throw new AFK_Exception(sprintf("The URL '%s' is not a full URL!", $url));
			}
			return self::reconstruct_path($pu);
		}

		$pb = parse_url($base);
		if (!array_key_exists('path', $pu)) {
			$pb['path'] = self::scrub_path($pb['path']);
		} elseif (substr($pu['path'], 1) == '/') {
			$pb['path'] = $pu['path'];
		} else {
			if (substr($pb['path'], -1) != '/') {
				$pb['path'] = dirname($pb['path']) . '/';
			}
			$pb['path'] = self::scrub_path($pb['path'] . $pu['path']);
		}

		if (array_key_exists('query', $pu)) {
			$pb['query'] = $pu['query'];
		} else {
			unset($pb['query']);
		}

		return self::reconstruct_path($pb);
	}

	public static function reconstruct_path(array $p) {
		return $p['scheme'] . '://' . $p['host'] .
			(array_key_exists('port', $p) ? ':' . $p['port'] : '') .
			$p['path'] .
			(array_key_exists('query', $p) ? '?' . $p['query'] : '');
	}

	public static function scrub_path($path) {
		// Attempt to canonicalise the path, removing any instances of '..'
		// and '.'. Why? Mainly because it's likely that the client dealing
		// with the request will likely not be smart enough to deal with them
		// itself. This code sucks, and no, realpath() won't work here.
		$path = preg_replace('~/(\.(/|$))+~', '/', $path);
		$c = 0;
		do {
			$path = preg_replace('~/[^./;?]([^./?;][^/?;]*)?/\.\.(/|$)~', '/', $path, -1, $c);
		} while ($c > 0);
		return $path;
	}
}
