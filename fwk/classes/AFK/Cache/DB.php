<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * To create the appropriate, you'll need to run something like the following:
 *
 * CREATE TABLE cache (
 *     id   CHAR(32)         NOT NULL,
 *     ts   INTEGER UNSIGNED NOT NULL,
 *     data TEXT             NOT NULL,
 *
 *     PRIMARY KEY (id),
 *     INDEX ix_timestamp (ts)
 * );
 *
 * I'm pretty sure the table schema and the class itself should work on just
 * about every RDBMS out there.
 *
 * You will need an implementation of DB_Base to get this to work.
 *
 * @author Keith Gaughan
 */
class AFK_Cache_DB implements AFK_Cache {

	private $dbh;
	private $table;

	public function __construct(DB_Base $dbh, $table='cache') {
		$this->dbh = $dbh;
		$this->table = $table;
	}

	public function invalidate($id) {
		$this->dbh->execute("
			DELETE FROM {$this->table} WHERE id = %s
			", md5($id));
	}

	public function invalidate_all($max_age=0) {
		$this->dbh->execute("
			DELETE FROM {$this->table} WHERE ts < %s
			", time() - $max_age);
	}

	public function load($id, $max_age=300) {
		$data = $this->dbh->query_value("
			SELECT	data
			FROM	{$this->table}
			WHERE	id = %s AND ts > %s
			", md5($id), time() - $max_age);

		return is_null($data) ? null : unserialize($data);
	}

	public function save($id, $item) {
		$hash = md5($id);
		$data = serialize($item);
		$now = time();
		if ($this->dbh->execute("UPDATE {$this->table} SET data = %s, ts = %s WHERE id = %s", $data, $now, $hash) == 0) {
			$this->dbh->execute("INSERT INTO {$this->table} (data, ts, id) VALUES (%s, %s, %s, %s)", $data, $now, $hash);
		}
	}
}
