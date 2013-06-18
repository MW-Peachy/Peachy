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

class Logging {

	public static function doLog( $loggingstuff, &$text, &$cat ) {
		
		$types = $loggingstuff[0];
		$logFile = $loggingstuff[1];
		
		if( in_array( $cat, $types ) ) {
			file_put_contents( $logFile, trim($text) . "\n", FILE_APPEND );
		}
		
	}

	public static function load( $types, $logfile ) {
		global $pgHooks;
		
		$pgHooks['OutputText'][] = array( 'Logging::dolog', array($types, $logFile ) );
		
	}

}