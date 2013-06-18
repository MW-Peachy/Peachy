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

This script deletes a bunch of pages using a list of page titles contained
in a file. The filename is argument #1.
*/

require_once( dirname( dirname(__FILE__) ) . '/Script.php' );
require_once( dirname( dirname(__FILE__) ) . '/Init.php' );

$script = new Script();

$wiki = $script->getWiki();

$reason = 'Reducing image size to comply with NFCC ([[WP:PEACHY|Peachy]])';
if( $script->getArg( 'reason' ) ) {
	$reason = $script->getArg( 'reason' );
}

$list = $wiki->categorymembers( "Category:Non-free Wikipedia file size reduction request", false, 6 );

foreach( $list as $buffer ) {
	$image = new ImageModify( $wiki, $buffer['title'] );
	
	if( $image->get_mime() == "application/ogg" ) continue;
	
	echo "\n\n--------------------\n\n";
	
	echo $image->get_title( false ) . " - " . $image->get_url()  . "\n\n";
	
	echo "Original size: " . $image->get_width() . "x" . $image->get_height() . "\n\n";
	
	$width = CLI::getInt( "New width?" );
	
	echo "\n";
	
	if( $width == 0 ) {
		if( CLI::getBool( "Are you sure?" ) ) {
			continue;
		}
	}
	
	$image->resize( $width, null, true, null, '', $reason );
	
	$image = $image->get_page();
	
	$text = $image->get_text();
	
	$text = str_ireplace( "{{Non-free reduce}}\n", "", $text );
	
	$image->edit( $text, "Removing non-free reduce template", true, false );
}
