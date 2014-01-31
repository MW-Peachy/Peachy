<?php

namespace Tests;

class GenFunctionsTest extends \PHPUnit_Framework_TestCase {

	public function provide_iin_array() {
		return array(
			array( 'BOO', array( 'boo' ), true ),
			array( 'BOO', array( 'BOO' ), true ),
			array( 'boo', array( 'BOO' ), true ),
			array( 'boo', array( 'BOO', 'boo' ), true ),
			array( 'boo', array(), false ),
			array( '', array( 'boo' ), false ),
		);
	}

	/**
	 * @dataProvider provide_iin_array
	 */
	public function test_iin_array( $needle, $haystack, $expected ) {
		$this->assertEquals( $expected, iin_array( $needle, $haystack ) );
	}

} 