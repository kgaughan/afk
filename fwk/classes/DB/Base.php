<?php
define('DB_ASSOC', 0);
define('DB_NUM', 1);

/**
 * Wrapper around the various DB drivers to abstract away various repetitive
 * work.
 */
abstract class DB_Base
{
	private $logger = false;
	private $cache;

	public function __construct()
	{
		$this->cache = new AFK_Cache_Null();
	}

	public function set_logger(DB_Logger $logger)
	{
		$this->logger = $logger;
	}

	public function get_logger()
	{
		return $this->logger;
	}

	public function set_cache(AFK_Cache $cache)
	{
		$this->cache = $cache;
	}

	public function invalidate($q)
	{
		$this->cache->invalidate($q);
	}

	/**
	 * Close the database connection.
	 */
	abstract public function close();

	abstract public function is_connected();

	/**
	 * Executes a update query of some kind against the database currently
	 * connected to. This class implements a kind of poor man's prepared
	 * statements. If you provide just a single argument--the query--it is
	 * sent to the DB as-is. If you provide more than one, the query is taken
	 * to be a template to be passed to the compose method. If the query runs
	 * successfully, it returns the last insert id for INSERT statements, the
	 * number of rows affected if it ran successfully, otherwise false if it
	 * didn't.
	 */
	public function execute()
	{
		$args = func_get_args();
		$q = array_shift($args);
		return $this->vexecute($q, $args);
	}

	/**
	 * Run a query against the database currently connected to. This class
	 * implements a kind of poor man's prepared statements. If you provide
	 * just a single argument--the query--it is sent to the DB as-is. If you
	 * provide more than one, the query is taken to be a template to be passed
	 * to the compose method. It returns true if the query runs successfully,
	 * and false if it didn't.
	 */
	public function query()
	{
		$args = func_get_args();
		$q = array_shift($args);
		return $this->vquery($q, $args);
	}

	/**
	 * Variant of execute() that allows arguments to be specified as an array.
	 *
	 * @note If you're implementing this class, execute() wraps this, so this
	 *       is the method to implement.
	 */
	abstract public function vexecute($q, array $args);

	/**
	 * Variant of query() that allows arguments to be specified as an array.
	 *
	 * @note If you're implementing this class, execute() wraps this, so this
	 *       is the method to implement.
	 */
	abstract public function vquery($q, array $args);

	/**
	 * Fetch the next tuple in the current resultset as an associative array.
	 */
	public function fetch($type=DB_ASSOC, $free_now=false)
	{
		return false;
	}

	/**
	 * Queries the database and returns the first matching tuple. Returns
	 * false if there was no match.
	 */
	public function query_row()
	{
		$args = func_get_args();
		if (call_user_func_array(array($this, 'query'), $args)
			&& ($r = $this->fetch(DB_ASSOC, true))
		) {
			return $r;
		}
		return false;
	}

	public function cached_query_row($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_row($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	public function query_tuple()
	{
		$args = func_get_args();
		if (call_user_func_array(array($this, 'query'), $args)
			&& ($r = $this->fetch(DB_NUM, true))
		) {
			return $r;
		}
		return false;
	}

	public function cached_query_tuple($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_tuple($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Queries the database and return the first value in the first matching
	 * tuple. Returns null if there was no match.
	 */
	public function query_value()
	{
		$args = func_get_args();
		if (call_user_func_array(array($this, 'query'), $args)
			&& ($r = $this->fetch(DB_NUM, true))
		) {
			return $r[0];
		}
		return null;
	}

	public function cached_query_value($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_value($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Like query_value(), but operates over the whole resultset, pulling the
	 * first value of each tuple into an array.
	 */
	public function query_list()
	{
		$args = func_get_args();
		$result = array();
		if (call_user_func_array(array($this, 'query'), $args)) {
			while ($r = $this->fetch(DB_NUM)) {
				$result[] = $r[0];
			}
		}
		return $result;
	}

	public function cached_query_list($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_list($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Returns an associative array derived from a query's two-column
	 * resultset. The first column in each row is used as the key and
	 * the second as the value the key maps to.
	 */
	public function query_map()
	{
		$args = func_get_args();
		$result = array();
		if (call_user_func_array(array($this, 'query'), $args)) {
			while ($r = $this->fetch(DB_NUM)) {
				$result[$r[0]] = $r[1];
			}
		}
		return $result;
	}

	public function cached_query_map($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_map($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Returns an associative array derived from a query resultset. The
	 * first column in each row is used as the key and the rest as the
	 * value the key maps to.
	 */
	public function query_row_map()
	{
		$args = func_get_args();
		$result = array();
		if (call_user_func_array(array($this, 'query'), $args)) {
			while ($r = $this->fetch(DB_ASSOC)) {
				$k = array_shift($r);
				$result[$k] = $r;
			}
		}
		return $result;
	}

	public function cached_query_row_map($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_row_map($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Returns an multimap (a map where the same key maps onto multipl values)
	 * derived from a query resultset. The first column in each row is used as
	 * the key and the second as a value the key maps to.
	 */
	public function query_multimap()
	{
		$args = func_get_args();
		$result = array();
		if (call_user_func_array(array($this, 'query'), $args)) {
			while ($r = $this->fetch(DB_NUM)) {
				if (array_key_exists($r[0], $result)) {
					$result[$r[0]][] = $r[1];
				} else {
					$result[$r[0]] = array($r[1]);
				}
			}
		}
		return $result;
	}

	public function cached_query_multimap($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_multimap($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Convenience method to query the database and convert the resultset into
	 * an array.
	 */
	public function query_all()
	{
		$args = func_get_args();
		call_user_func_array(array($this, 'query'), $args);
		$rows = array();
		while ($r = $this->fetch()) {
			$rows[] = $r;
		}
		return $rows;
	}

	public function cached_query_all($max_age, $q)
	{
		$r = $this->cache->load($q, $max_age);
		if (is_null($r)) {
			$r = $this->query_all($q);
			$this->cache->save($q, $r);
		}
		return $r;
	}

	/**
	 * Convenience method for starting a transaction.
	 */
	public function begin()
	{
		return  $this->execute('BEGIN');
	}

	/**
	 * Convenience method for committing a transaction.
	 */
	public function commit()
	{
		return $this->execute('COMMIT');
	}

	/**
	 * Convenience method for rolling back a transaction.
	 */
	public function rollback()
	{
		return $this->execute('ROLLBACK');
	}

	/**
	 * Convenience method for doing inserts.
	 *
	 * @param $table  Name of table to do the insert on.
	 * @param $data   Associative array with column names for keys and the
	 *                values to insert on those columns as values.
	 *
	 * @return Last insert ID.
	 */
	public function insert($table, array $data, $delayed=false)
	{
		if (count($data) == 0) {
			return false;
		}
		$flags = $delayed ? '/*! DELAYED */ ' : '';
		$keys = implode(', ', array_keys($data));
		$values = implode(', ', array_map(array($this, 'make_safe'), array_values($data)));
		return $this->execute("INSERT $flags INTO $table ($keys) VALUES ($values)");
	}

	/**
	 *
	 */
	public function update($table, array $data, array $qualifiers=array())
	{
		if (count($data) == 0) {
			return false;
		}

		$is_first = true;
		$sql = "UPDATE $table SET ";
		foreach ($data as $f => $v) {
			if (!$is_first) {
				$sql .= ', ';
			} else {
				$is_first = false;
			}
			$sql .= $f . ' = ' . $this->make_safe($v);
		}

		if (count($qualifiers) > 0) {
			$sql .= ' WHERE ';
			$is_first = true;
			foreach ($qualifiers as $f => $qual) {
				if (!$is_first) {
					$sql .= ' AND ';
				} else {
					$is_first = false;
				}
				$sql .= $f . $qual[0] . $this->make_safe($qual[1]);
			}
		}

		return $this->execute($sql);
	}

	/**
	 * Escapes a string in a driver dependent manner to make it safe to use
	 * in queries.
	 */
	public function e($s)
	{
		// Better than nothing.
		return addslashes($s);
	}

	/**
	 * Allows query errors to be logged or echoed to the user; subclasses
	 * should override this.
	 */
	protected abstract function report_error($query);

	/**
	 * The poor man's prepared statements. The first argument is an SQL query
	 * and the rest are a set of arguments to embed in it. The arguments are
	 * converted to forms safe for use in a query. Also note that if you pass
	 * in an array, it is flattened and converted into a comma-separated list
	 * (this is for convenience's sake when working with ranged queries, i.e.,
	 * those that use the IN operator) and objects passed in are serialised.
	 */
	protected function compose($q, array $args)
	{
		$i = 1;
		return preg_replace(
			'/%(?:(\d+)[:$])?([sdfu%])/e',
			"\$this->get_arg('\\1' == '' && '\\2' != '%' ? \$i++ : '\\1', '\\2', \$args)",
			$q
		);
	}

	private function get_arg($i, $type, array $args)
	{
		if ($i > count($args)) {
			throw new DB_PreparationException("Bad placeholder index: $i");
		}
		switch ($type) {
		case '%':
			return '%';
		case 's': case 'd': case 'u': case 'f':
			return $this->make_safe($args[$i - 1], $type);
		}
		throw new DB_PreparationException("Bad placeholder type: '$type'");
	}

	private function make_safe($v, $type='s')
	{
		if (is_array($v)) {
			$checked = array();
			foreach ($v as $elem) {
				$checked[] = $this->make_safe($elem, $type);
			}
			return implode(', ', $checked);
		}
		if (is_object($v)) {
			return "'" . $this->e(serialize($v)) . "'";
		}

		if (is_null($v)) {
			return 'NULL';
		}

		switch ($type) {
		case 's':
			return "'" . $this->e($v) . "'";
		case 'd': case 'u':
			return intval($v);
		case 'f':
			return floatval($v);
		}
		throw new DB_PreparationException("make_safe: bad type '$type', should not get here");
	}

	protected function log_query($q)
	{
		if ($this->logger !== false) {
			$this->logger->log($q);
		}
	}
}
