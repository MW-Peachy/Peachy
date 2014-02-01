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
	 * @covers ::iin_array
	 */
	public function test_iin_array( $needle, $haystack, $expected ) {
		$this->assertEquals( $expected, iin_array( $needle, $haystack ) );
	}

	public function provide_strtoupper_safe() {
		return array(
			array( 'foo', 'FOO' ),
			array( 'FOO', 'FOO' ),
			array( array( 'foo' ), array( 'FOO' ) ),
			array( array( array( 'foo' ) ), array( array( 'FOO' ) ) ),
			array( array( array( 'foo' ), array( 'boo' ), 'F' ), array( array( 'FOO' ), array( 'BOO' ), 'F' ) ),
		);
	}

	/**
	 * @dataProvider provide_strtoupper_safe
	 * @covers ::strtoupper_safe
	 */
	public function test_strtoupper_safe( $input, $expected ) {
		$this->assertSame( $expected, strtoupper_safe( $input ) );
	}

	public function provide_in_string() {
		return array(
			array( 'B', 'aBc', false , true ),
			array( 'b', 'aBc', false , false ),
			array( 'b', 'aBc', true , true ),
		);
	}

	/**
	 * @dataProvider provide_in_string
	 * @covers ::in_string
	 */
	public function test_in_string( $needle, $haystack, $insensitive, $expected ) {
		$this->assertEquals( $expected, in_string( $needle, $haystack, $insensitive ) );
	}

} 