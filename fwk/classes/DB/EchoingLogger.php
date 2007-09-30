<?php
/**
 * a logger that echos any queries to standard output for debugging purposes.
 */
class DB_EchoingLogger extends DB_BasicLogger {

	public function log($q) {
		parent::log($q);
		printf('<pre class="log">%s</pre>', e($q));
	}
}
