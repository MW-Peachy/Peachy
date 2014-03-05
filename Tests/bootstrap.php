<?php
/**
 * This file is references in Unit.xml and is used to load everything needed while testing peachy
 */

/**
 * All globals used in the Init file must be defined here
 * @see https://github.com/sebastianbergmann/phpunit/issues/325#issuecomment-2021106
 */
global
	$pgVerbose,
	$pgIp,
	$pgHooks,
	$pgVerbose,
	$pgIRCTrigger,
	$tmp;

define( 'PEACHY_PHPUNIT_TESTS', 'true!' );

//Include the Init file
require_once( __DIR__ . '/../Init.php' );