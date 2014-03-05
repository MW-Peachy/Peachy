<?php

namespace Tests;

use PeachyAWBFunctions;

class PeachyAWBFunctionsTest extends \PHPUnit_Framework_TestCase {

	public function provideFixDateTags() {
		//@todo add tests for more cases
		return array(
			array(
				'{{Wikify|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
				array(
					'{{wfy}}',
					'{{wikify}}',
					'{{Template:Wikify}}',
					'{{Template:Wikify-date}}',
					'{{wiki}}',
					'{{wiki    }}',
				)
			),
			array(
				'{{Wikify|section|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
				array(
					'{{wfy|section}}',
					'{{wikify|section}}',
					'{{Template:Wikify|section}}',
				)
			),
			array(
				'{{Cleanup|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
				array(
					'{{cleanup}}',
					'{{template:cleanup}}',
					'{{cu}}',
					'{{tidy}}',
				),
			)
		);
	}

	/**
	 * @dataProvider provideFixDateTags
	 * @covers       PeachyAWBFunctions::fixDateTags
	 */
	public function testFixDateTags( $expected, $inputTextArray ) {
		foreach( $inputTextArray as $text ){
			$this->assertEquals( $expected, PeachyAWBFunctions::fixDateTags( $text ) );
		}
	}

} 