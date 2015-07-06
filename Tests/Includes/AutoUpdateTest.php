<?php

namespace Tests;

use AutoUpdate;
use PHPUnit_Framework_TestCase;

class AutoUpdateTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var string this is used to cache the contents of the log when running tests and restore it after
	 */
	private static $logContents;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		//cache anything in the StableUpdate log to restore after
		if( file_exists( __DIR__ . '/../../Includes/StableUpdate.log' ) ) {
			self::$logContents = file_get_contents( __DIR__ . '/../../Includes/StableUpdate.log' );
		}
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		//restore the StableUpdate log to previous contents
		if( isset( self::$logContents ) ) {
			file_put_contents( __DIR__ . '/../../Includes/StableUpdate.log', self::$logContents );
		}
	}

	private function getUpdater( $http ) {
		return new AutoUpdate( $http );
	}

	private function getMockHttp( $data = array() ) {
		$mock = $this->getMockBuilder( 'HTTP' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( json_encode( $data ) ) );
		return $mock;
	}

	public function provideCheckforupdate() {
		return array(
			array(
				true,
				array( 'message' => 'API rate limit exceeded' ),
				'/Cant check for updates right now, next window in/'
			),
			array(
				false,
				array( 'commit' => array( 'sha' => 'testshahash' ) ),
				'/Peachy Updated!  Changes will go into effect on the next run./',
			),
			array(
				true,
				array( 'commit' => array( 'sha' => 'testshahash' ) ),
				'/Peachy is up to date/',
				serialize( array( 'commit' => array( 'sha' => 'testshahash' ) ) )
			),
			array(
				false,
				array( 'commit' => array( 'sha' => 'testshahash' ) ),
				'/Peachy Updated!  Changes will go into effect on the next run./',
				serialize( array( 'commit' => array( 'sha' => 'differenthash!' ) ) )
			),
		);
	}

	/**
	 * @dataProvider provideCheckforupdate
	 * @covers       AutoUpdate::Checkforupdate
	 * @covers	AutoUpdate::cacheLastGithubETag
	 * @covers	AutoUpdate::getTimeToNextLimitWindow
	 * @covers	AutoUpdate::__construct
	 * @covers	AutoUpdate::updatePeachy
	 * @covers	AutoUpdate::getLocalPath
	 * @covers	AutoUpdate::copyOverGitFiles
	 * @covers	AutoUpdate::rrmdir
	 */
	public function testCheckforupdate( $expected, $data, $outputRegex = '/.*?/', $updatelog = null, $experimental = false, $wasexperimental = false ) {
		$updater = $this->getUpdater( $this->getMockHttp( $data ) );
		if( $updatelog === null ) {
			if( file_exists( __DIR__ . '/../../Includes/' . ( $experimental ? 'Update.log' : 'StableUpdate.log' ) ) ) {
				unlink( __DIR__ . '/../../Includes/' . ( $experimental ? 'Update.log' : 'StableUpdate.log' ) );
			}
		} else {
			file_put_contents( __DIR__ . '/../../Includes/' . ( $experimental ? 'Update.log' : 'StableUpdate.log' ), $updatelog );
		}

		if( $wasexperimental ) {
			file_put_contents( __DIR__ . '/../../Includes/updateversion', serialize( 'master' ) );
		} else file_put_contents( __DIR__ . '/../../Includes/updateversion', serialize( 'stable' ) );

		$this->expectOutputRegex( $outputRegex );
		$result = $updater->Checkforupdate();
		if( !$result ) $updater->updatePeachy();
		$this->assertEquals( $expected, $result );
	}

} 
