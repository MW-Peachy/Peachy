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
*/

error_reporting( E_ALL | E_STRICT );

require_once( dirname( dirname(__FILE__) ) . '/Script.php' );
require_once( dirname( dirname(__FILE__) ) . '/Init.php' );

$script = new Script();
Peachy::loadPlugin( 'rped' );

if( !$script->getList() ) {
	die( "List not specified.\n\n" );
}

$rped = RPED::load( $script->getWiki() );

$searching = false;
if( $script->getArg( 'searching' ) ) {
    $searching = true;
}


if( $script->getArg( 'daemonize' ) ) $daemonize = true;

if ( isset( $daemonize ) && $daemonize ) {

    $pid = pcntl_fork(); // fork
    if ( $pid < 0 ) {
        exit;
    }
    else if ( $pid ) { // parent
        exit;
    }
    // child
    $sid = posix_setsid();
    if ( $sid < 0 ) {
            exit;
    }
}

$maxCount = 1000;
$count = 0;
$rawCount = 0;
$rpedArray = array();

foreach( $script->getList() as $buffer ) {
	if ( $searching ) {
        if ( $buffer == $argv[2] ) {
            $searching = false;
        }
    }
    
    if ( !$searching ) {
        $count++;
        if ( $count > $maxCount ) {
            try {
            	$rped->insertArray( $rpedArray, 2000 );
            }
            catch( Exception $e ) {
            	pecho( "Peachy Error: " . $e->getMessage(), PECHO_FATAL );
            	continue;
            }
            
            $count = 0;
            unset ( $rpedArray );
            $rpedArray = array();
        }
        $rpedArray[] = $buffer;
    }
    $rawCount++;
    if ( $rawCount % 1000 == 0 && !$daemonize ) {
        pecho( $buffer . '\n', PECHO_NORMAL );
        $rawCount = 0;
    }
}
$rped->insertArray( $rpedArray, 2000 );
