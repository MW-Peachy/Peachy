<?php

namespace Tests;

use Page;

class PageTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers Page::__construct
	 */
	public function testCanConstruct() {
		$this->expectOutputString( "Getting page info for Foo..\n\n" );

		$page = new Page( $this->getMockWiki(), 'Foo' );

		$this->assertEquals( 1234, $page->get_id() );
		$this->assertEquals( 0, $page->get_namespace() );
		$this->assertEquals( 'Foo', $page->get_title() );
		$this->assertEquals( 76, $page->get_length() );
		$this->assertEquals( false, $page->redirectFollowed() );
		$this->assertEquals( 66654, $page->get_talkID() );
		$this->assertEquals( '', $page->get_preload() );
	}

	/**
	 * @return \Wiki
	 */
	private function getMockWiki() {
		$mock = $this->getMockBuilder( 'Wiki' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'get_namespaces' )
			->will( $this->returnValue( array( 0 => '', 1 => 'Talk' ) ) );
		$mock->expects( $this->any() )
			->method( 'apiQuery' )
			->will( $this->returnCallback( function( $params ) {
				if( $params['action'] === 'query'
					&& $params['prop'] === 'info'
					&& $params['inprop'] === 'protection|talkid|watched|watchers|notificationtimestamp|subjectid|url|readable|preload|displaytitle'
					&& $params['titles'] === 'Foo' )
				{
					return array(
						'query' => array(
							'pages' => array(
								1234 => array(
									'pageid' => 1234,
									'ns' => 0,
									'title' => 'Foo',
									'contentmodel' => 'wikitext',
									'pagelanguage' => 'en',
									'touched' => '2014-01-26T01:13:44Z',
									'lastrevid' => 999,
									'counter' => '',
									'length' => 76,
									'redirect' => '',
									'protection' => array(),
									'notificationtimestamp' => '',
									'talkid' => '66654',
									'fullurl' => 'imafullurl',
									'editurl' => 'imaediturl',
									'readable' => '',
									'preload' => '',
									'displaytitle' => 'Foo',
								)
							)
						)
					);
				}
				return array();
			} ) );
		return $mock;
	}

} 