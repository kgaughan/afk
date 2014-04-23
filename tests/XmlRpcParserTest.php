<?php
require_once dirname(__FILE__) . '/../internal.php';

class XmlRpcParserTest extends PHPUnit_Framework_TestCase {

	private function load($file) {
		return file_get_contents(dirname(__FILE__) . "/files/$file");
	}

	private function parse($file) {
		$p = new AFK_XmlRpc_Parser();
		$p->parse($this->load($file));
		return $p->get_result();
	}

	private function format($xml) {
		$file = tempnam(sys_get_temp_dir(), 'afk-test');
		file_put_contents($file, $xml);
		$result = shell_exec('xmlstarlet fo -C -N -e "utf-8" ' . escapeshellarg($file));
		unlink($file);
		return $result;
	}

	public function test_hierarchy() {
		$r = $this->parse('xmlrpc-hierarchy.xml');
		$expected = array(
			'examples.getStateName',
			array(
				41,
				array('flibble', 2),
				'zoop',
				'',
				array(
					'wilma' => array('quux' => 'baz', 'fred' => 'barney'),
					'foo' => 'bar')));
		$this->assertEquals($expected, $r);
	}

	public function test_fault() {
		$r = $this->parse('xmlrpc-fault.xml');
		$this->assertEquals('AFK_XmlRpc_Fault', get_class($r));
		$this->assertEquals(new AFK_XmlRpc_Fault(4, 'Too many parameters.'), $r);
	}

	public function test_fault_build() {
		$expected = $this->load('xmlrpc-fault.xml');
		$result = AFK_XmlRpc_Parser::serialise_response(
			new AFK_XmlRpc_Fault(4, 'Too many parameters.'));
		$expected = $this->format($expected);
		$result = $this->format($result);
		$this->assertEquals($expected, $result);
	}

	public function test_types() {
		list($method, $args) = $this->parse('xmlrpc-types.xml');
		list($dt, $t, $f, $d, $b64) = $args;
		$this->assertEquals('2002-05-12T07:09:42+00:00', $dt->format('c'));
		$this->assertTrue($t === true, '1 == true');
		$this->assertTrue($f === false, '0 == false');
		$this->assertTrue($d === 1.5, '1.5 is 1.5');
		$this->assertEquals($b64, new AFK_Blob('Hello, World!'));
	}

	public function test_types_build() {
		$expected = $this->load('xmlrpc-types.xml');
		$result = AFK_XmlRpc_Parser::serialise_request(
			'examples.testTypes',
			array(
				new DateTime('2002-05-12T07:09:42+00:00'),
				true,
				false,
				1.5,
				new AFK_Blob('Hello, World!')));
		$expected = $this->format($expected);
		$result = $this->format($result);
		$this->assertEquals($expected, $result);
	}

	/** Runs each of the eight validation tests in turn. */
	public function test_validation() {
		for ($i = 1; $i <= 8; $i++) {
			list(, $args) = $this->parse("test$i.xml");
			$response = AFK_XmlRpc_Parser::serialise_response(
				call_user_func_array(array($this, "validate_$i"), $args));
			$expected = $this->load("test$i.response.xml");
			$response = $this->format($response);
			$expected = $this->format($expected);
			$this->assertEquals($expected, $response);
		}
	}

	/**
	 * This handler takes a single parameter, an array of structs, each of which
	 * contains at least three elements named moe, larry and curly, all <i4>s.
	 * Your handler must add all the struct elements named curly and return the
	 * result.
	 */
	private function validate_1(array $a) {
		$curlies = 0;
		foreach ($a as $s) {
			if (isset($s['curly'])) {
				$curlies += $s['curly'];
			}
		}
		return $curlies;
	}

	/**
	 * This handler takes a single parameter, a string, that contains any number
	 * of predefined entities, namely <, >, &, ' and ".
	 *
	 * Your handler must return a struct that contains five fields, all numbers:
	 * ctLeftAngleBrackets, ctRightAngleBrackets, ctAmpersands, ctApostrophes,
	 * ctQuotes.
	 *
	 * To validate, the numbers must be correct.
	 */
	private function validate_2($s) {
		$ns = array('<' => 0, '>' => 0, '&' => 0, '\'' => 0, '"' => 0);
		foreach (str_split($s) as $c) {
			if (isset($ns[$c])) {
				$ns[$c]++;
			}
		}
		return array(
			'ctAmpersands' => $ns['&'],
			'ctApostrophes' => $ns['\''],
			'ctLeftAngleBrackets' => $ns['<'],
			'ctQuotes' => $ns['"'],
			'ctRightAngleBrackets' => $ns['>']);
	}

	/**
	 * This handler takes a single parameter, a struct, containing at least three
	 * elements named moe, larry and curly, all <i4>s.  Your handler must add the
	 * three numbers and return the result.
	 */
	private function validate_3($s) {
		$n = 0;
		foreach ($s as $v) {
			$n += $v;
		}
		return $n;
	}

	/**
	 * This handler takes a single parameter, a struct.  Your handler must return
	 * the struct.
	 */
	private function validate_4($s) {
		return $s;
	}

	/**
	 * This handler takes six parameters, and returns an array containing all the
	 * parameters.
	 */
	private function validate_5($p1, $p2, $p3, $p4, $p5, $p6) {
		return array($p1, $p2, $p3, $p4, $p5, $p6);
	}

	/**
	 * This handler takes a single parameter, which is an array containing
	 * between 100 and 200 elements.  Each of the items is a string, your handler
	 * must return a string containing the concatenated text of the first and
	 * last elements.
	 */
	private function validate_6(array $a) {
		return $a[0] . $a[count($a) - 1];
	}

	/**
	 * This handler takes a single parameter, a struct, that models a daily
	 * calendar.  At the top level, there is one struct for each year.  Each year
	 * is broken down into months, and months into days.  Most of the days are
	 * empty in the struct you receive, but the entry for April 1, 2000 contains
	 * a least three elements named moe, larry and curly, all <i4>s.  Your
	 * handler must add the three numbers and return the result.
	 *
	 * Ken MacLeod: "This description isn't clear, I expected '2000.April.1' when
	 * in fact it's '2000.04.01'.  Adding a note saying that month and day are
	 * two-digits with leading 0s, and January is 01 would help." Done.
	 */
	private function validate_7(array $s) {
		$n = 0;
		foreach ($s['2000']['04']['01'] as $v) {
			$n += $v;
		}
		return $n;
	}

	/**
	 * This handler takes one parameter, and returns a struct containing three
	 * elements, times10, times100 and times1000, the result of multiplying the
	 * number by 10, 100 and 1000.
	 */
	private function validate_8($n) {
		return array(
			'times10' => $n * 10,
			'times100' => $n * 100,
			'times1000' => $n * 1000);
	}
}
