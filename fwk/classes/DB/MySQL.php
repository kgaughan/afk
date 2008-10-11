<?php
/**
 * An implementation of DB_Base for MySQL.
 */
class DB_MySQL extends DB_Base {

	/**
	 * Used to prevent the creation of too-many unneeded connections and to
	 * prevent the client libraries from recycling connections we don't want it
	 * to.
	 */
	static $cache = array();

	private $key = false;
	private $dbh = false;
	private $rs = false;

	private $depth = 0;

	/**
	 *
	 */
	public function __construct($host, $user, $pass, $db) {
		$this->key = "$host:$user:$db";
		if (array_key_exists($this->key, self::$cache)) {
			list($this->dbh, $count) = self::$cache[$this->key];
			self::$cache[$this->key] = array($this->dbh, $count + 1);
		} else {
			$this->dbh = mysql_connect($host, $user, $pass, true);
			if ($this->dbh) {
				if (version_compare(mysql_get_server_info($this->dbh), '4.1.0', '>=')) {
					$charset = defined('DB_CHARSET') ? constant('DB_CHARSET') : 'utf8';
					$this->execute("SET NAMES $charset");
					$this->execute("SET SESSION character_set_database='$charset'");
					$this->execute("SET SESSION character_set_server='$charset'");
					$this->execute("SET SESSION character_set_connection='$charset'");
					$this->execute("SET SESSION character_set_results='$charset'");
					$this->execute("SET SESSION character_set_client='$charset'");
					$this->execute("SET SESSION collation_connection='{$charset}_general_ci'");
					$this->execute("SET SESSION collation_database='{$charset}_general_ci'");
				}
				if (!mysql_select_db($db, $this->dbh)) {
					throw new DB_Exception('Could not select database: ' . $this->get_last_error());
				}
			} else {
				throw new DB_Exception('Could not connect to database.');
			}
			self::$cache[$this->key] = array($this->dbh, 1);
		}
	}

	public function close() {
		if ($this->dbh) {
			while ($this->depth > 0) {
				$this->rollback();
			}
			list(, $count) = self::$cache[$this->key];
			if ($count > 1) {
				self::$cache[$this->key] = array($this->dbh, $count - 1);
			} else {
				mysql_close($this->dbh);
				unset(self::$cache[$this->key]);
			}
			$this->dbh = false;
			$this->rs = false;
		}
	}

	public function is_connected() {
		return $this->dbh !== false;
	}

	public function vexecute($q, array $args) {
		if (count($args) > 0) {
			$q = $this->compose($q, $args);
		}
		$this->log_query($q);
		if (mysql_query($q, $this->dbh) === false && mysql_errno($this->dbh) != 0) {
			$this->report_error($q);
			return false;
		}

		if (strtoupper(substr(ltrim($q), 0, 6)) === 'INSERT') {
			return mysql_insert_id($this->dbh);
		}
		return mysql_affected_rows($this->dbh);
	}

	public function vquery($q, array $args) {
		if (count($args) > 0) {
			$q = $this->compose($q, $args);
		}
		$this->log_query($q);
		$this->rs = mysql_unbuffered_query($q, $this->dbh);
		if ($this->rs === false && mysql_errno($this->dbh) != 0) {
			$this->report_error($q);
			return false;
		}
		return true;
	}

	public function fetch($type=DB_ASSOC, $free_now=false) {
		if (!$this->rs) {
			return false;
		}
		$r = mysql_fetch_array($this->rs, $type == DB_ASSOC ? MYSQL_ASSOC : MYSQL_NUM);
		if ($r === false || $free_now) {
			mysql_free_result($this->rs);
			$this->rs = false;
		}
		return $r;
	}

	protected function e($s) {
		if (function_exists('mysql_real_escape_string')) {
			return mysql_real_escape_string($s, $this->dbh);
		}
		return mysql_escape_string($s);
	}

	public function get_last_error() {
		return mysql_error($this->dbh);
	}

	public function begin() {
		$this->depth++;
		if ($this->depth == 1) {
			$this->execute('SET AUTOCOMMIT=0');
			$this->execute('BEGIN');
		}
	}

	public function commit() {
		$this->depth--;
		if ($this->depth == 0) {
			$this->execute('COMMIT');
			$this->execute('SET AUTOCOMMIT=1');
		}
	}

	public function rollback() {
		$this->depth--;
		if ($this->depth == 0) {
			$this->execute('ROLLBACK');
			$this->execute('SET AUTOCOMMIT=1');
		}
	}
}
