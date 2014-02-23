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

require_once( dirname( dirname( __FILE__ ) ) . '/Script.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/Init.php' );

$script = new Script();

if( !$script->getList() ) {
	die( "List not specified.\n\n" );
}

if( !$script->getArg( 'module' ) ) {
	die( "Module not specified.\n\n" );
}

if( !file_exists( $script->getArg( 'module' ) ) ) {
	if( file_exists( $pgIP . '/Configs/Modules/' . $script->getArg( 'module' ) . '.cfg' ) ) {
		$module_config = parse_ini_file( $pgIP . '/Configs/Modules/' . $script->getArg( 'module' ) . '.cfg' );
	} else {
		die( "Invalid module specified.\n\n" );
	}
} else {
	$module_config = parse_ini_file( $script->getArg( 'module' ) );
}

if( !count( $module_config ) ) die( "Invalid module syntax.\n\n" );


foreach( $script->getList() as $buffer ){
	$page = $script->getWiki()->initPage( $buffer );

	$text = $oldtext = $page->get_text();

	$hidemore = new HideMore;
	$text = $hidemore->hide( $text );

	echo $text;

	if( isset( $module_config['typos'] ) ) {
		$text = PeachyAWBFunctions::fixTypos( $text, $buffer );
	}

	$text = $hidemore->addBack( $text );

	echo Diff::load( 'unified', $oldtext, $text );

}





