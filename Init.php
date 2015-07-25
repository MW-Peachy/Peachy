<?php

/*
This file is part of Peachy MediaWiki Bot API

Peachy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Peachy is not responsible for any damage caused to the system running it.
*/

/**
 * @file
 * Main Peachy file
 * Defines constants, initializes global variables
 * Stores Peachy class
 */

/**
 * The version that Peachy is running
 */
define( 'PEACHYVERSION', '2.0 (alpha 8)' );

/**
 * Minimum MediaWiki version that is required for Peachy
 */
define( 'MINMW', '1.23' );

/**
 * Minimum PHP version that is required for Peachy
 */
define( 'MINPHP', '5.2.1' );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_VERBOSE', -1 );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_NORMAL', 0 );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_NOTICE', 1 );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_WARN', 2 );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_ERROR', 3 );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_FATAL', 4 );

$pgIP = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

//If out /tmp directory doesnt exist, make it!
if( !file_exists( __DIR__ . '/tmp' ) ) {
	mkdir( __DIR__ . '/tmp' );
}

require_once( $pgIP . 'Includes/Exceptions.php' );
require_once( $pgIP . 'Includes/AutoUpdate.php' );

peachyCheckPHPVersion( MINPHP );

$pgHooks = array();
$pgProxy = array();
$pgUA = 'Peachy MediaWiki Bot API Version ' . PEACHYVERSION;

require_once( $pgIP . 'Includes/Hooks.php' );
require_once( $pgIP . 'HTTP.php' );
require_once( $pgIP . 'Includes/Autoloader.php' );
require_once( $pgIP . 'GenFunctions.php' );
require_once( $pgIP . 'config.inc.php' );
require_once( $pgIP . 'Includes/SSH.php' );

$pgVerbose = array();
if( $pgDisplayPechoVerbose ) $pgVerbose[] = -1;
if( $pgDisplayPechoNormal ) $pgVerbose[] = 0;
if( $pgDisplayPechoNotice ) $pgVerbose[] = 1;
if( $pgDisplayPechoWarn ) $pgVerbose[] = 2;
if( $pgDisplayPechoError ) $pgVerbose[] = 3;
if( $pgDisplayPechoFatal ) $pgVerbose[] = 4;

$pgIRCTrigger = array( '!', '.' );

//Last version check
$tmp = null;

if( function_exists( 'mb_internal_encoding' ) ) {
	mb_internal_encoding( "UTF-8" );
}

// Suppress warnings if timezone not set on server
date_default_timezone_set( @date_default_timezone_get() );

//Check for updates before loading Peachy.
if( !$pgDisableUpdates && !defined( 'PEACHY_PHPUNIT_TESTS' ) ) {
	//the below MUST have its own Http object or else things will break
	$updater = new AutoUpdate(new HTTP());
	$Uptodate = $updater->Checkforupdate();
	if( !$Uptodate ) $updater->updatePeachy();
}

require_once( $pgIP . 'Includes/Peachy.php' );

/**
 * Simple version_compare() wrapper
 * @param string $check_version string
 * @throws DependencyError
 * @return void
 */
function peachyCheckPHPVersion( $check_version ) {
	if( version_compare( PHP_VERSION, $check_version, '<' ) ) {
		throw new DependencyError( "PHP " . $check_version, "http://php.net/downloads.php" );
	}
}
