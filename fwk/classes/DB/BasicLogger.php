<?php
/**
 * A logger the counts how many queries have been ran.
 */
class DB_BasicLogger implements DB_Logger
{
	private $logged = 0;

	public function log($q)
	{
		$this->logged++;
	}

	public function logged()
	{
		return $this->logged;
	}
}
