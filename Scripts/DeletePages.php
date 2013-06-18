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

if( !$script->getList() ) {
	die( "List not specified.\n\n" );
}

$reason = 'Deleting a batch of pages';
if( $script->getArg( 'reason' ) ) {
	$reason = $script->getArg( 'reason' );
}

foreach( $script->getList() as $buffer ) {
	$page = $script->getWiki()->initPage( $buffer );
	if( $script->getArg( 'undelete' ) ) {
		$page->undelete( $reason );
	}
	else {
		$page->delete( $reason );
	}
}
