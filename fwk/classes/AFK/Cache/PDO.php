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
 * You will need PDO to make the magic happen.
 *
 * @author Keith Gaughan
 */
class AFK_Cache_PDO implements AFK_Cache {

	private $dbh;
	private $table;

	public function __construct(PDO $dbh, $table='cache') {
		$this->dbh = $dbh;
		$this->table = $table;
	}

	public function invalidate($id) {
		$this->e("DELETE FROM {$this->table} WHERE id = :id", array('id' => md5($id)));
	}

	public function invalidate_all($max_age=0) {
		$this->e("DELETE FROM {$this->table} WHERE ts < :ts", array('ts' => time() - $max_age));
	}

	public function load($id, $max_age=300) {
		$data = AFK_PDOHelper::query_value($this->dbh, "
			SELECT	data
			FROM	{$this->table}
			WHERE	id = :id AND ts > :ts
			", array('id' => md5($id), 'ts' => time() - $max_age));
		return $data === false ? null : unserialize($data);
	}

	public function save($id, $item) {
		$args = array('data' => serialize($item), 'now' => time(), 'id' => md5($id));
		if ($this->e("UPDATE {$this->table} SET data = :data, ts = :now WHERE id = :id", $args) == 0) {
			$this->e("INSERT INTO {$this->table} (data, ts, id) VALUES (:data, :now, :id)", $args);
		}
	}

	private function e($q, array $args) {
		return AFK_PDOHelper::execute($this->dbh, $q, $args);
	}
}
