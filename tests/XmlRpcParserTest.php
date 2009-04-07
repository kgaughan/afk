<?php
require_once dirname(__FILE__) . '/../internal.php';

class XmlRpcParserTest extends PHPUnit_Framework_TestCase {

	public function load($file) {
		return file_get_contents(dirname(__FILE__) . "/files/$file");
	}

	public function test_a() {
		$p = new AFK_XmlRpcParser();
		$p->parse($this->load('xmlrpc-sample.xml'));
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
}
