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

The purpose of this script is to get the titles of all new, moved, deleted and restored
pages from #en.wikipedia and add/delete them from the rped_table.
*/

error_reporting( E_ALL | E_STRICT );

require_once( dirname( dirname(__FILE__) ) . '/Script.php' );
require_once( dirname( dirname(__FILE__) ) . '/Init.php' );

$script = new Script();
Peachy::loadPlugin( 'rped' );

if( $script->getArg( 'daemonize' ) ) {
    $daemonize = true;
}
$wiki = $script->getWiki();
$rped = new RPED( $wiki );

# TODO: Get this script to use the IRC class.

$config = $wiki->get_configuration();
$host = $config['host'];
$port = $config['port'];
$nick = $config['nick'];
$ident = $config['ident'];
$realname = $config['realname'];
$chan = $config['chan'];
$deleteLine = $config['deleteline'];
$moveLine = $config['moveline'];
$deletedWord = $config['deletedword'];
$newCharacter = $config['newcharacter'];

$readbuffer = "";
$startSep = "[[";
$endSep = "]]";

// open a socket connection to the IRC server
$fp = fsockopen( $host, $port, $erno, $errstr, 30 );

// print the error if there is no connection
if ( !$fp ) {
    echo $errstr . " (" . $errno . ")<br />\n";
    die();
}

// write data through the socket to join the channel
fwrite( $fp, "NICK " . $nick . "\r\n" );
fwrite( $fp, "USER " . $ident . " " . $host . " bla :" . $realname . "\r\n" );
fwrite( $fp, "JOIN :" . $chan . "\r\n" );

# Launch daemon!
if ( isset( $daemonize ) && $daemonize ) {

    $pid = pcntl_fork(); // fork
    if ( $pid < 0 ) {
        exit;
    }
    else if ( $pid ){ // parent
        exit;
    }
    // child
    $sid = posix_setsid();
    if ( $sid < 0 ) {
            exit;
    }
}

while ( !feof( $fp ) ) {
    $line =  fgets( $fp, 512 );
    if ( !isset ( $daemonize ) || !$daemonize ) {
        echo $line;
    }
    $pingLine = explode( ' ', $line );
    if ( strtolower( $pingLine[0] ) == 'ping' ) {
        $response = "PONG " . $pingLine[1] . "\n";
        fwrite( $fp, "PONG " . $response );
    }
    usleep( 10 );
    $startPos = strpos( $line, $startSep );
    $endPos = strpos( $line, $endSep );
    $subLine = substr( $line, $startPos + 5, $endPos -$startPos -8 );
    if ( $subLine == $deleteLine ) {
        $delstartPos = strpos( $line, $startSep, $endPos );
        $delendPos = strpos( $line, $endSep, $endPos + 1 );
        $delLine = substr( $line, $delstartPos + 5, $delendPos -$delstartPos -8 );
        $action = substr( $line, $delstartPos -9, 7 );
        if ( $action == $deletedWord ) {
            try {
            	$rped->delete( $delLine );
            }
            catch( Exception $e ) {
            	pecho( "Peachy Error: " . $e->getMessage(), PECHO_FATAL );
            	continue;
            }
        } else {
            try {
            	$rped->insert( $delLine );
            }
            catch( Exception $e ) {
            	pecho( "Peachy Error: " . $e->getMessage(), PECHO_FATAL );
            	continue;
            }
        }
    }
    if ( $subLine == $moveLine ) {
        $delstartPos = strpos( $line, $startSep, $endPos );
        $delendPos = strpos( $line, $endSep, $endPos + 1 );
        $delstartPos = strpos( $line, $startSep, $delstartPos + 1 );
        $delendPos = strpos( $line, $endSep, $delendPos + 1 );
        $delLine = substr( $line, $delstartPos + 2, $delendPos -$delstartPos -2 );
        $rped->insert( $delLine );
    }
    if ( substr( $line, $endPos + 5, 1 ) == $newCharacter || substr( $line, $endPos + 6, 1 ) == $newCharacter ) {
        
        try {
        	$rped->insert( $subLine );
        }
        catch( Exception $e ) {
        	pecho( "Peachy Error: " . $e->getMessage(), PECHO_FATAL );
        	continue;
        }
    }
}

fclose( $fp );