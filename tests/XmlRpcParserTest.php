<?php
require_once dirname(__FILE__) . '/../internal.php';

class XmlRpcParserTest extends PHPUnit_Framework_TestCase {

	public function load($file) {
		return file_get_contents(dirname(__FILE__) . "/files/$file");
	}

	public function test_hierarchy() {
		$p = new AFK_XmlRpcParser();
		$p->parse($this->load('xmlrpc-hierarchy.xml'));
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
		$this->assertEquals($expected, $p->get_result());
	}

	public function test_types() {
		$p = new AFK_XmlRpcParser();
		$p->parse($this->load('xmlrpc-types.xml'));
		list($method, $args) = $p->get_result();
		list($dt, $t1, $t2, $f1, $f2, $d, $b64) = $args;
		$this->assertEquals('2002-05-12T07:09:42+00:00', $dt->format('c'));
		$this->assertTrue($t1 === true, '1 == true');
		$this->assertTrue($t2 === true, 'true == true');
		$this->assertTrue($f1 === false, '0 == false');
		$this->assertTrue($f2 === false, 'false == false');
		$this->assertTrue($d === 1.5, '1.5 is 1.5');
		$this->assertEquals($b64, 'Hello, World!');
	}
}
