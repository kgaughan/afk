<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A collection of helper code for common tasks with PDO.
 *
 * @author Keith Gaughan
 */
class AFK_PDOHelper {

	public static function behave(PDO $dbh) {
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public static function query_value(PDO $dbh, $q, array $args) {
		if ($result = self::query_row($dbh, $q, $args, PDO::FETCH_NUM)) {
			list($v) = $result;
			return $v;
		}
		return false;
	}

	public static function query_row(PDO $dbh, $q, array $args, $fetch=PDO::FETCH_ASSOC) {
		$stmt = $dbh->prepare($q, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$result = false;
		if ($stmt->execute($args) && ($r = $stmt->fetch($fetch))) {
			$result = $r;
		}
		$stmt->closeCursor();
		return $result;
	}

	public static function execute(PDO $dbh, $q, array $args) {
		$stmt = $dbh->prepare($q);
		if (!$stmt->execute($args)) {
			$n = 0;
		} elseif (strtoupper(substr(ltrim($q), 0, 6)) === 'INSERT') {
			 $n = $stmt->rowCount();
		} else {
			 $n = $dbh->lastInsertId();
		}
		$stmt->closeCursor();
		return $n;
	}

	public static function query(PDO $dbh, $q, array $args, $class='AFK_PDO_RowIterator') {
		$stmt = $dbh->prepare($q, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		return $stmt->execute($args) ? new $class($stmt) : false;
	}

	public static function query_list(PDO $dbh, $q, array $args) {
		return self::query($dbh, $q, $args, 'AFK_PDO_ListIterator');
	}

	public static function query_map(PDO $dbh, $q, array $args) {
		return self::query($dbh, $q, $args, 'AFK_PDO_MapIterator');
	}

	public static function query_row_map(PDO $dbh, $q, array $args) {
		return self::query($dbh, $q, $args, 'AFK_PDO_RowMapIterator');
	}

	public static function flatten(PDO $dbh, array $a) {
		return implode(', ', array_map(array($dbh, 'quote'), $a));
	}

	public static function to_multimap($key_column, $iterable) {
		$result = array();
		foreach ($iterable as $v) {
			$key = $v[$key_column];
			if (isset($result[$key])) {
				$result[$key] = array($v);
			} else {
				$result[$key][] = $v;
			}
		}
		return $result;
	}
}
