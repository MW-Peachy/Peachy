<?php

namespace Tests;

use Page;

class PageTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers Page::__construct
	 * @dataProvider provideValidConstruction
	 */
	public function testCanConstruct( $title , $pageid = null , $followRedir = true , $normalize= true, $timestamp = null ) {
		if( is_int( $pageid ) ) {
			$this->expectOutputString( "Getting page info for page ID {$pageid}..\n\n" );
			$expectedPageId = $pageid;
		} else {
			$this->expectOutputString( "Getting page info for {$title}..\n\n" );
			$expectedPageId = 1234;
		}

		$page = new Page( $this->getMockWiki(), $title, $pageid, $followRedir, $normalize, $timestamp );

		$this->assertEquals( $expectedPageId, $page->get_id() );
		$this->assertEquals( 0, $page->get_namespace() );
		$this->assertEquals( 'Foo', $page->get_title() );
		$this->assertEquals( 76, $page->get_length() );
		$this->assertEquals( false, $page->redirectFollowed() );
		$this->assertEquals( 66654, $page->get_talkID() );
		$this->assertEquals( '', $page->get_preload() );
	}

	public function provideValidConstruction() {
		return array(
			array( 'Foo' ),
			array( 'Foo', 1234 ),
			array( 'Foo', 1234, false ),
			array( 'Foo', 1234, true ),
			array( 'Foo', 1234, false, true ),
			array( 'Foo', 1234, true, false ),
			array( 'Foo', 1234, true, true, '20141212121212' ),
		);
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
			->will(
				$this->returnCallback(
					function ( $params ) {
						if( $params['action'] === 'query'
							&& $params['prop'] === 'info'
							&& $params['inprop'] === 'protection|talkid|watched|watchers|notificationtimestamp|subjectid|url|readable|preload|displaytitle'
						) {
							if( array_key_exists( 'titles', $params ) ) {
								$title = $params['titles'];
								$pageid = 1234;
							} else {
								$title = 'Foo';
								$pageid = $params['pageids'];
							}
							return array(
								'query' => array(
									'pages' => array(
										$pageid => array(
											'pageid'                => $pageid,
											'ns'                    => 0,
											'title'                 => $title,
											'contentmodel'          => 'wikitext',
											'pagelanguage'          => 'en',
											'touched'               => '2014-01-26T01:13:44Z',
											'lastrevid'             => 999,
											'counter'               => '',
											'length'                => 76,
											'redirect'              => '',
											'protection'            => array(),
											'notificationtimestamp' => '',
											'talkid'                => '66654',
											'fullurl'               => 'imafullurl',
											'editurl'               => 'imaediturl',
											'readable'              => '',
											'preload'               => '',
											'displaytitle'          => 'Foo',
										)
									)
								)
							);
						}
						return array();
					}
				)
			);
		return $mock;
	}

} 