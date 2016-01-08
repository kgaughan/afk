<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2009. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A port to AFK of the new, cleaner session handler I wrote for Tempus Wiki,
 * tweaked for PDO.
 *
 * To create the appropriate, you'll need to run something like the following:
 *
 * CREATE TABLE sessions (
 *     id   CHAR(32) NOT NULL,
 *     name CHAR(16) NOT NULL,
 *     ts   INTEGER  NOT NULL,
 *     data TEXT     NOT NULL,
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
class AFK_Session_PDO extends AFK_Session
{
	private $dbh;
	private $name;
	private $table;

	public function __construct(PDO $dbh, $table='sessions')
	{
		parent::__construct();
		$this->dbh = $dbh;
		$this->table = $table;
	}

	public function open($save_path, $name)
	{
		$this->name = $name;
		return true;
	}

	public function read($id)
	{
		$result = AFK_PDOHelper::query_value(
			$this->dbh, "
			SELECT	data
			FROM	{$this->table}
			WHERE	name = :name AND id = :id
			", array('name' => $this->name, 'id' => $id)
		);
		return $result === false ? '' : $result;
	}

	public function write($id, $data)
	{
		if ($data == '') {
			return $this->destroy($id);
		}
		$args = array('name' => $this->name, 'now' => time(), 'data' => $data, 'id' => $id);
		if ($this->e("UPDATE {$this->table} SET data = :data, ts = :now WHERE name = :name AND id = :id", $args) == 0) {
			try {
				$this->e("INSERT INTO {$this->table} (data, ts, name, id) VALUES (:data, :now, :name, :id)", $args);
			} catch (PDOException $ex) {
				return false;
			}
		}
		return true;
	}

	public function destroy($id)
	{
		$this->e("DELETE FROM {$this->table} WHERE name = :name AND id = :id", array('name' => $this->name, 'id' => $id));
		return true;
	}

	public function gc($max_age)
	{
		$this->e("DELETE FROM {$this->table} WHERE ts < :ts", array('ts' => time() - $max_age));
		return true;
	}

	private function e($q, array $args)
	{
		return AFK_PDOHelper::execute($this->dbh, $q, $args);
	}
}
