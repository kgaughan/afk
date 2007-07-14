<?php
/**
 * An implementation of DB_Base for MySQL.
 */
class DB_MySQL extends DB_Base {

	private $dbh = false;
	private $rs = false;

	/**
	 *
	 */
	public function __construct($host, $user, $pass, $db) {
		$this->dbh = mysql_connect($host, $user, $pass);
		if ($this->dbh) {
			if (version_compare(mysql_get_server_info($this->dbh), '4.1.0', '>=')) {
				$charset = defined('DB_CHARSET') ? constant('DB_CHARSET') : 'UTF8';
				$this->execute("SET NAMES $charset");
			}
			mysql_select_db($db, $this->dbh);
		} else {
			throw new DB_Exception('Could not connect to database.');
		}
	}

	public function close() {
		if ($this->dbh) {
			mysql_close($this->dbh);
			$this->dbh = false;
			$this->rs = false;
		}
	}

	public function is_connected() {
		return $this->dbh !== false;
	}

	public function execute() {
		$args = func_get_args();
		$q = array_shift($args);
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

	public function query() {
		$args = func_get_args();
		$q = array_shift($args);
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
		return $this->execute('SET AUTOCOMMIT=0') && $this->execute('BEGIN');
	}

	public function commit() {
		return $this->execute('COMMIT') && $this->execute('SET AUTOCOMMIT=1');
	}

	public function rollback() {
		return $this->execute('ROLLBACK') && $this->execute('SET AUTOCOMMIT=1');
	}
}
?>
