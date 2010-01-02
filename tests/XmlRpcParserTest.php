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
		$result = shell_exec('xmlstarlet fo ' . escapeshellarg($file));
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
		$this->assertEquals($b64, 'Hello, World!');
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
}
